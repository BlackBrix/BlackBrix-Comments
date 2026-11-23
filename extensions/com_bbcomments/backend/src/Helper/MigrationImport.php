<?php
/**
 * BlackBrix Comments migration import, JSON format v1.
 * Copyright (c) 2026 BlackBrix. GPL-3.0-or-later.
 * This class NEVER loads Akeeba Engage code or tables.
 */
namespace BlackBrix\Component\Bbcomments\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class MigrationImport
{
    public const SOURCE = 'akeeba-engage-3.4.3';
    public const FORMAT = 'blackbrix-comments-import';
    public const VERSION = 1;
    public const MAX_BYTES = 10485760; // 10 MiB; never persist an uploaded file in webroot
    public const MAX_COMMENTS = 20000;

    public static function siteFingerprint(string $secret): string
    {
        return hash('sha256', 'bbcomments-migration-site-v1:' . $secret);
    }

    /**
     * Detect remaining Engage extension records AND the exact legacy data tables
     * with this installation's Joomla database prefix, regardless of whether
     * the tables are empty. Never delete, alter, or import any of these rows.
     */
    public static function assertEngageRemoved(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $q = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__extensions'))
                ->where('(' . $db->quoteName('element') . ' IN ('
                    . implode(',', array_map([$db, 'quote'], [
                        'pkg_engage', 'com_engage', 'mod_engage_latest',
                    ])) . ') OR (' . $db->quoteName('type') . ' = ' . $db->quote('plugin')
                    . ' AND (' . $db->quoteName('element') . ' IN ('
                    . $db->quote('engage') . ',' . $db->quote('engagecache') . ') OR '
                    . $db->quoteName('folder') . ' = ' . $db->quote('engage') . ')))');
            $hasExtension = (int) $db->setQuery($q)->loadResult() > 0;
            // Only examine tables with this Joomla installation's DB prefix.
            $tables = $db->getTableList();
            $prefix = $db->getPrefix();
            $hasLegacyTables = false;
            foreach ($tables as $table) {
                if (str_starts_with($table, $prefix . 'engage_')) {
                    $hasLegacyTables = true;
                    break;
                }
            }
        } catch (\Throwable $e) {
            // Fail closed if the legacy state cannot be established.
            self::fail('COM_BBCOMMENTS_MIGRATION_ENGAGE_CHECK_FAILED');
        }

        if ($hasExtension) {
            self::fail('COM_BBCOMMENTS_MIGRATION_ENGAGE_INSTALLED');
        }
        if ($hasLegacyTables) {
            self::fail('COM_BBCOMMENTS_MIGRATION_ENGAGE_TABLES');
        }
    }

    /** Translate every migration validation error via Joomla's language files. */
    private static function fail(string $key, mixed ...$args): never
    {
        throw new RuntimeException($args ? Text::sprintf($key, ...$args) : Text::_($key));
    }

    /**
     * Validate the complete import and build parent-first records. Throws before any writes.
     * @return array{count:int,articles:int,changed_html:int,rows:array,users_missing:int}
     */
    public static function inspect(string $json): array
    {
        self::assertEngageRemoved(); // Always before preview and before import writes.

        if (strlen($json) > self::MAX_BYTES) {
            self::fail('COM_BBCOMMENTS_MIGRATION_TOO_LARGE');
        }
        try {
            $d = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            self::fail('COM_BBCOMMENTS_MIGRATION_BAD_JSON');
        }
        if (!is_array($d) || ($d['format'] ?? '') !== self::FORMAT || ($d['version'] ?? null) !== self::VERSION
            || ($d['source'] ?? '') !== self::SOURCE) {
            self::fail('COM_BBCOMMENTS_MIGRATION_BAD_FORMAT');
        }
        $secret = (string) Factory::getApplication()->get('secret', '');
        if ($secret === '' || !isset($d['site_fingerprint']) || !is_string($d['site_fingerprint'])
            || !hash_equals(self::siteFingerprint($secret), $d['site_fingerprint'])) {
            self::fail('COM_BBCOMMENTS_MIGRATION_IDENTITY_MISMATCH');
        }
        $articles = $d['articles'] ?? null;
        $comments = $d['comments'] ?? null;
        $unsubs = $d['unsubscribes'] ?? [];
        if (!is_array($unsubs) || !array_is_list($unsubs) || count($unsubs) > self::MAX_COMMENTS) {
            self::fail('COM_BBCOMMENTS_MIGRATION_BAD_UNSUB_LIST');
        }
        if (($d['unsubscribe_count'] ?? 0) !== count($unsubs)) {
            self::fail('COM_BBCOMMENTS_MIGRATION_UNSUB_COUNT');
        }
        if (!is_array($articles) || !array_is_list($articles) || !is_array($comments) || !array_is_list($comments)) {
            self::fail('COM_BBCOMMENTS_MIGRATION_BAD_ARRAYS');
        }
        if ((!count($comments) && !count($unsubs)) || count($comments) > self::MAX_COMMENTS) {
            self::fail('COM_BBCOMMENTS_MIGRATION_EMPTY_OR_LIMIT');
        }
        if (($d['comment_count'] ?? null) !== count($comments)) {
            self::fail('COM_BBCOMMENTS_MIGRATION_COMMENT_COUNT');
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $expected = [];
        foreach ($articles as $a) {
            if (!is_array($a) || !isset($a['id'], $a['title'], $a['alias']) || !is_int($a['id']) || $a['id'] <= 0
                || !is_string($a['title']) || !is_string($a['alias']) || isset($expected[$a['id']])) {
                self::fail('COM_BBCOMMENTS_MIGRATION_BAD_ARTICLE_META');
            }
            $expected[$a['id']] = $a;
        }
        if (!$expected) {
            self::fail('COM_BBCOMMENTS_MIGRATION_MISSING_ARTICLE_META');
        }

        // Validate against CURRENT Joomla articles; article IDs are never replaced by title/alias guesses.
        $actual = [];
        foreach (array_chunk(array_keys($expected), 250) as $ids) {
            $q = $db->getQuery(true)->select(['id', 'title', 'alias', 'asset_id'])
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
            foreach ($db->setQuery($q)->loadAssocList() as $a) {
                $actual[(int) $a['id']] = $a;
            }
        }
        foreach ($expected as $id => $a) {
            if (!isset($actual[$id]) || (int) $actual[$id]['asset_id'] <= 0) {
                self::fail('COM_BBCOMMENTS_MIGRATION_ARTICLE_MISSING', $id);
            }
            if ($actual[$id]['title'] !== $a['title'] || $actual[$id]['alias'] !== $a['alias']) {
                self::fail('COM_BBCOMMENTS_MIGRATION_ARTICLE_CHANGED', $id);
            }
        }

        // Opt-outs are matched by article ID and email; no old asset IDs are trusted.
        $seenUnsub = [];
        foreach ($unsubs as $o) {
            if (!is_array($o) || !is_int($o['article_id'] ?? null)
                || !isset($expected[$o['article_id']]) || !is_string($o['email'] ?? null)
                || strlen($o['email']) > 255 || !filter_var($o['email'], FILTER_VALIDATE_EMAIL)) {
                self::fail('COM_BBCOMMENTS_MIGRATION_BAD_UNSUB');
            }
            $key = $o['article_id'] . ':' . strtolower($o['email']);
            if (isset($seenUnsub[$key])) self::fail('COM_BBCOMMENTS_MIGRATION_DUP_UNSUB');
            $seenUnsub[$key] = true;
        }
        $byOld = [];
        $userIds = [];
        $changedHtml = 0;
        foreach ($comments as $c) {
            if (!is_array($c) || !is_int($c['old_id'] ?? null) || $c['old_id'] <= 0
                || !is_int($c['article_id'] ?? null) || !isset($expected[$c['article_id']])
                || (isset($c['parent_id']) && (!is_int($c['parent_id']) || $c['parent_id'] <= 0))
                || !is_string($c['body'] ?? null) || strlen($c['body']) > 524288
                || !is_int($c['enabled'] ?? null) || !in_array($c['enabled'], [0, 1, -2, -3], true)
                || isset($byOld[$c['old_id']])) {
                self::fail('COM_BBCOMMENTS_MIGRATION_BAD_COMMENT');
            }
            foreach (['name', 'email'] as $field) {
                if (isset($c[$field]) && (!is_string($c[$field]) || strlen($c[$field]) > 255)) {
                    self::fail('COM_BBCOMMENTS_MIGRATION_BAD_AUTHOR');
                }
            }
            foreach (['created', 'modified'] as $field) {
                if (isset($c[$field]) && (!is_string($c[$field]) || !self::validDate($c[$field]))) {
                    self::fail('COM_BBCOMMENTS_MIGRATION_BAD_DATE');
                }
            }
            foreach (['created_by', 'modified_by'] as $field) {
                if (isset($c[$field]) && (!is_int($c[$field]) || $c[$field] < 0)) {
                    self::fail('COM_BBCOMMENTS_MIGRATION_BAD_USER_ID');
                }
                if (($c[$field] ?? 0) > 0) {
                    $userIds[$c[$field]] = true;
                }
            }
            // Legacy Engage guestbook entries may use <h5> as a title.
            // Preserve the visual emphasis within BlackBrix's narrow HTML allowlist.
            // This is import-only: do not modify normal frontend comment input.
            $normalizedBody = self::convertLegacyHeadings($c['body']);
            $clean = HtmlFilter::filterText($normalizedBody);
            if ($clean !== $c['body']) {
                $changedHtml++;
            }
            if (trim(strip_tags($clean)) === '') {
                self::fail('COM_BBCOMMENTS_MIGRATION_EMPTY_BODY', $c['old_id']);
            }
            $c['clean_body'] = $clean;
            $byOld[$c['old_id']] = $c;
        }
        // All parents must be included; never silently lose a reply or relink it to another article.
        $visiting = [];
        $ordered = [];
        $visit = function (int $id) use (&$visit, &$visiting, &$ordered, &$byOld): void {
            if (($visiting[$id] ?? 0) === 2) return;
            if (($visiting[$id] ?? 0) === 1) self::fail('COM_BBCOMMENTS_MIGRATION_CYCLE');
            $visiting[$id] = 1;
            $c = $byOld[$id];
            $p = $c['parent_id'] ?? null;
            if ($p !== null) {
                if (!isset($byOld[$p])) self::fail('COM_BBCOMMENTS_MIGRATION_MISSING_PARENT', $p);
                if ($byOld[$p]['article_id'] !== $c['article_id']) self::fail('COM_BBCOMMENTS_MIGRATION_PARENT_ARTICLE');
                $visit($p);
            }
            $visiting[$id] = 2;
            $ordered[] = $c;
        };
        foreach (array_keys($byOld) as $id) $visit($id);

        // Import must be all-or-nothing; reject even a partially imported source file.
        foreach (array_chunk(array_keys($byOld), 250) as $ids) {
            $q = $db->getQuery(true)->select('old_comment_id')
                ->from($db->quoteName('#__bbcomments_import_map'))
                ->where($db->quoteName('source') . ' = ' . $db->quote(self::SOURCE))
                ->where($db->quoteName('old_comment_id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
            $existing = $db->setQuery($q)->loadColumn();
            if ($existing) {
                self::fail('COM_BBCOMMENTS_MIGRATION_ALREADY_IMPORTED', (int) $existing[0]);
            }
        }
        $presentUsers = [];
        foreach (array_chunk(array_keys($userIds), 250) as $ids) {
            $q = $db->getQuery(true)->select('id')->from($db->quoteName('#__users'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
            foreach ($db->setQuery($q)->loadColumn() as $id) $presentUsers[(int)$id] = true;
        }
        return ['count'=>count($byOld), 'articles'=>count($expected), 'changed_html'=>$changedHtml,
            'rows'=>$ordered, 'users_missing'=>count(array_diff_key($userIds, $presentUsers)),
            'actual_articles'=>$actual, 'present_users'=>$presentUsers, 'unsubscribes'=>$unsubs,
            'unsubscribe_count'=>count($unsubs)];
    }

    public static function perform(string $json): int
    {
        $preview = self::inspect($json); // Re-check IDs, title, aliases and duplicate state on commit.
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $mapping = [];
        $db->transactionStart();
        try {
            foreach ($preview['rows'] as $c) {
                $parent = $c['parent_id'] ?? null;
                $newParent = $parent === null ? null : ($mapping[$parent] ?? null);
                if ($parent !== null && $newParent === null) self::fail('COM_BBCOMMENTS_MIGRATION_PARENT_DURING_IMPORT');
                $user = (int) ($c['created_by'] ?? 0);
                if ($user && !isset($preview['present_users'][$user])) $user = 0;
                $modifier = (int) ($c['modified_by'] ?? 0);
                if ($modifier && !isset($preview['present_users'][$modifier])) $modifier = 0;
                $a = $preview['actual_articles'][$c['article_id']];
                $fields = [
                    'parent_id'=>$newParent, 'asset_id'=>(int)$a['asset_id'], 'body'=>$c['clean_body'],
                    'name'=>($c['name'] ?? null), 'email'=>($c['email'] ?? null), 'ip'=>null,
                    'user_agent'=>'BlackBrix JSON migration', 'enabled'=>$c['enabled'],
                    'created'=>$c['created'] ?? null, 'created_by'=>$user,
                    'modified'=>$c['modified'] ?? null, 'modified_by'=>$modifier ?: null,
                ];
                $q = $db->getQuery(true)->insert($db->quoteName('#__bbcomments_comments'))
                    ->columns(array_map([$db, 'quoteName'], array_keys($fields)))
                    ->values(implode(',', array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), array_values($fields))));
                $db->setQuery($q)->execute();
                $newId = (int) $db->insertid();
                if ($newId <= 0) self::fail('COM_BBCOMMENTS_MIGRATION_INSERT_FAILED');
                $mapping[$c['old_id']] = $newId;
                $q = $db->getQuery(true)->insert($db->quoteName('#__bbcomments_import_map'))
                    ->columns([$db->quoteName('source'),$db->quoteName('old_comment_id'),$db->quoteName('new_comment_id'),$db->quoteName('article_id'),$db->quoteName('imported_at')])
                    ->values(implode(',', [
                        $db->quote(self::SOURCE), (string)(int)$c['old_id'], (string)$newId,
                        (string)(int)$c['article_id'], $db->quote(gmdate('Y-m-d H:i:s')),
                    ]));
                $db->setQuery($q)->execute();
            }
            // Do not reactivate old email subscriptions against users' prior decisions.
            // Existing opt-outs stay in place; duplicates are ignored idempotently.
            foreach ($preview['unsubscribes'] as $o) {
                $assetId = (int)$preview['actual_articles'][$o['article_id']]['asset_id'];
                $q = $db->getQuery(true)->insert($db->quoteName('#__bbcomments_unsubscribe'))
                    ->columns([$db->quoteName('asset_id'), $db->quoteName('email')])
                    ->values($assetId . ', ' . $db->quote($o['email']));
                $sql = (string)$q;
                $db->setQuery(preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $sql))->execute();
            }
            $db->transactionCommit();
            return count($mapping);
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }

    /**
     * Convert historical Engage <h5> headings to supported paragraph/bold markup.
     * Original content is still filtered by HtmlFilter afterwards, including links.
     */
    private static function convertLegacyHeadings(string $html): string
    {
        $normalized = preg_replace_callback(
            '~<h5\b[^>]*>(.*?)</h5\s*>~is',
            static fn(array $match): string => '<p><strong>' . $match[1] . '</strong></p>',
            $html
        );

        if ($normalized === null) {
            self::fail('COM_BBCOMMENTS_MIGRATION_HEADING_FAILED');
        }

        return $normalized;
    }

    private static function validDate(string $s): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $s, new \DateTimeZone('UTC'));
        return $d !== false && $d->format('Y-m-d H:i:s') === $s;
    }
}
