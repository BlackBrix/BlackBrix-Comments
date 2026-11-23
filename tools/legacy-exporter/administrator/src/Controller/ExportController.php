<?php
/** BlackBrix independent migration EXPORTER for Akeeba Engage 3.4.3 schema.
 *  Copyright (c) 2026 BlackBrix. GPL-3.0-or-later.
 *  Reads database only; no dependencies on Akeeba/FOF code; never removes data.
 */
namespace BlackBrix\Component\Bbengageexport\Administrator\Controller;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class ExportController extends BaseController
{
    public function download(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.admin')) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        if (!Session::checkToken('post')) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        // Verify old installation's actual table and required columns before accessing it.
        $tables = $db->getTableList();
        $oldTable = $db->getPrefix() . 'engage_comments';
        if (!in_array($oldTable, $tables, true)) {
            throw new RuntimeException('Akeeba Engage 3.4.3 comments table not found.');
        }
        $columns = array_keys($db->getTableColumns('#__engage_comments'));
        foreach (['id','parent_id','asset_id','body','name','email','enabled','created','created_by','modified','modified_by'] as $column) {
            if (!in_array($column, $columns, true)) {
                throw new RuntimeException('Missing Engage 3.4.3 column: ' . $column);
            }
        }
        $q = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__engage_comments'));
        $count = (int) $db->setQuery($q)->loadResult();
        if ($count < 1) throw new RuntimeException('There are no comments to export.');
        if ($count > 20000) throw new RuntimeException('More than 20,000 comments; this test exporter requires batching support.');

        $q = $db->getQuery(true)
            ->select(['c.*', 'a.id AS article_id', 'a.title AS article_title', 'a.alias AS article_alias',
                      'u.name AS registered_name', 'u.email AS registered_email'])
            ->from($db->quoteName('#__engage_comments','c'))
            ->leftJoin($db->quoteName('#__content','a') . ' ON a.asset_id = c.asset_id')
            ->leftJoin($db->quoteName('#__users','u') . ' ON u.id = c.created_by')
            ->order('c.id ASC');
        $rows = $db->setQuery($q)->loadAssocList();
        if (count($rows) !== $count) throw new RuntimeException('Comment count changed during export; repeat on a quiescent site.');
        $articleMap = [];
        $commentMap = [];
        $comments = [];
        foreach ($rows as $r) {
            $oldId = (int)$r['id'];
            $articleId = (int)($r['article_id'] ?? 0);
            if ($oldId <= 0 || isset($commentMap[$oldId]) || $articleId <= 0) {
                throw new RuntimeException("Comment ID $oldId has no unambiguous com_content article; export aborted.");
            }
            $commentMap[$oldId] = ['parent'=>$r['parent_id'] === null ? null : (int)$r['parent_id'], 'article_id'=>$articleId];
            $articleMap[$articleId] = ['id'=>$articleId, 'title'=>(string)$r['article_title'], 'alias'=>(string)$r['article_alias']];
            $comments[] = [
                'old_id'=>$oldId, 'article_id'=>$articleId,
                'parent_id'=>$r['parent_id'] === null ? null : (int)$r['parent_id'],
                'body'=>(string)$r['body'], 'enabled'=>(int)$r['enabled'],
                'name'=> $r['name'] ?: ($r['registered_name'] ?: null),
                'email'=> $r['email'] ?: ($r['registered_email'] ?: null),
                'created'=>$r['created'] ?: null, 'created_by'=>(int)($r['created_by'] ?: 0),
                'modified'=>$r['modified'] ?: null, 'modified_by'=>(int)($r['modified_by'] ?: 0),
            ];
        }
        // Preserve historical per-article email opt-outs as well.
        $unsubscribes = [];
        $oldUnsub = $db->getPrefix() . 'engage_unsubscribe';
        if (in_array($oldUnsub, $tables, true)) {
            $q = $db->getQuery(true)
                ->select(['o.email', 'a.id AS article_id', 'a.title AS article_title', 'a.alias AS article_alias'])
                ->from($db->quoteName('#__engage_unsubscribe','o'))
                ->leftJoin($db->quoteName('#__content','a') . ' ON a.asset_id = o.asset_id');
            foreach ($db->setQuery($q)->loadAssocList() as $o) {
                $articleId = (int)($o['article_id'] ?? 0);
                if ($articleId <= 0 || !filter_var($o['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Unsubscribe row without a valid article/email; export aborted.');
                }
                $articleMap[$articleId] = ['id'=>$articleId, 'title'=>(string)$o['article_title'], 'alias'=>(string)$o['article_alias']];
                $unsubscribes[] = ['article_id'=>$articleId, 'email'=>(string)$o['email']];
            }
        }
        foreach ($commentMap as $oldId => $c) {
            if ($c['parent'] !== null) {
                if (!isset($commentMap[$c['parent']]) || $commentMap[$c['parent']]['article_id'] !== $c['article_id']) {
                    throw new RuntimeException("Missing or cross-article parent for comment $oldId.");
                }
            }
        }
        $secret = (string)$app->get('secret', '');
        if ($secret === '') throw new RuntimeException('Missing Joomla site secret.');
        $payload = [
            'format'=>'blackbrix-comments-import', 'version'=>1,
            'source'=>'akeeba-engage-3.4.3', 'exported_utc'=>gmdate('c'),
            'site_fingerprint'=>hash('sha256','bbcomments-migration-site-v1:' . $secret),
            'comment_count'=>count($comments), 'unsubscribe_count'=>count($unsubscribes),
            'articles'=>array_values($articleMap), 'comments'=>$comments,
            'unsubscribes'=>$unsubscribes,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > 10485760) throw new RuntimeException('JSON exceeds 10 MiB; do not proceed with old extension uninstall.');
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="blackbrix-engage-export-' . gmdate('Ymd-His') . '.json"');
        header('Content-Length: ' . strlen($json));
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        echo $json;
        $app->close();
    }
}
