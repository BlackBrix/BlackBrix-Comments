<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 *
 * BlackBrix Comments installation/update script (Joomla 5.4+ / 6).
 * No compatibility bridge to Akeeba Engage, FOF, or other legacy packages.
 */

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Helper\TemplateEmails;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

class Pkg_BbcommentsInstallerScript extends InstallerScript
{
    protected $minimumPhp = '8.2.0';
    protected $minimumJoomla = '5.4.0';
    protected $allowDowngrades = true;

    /** @var array<string, array{0:string,1:string}> */
    private const PLUGINS = [
        'actionlog' => ['plugin', 'bbcomments'],
        'content'   => ['plugin', 'bbcomments'],
        'bbcomments'=> ['plugin', 'email'],
        'privacy'   => ['plugin', 'bbcomments'],
        'system'    => ['plugin', 'bbcomments'],
        'user'      => ['plugin', 'bbcomments'],
    ];

    /** @var string[] */
    private const AUTO_ENABLE_ON_FRESH_INSTALL = ['content', 'bbcomments', 'user'];

    /**
     * Files exclusively shipped by the early BlackBrix alpha packages and no longer used.
     * No database tables or user-created data are removed on update.
     */
    private const OBSOLETE_FILES = [
        'administrator/components/com_bbcomments/src/Model/UpgradeModel.php',
        'administrator/components/com_bbcomments/src/Mixin/ModelFilterDateTimeTrait.php',
        'administrator/components/com_bbcomments/tmpl/common/joomla_eol.php',
        'media/com_bbcomments/css/amp.css',
        'media/com_bbcomments/css/amp.scss',
    ];

    private const OLD_SITE_TEMPLATES = [
        'amp.php', 'amp_list.php', 'default.php', 'default_form.php',
        'default_list.php', 'default_login.php', 'form.php', 'form.j4.php',
    ];

    /**
     * Stop BEFORE Joomla installs or updates any child extension if the original
     * Engage package, one of its extensions, or its legacy data tables remains.
     * Never remove old extensions, tables, or user data automatically.
     */
    public function preflight($type, $parent): bool
    {
        if (!parent::preflight($type, $parent)) {
            return false;
        }

        if (!in_array($type, ['install', 'update', 'discover_install'], true)) {
            return true;
        }

        $app = Factory::getApplication();
        $language = $app->getLanguage();
        // On fresh installs the package language may not yet be registered.
        $language->load('pkg_bbcomments.sys', __DIR__, null, true);
        $language->load('pkg_bbcomments.sys', JPATH_ADMINISTRATOR, null, true);

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

            if ((int) $db->setQuery($q)->loadResult() > 0) {
                $app->enqueueMessage(
                    $this->migrationInstallerText('PKG_BBCOMMENTS_ENGAGE_INSTALLED'), 'error'
                );
                return false;
            }

            $tables = $db->getTableList();
            $prefix = $db->getPrefix();
            // Includes legacy tables such as engage_emailtemplates, if present.
            $hasLegacyTables = false;
            foreach ($tables as $table) {
                if (str_starts_with($table, $prefix . 'engage_')) {
                    $hasLegacyTables = true;
                    break;
                }
            }
            if ($hasLegacyTables) {
                $app->enqueueMessage(
                    $this->migrationInstallerText('PKG_BBCOMMENTS_ENGAGE_TABLES'), 'error'
                );
                return false;
            }
        } catch (\Throwable $e) {
            // Fail closed: an unavailable database must NEVER bypass the guard.
            $this->warn('Could not run the pre-install Engage safety check', $e);
            $app->enqueueMessage(
                $this->migrationInstallerText('PKG_BBCOMMENTS_ENGAGE_CHECK_FAILED'), 'error'
            );
            return false;
        }

        return true;
    }

    /** Translated before and after the language package is installed. */
    private function migrationInstallerText(string $key): string
    {
        $message = Text::_($key);
        if ($message !== $key) {
            return $message;
        }

        $isGerman = str_starts_with(
            strtolower(Factory::getApplication()->getLanguage()->getTag()), 'de-'
        );
        $fallback = [
            'PKG_BBCOMMENTS_ENGAGE_INSTALLED' => [
                'Akeeba Engage ist noch installiert (auch deaktivierte Erweiterungen zählen). BlackBrix Comments wurde nicht installiert. Zuerst den JSON-Export und ein vollständiges Backup prüfen; anschließend Akeeba Engage und seine Untererweiterungen deinstallieren.',
                'Akeeba Engage is still installed (including disabled extensions). BlackBrix Comments was not installed. First verify the JSON export and a complete backup; then uninstall Akeeba Engage and its subextensions.',
            ],
            'PKG_BBCOMMENTS_ENGAGE_TABLES' => [
                'Die bisherigen Akeeba-Engage-Datentabellen existieren noch. BlackBrix Comments wurde nicht installiert. Export und Backup prüfen; die Altinstallation vollständig und datenschonend bereinigen.',
                'Legacy Akeeba Engage data tables still exist. BlackBrix Comments was not installed. Verify export and backup; complete the legacy removal without deleting unbacked-up data.',
            ],
            'PKG_BBCOMMENTS_ENGAGE_CHECK_FAILED' => [
                'Die Sicherheitsprüfung auf Akeeba Engage konnte nicht abgeschlossen werden. Installation vorsichtshalber abgebrochen; Details stehen im Joomla-Protokoll.',
                'Could not complete the Akeeba Engage safety check. Installation aborted as a precaution; see the Joomla log for details.',
            ],
        ];
        return $fallback[$key][$isGerman ? 0 : 1] ?? $key;
    }

    public function postflight(string $type, PackageAdapter $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        // Joomla creates / updates all child extension records before package postflight.
        // Preserve all existing plugin enabled states on updates.
        try {
            $this->associateChildrenWithPackage();

            if ($type === 'install' || $type === 'discover_install') {
                $this->enableEssentialPlugins();
            }
        } catch (\Throwable $e) {
            $this->warn('Could not update package associations/plugin defaults', $e);
        }

        if ($type === 'update') {
            $this->removeKnownObsoleteFiles();
        }

        // Alpha9: idempotent import mapping table; never touch comment/opt-out data.
        try {
            $this->ensureImportMap();
        } catch (\Throwable $e) {
            $this->warn('Could not create import mapping table', $e);
        }

        $this->refreshNamespaceMap();
        $this->updateMailTemplates();

        return true;
    }

    private function ensureImportMap(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName('#__bbcomments_import_map')
            . ' ('
            . $db->quoteName('source') . ' VARCHAR(64) NOT NULL, '
            . $db->quoteName('old_comment_id') . ' BIGINT UNSIGNED NOT NULL, '
            . $db->quoteName('new_comment_id') . ' BIGINT UNSIGNED NOT NULL, '
            . $db->quoteName('article_id') . ' INT UNSIGNED NOT NULL, '
            . $db->quoteName('imported_at') . ' DATETIME NOT NULL, '
            . 'PRIMARY KEY (' . $db->quoteName('source') . ', ' . $db->quoteName('old_comment_id') . '), '
            . 'KEY ' . $db->quoteName('#__bbcomments_import_new_id') . ' (' . $db->quoteName('new_comment_id') . ')'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $db->setQuery($sql)->execute();
    }

    /** Assign the seven child extension records to this package, without touching config. */
    private function associateChildrenWithPackage(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $packageId = $this->getExtensionId('package', 'pkg_bbcomments');

        if ($packageId <= 0) {
            return;
        }

        $children = [$this->getExtensionId('component', 'com_bbcomments')];

        foreach (self::PLUGINS as $folder => [$type, $element]) {
            $children[] = $this->getExtensionId($type, $element, $folder);
        }

        foreach (array_filter($children) as $id) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('package_id') . ' = ' . (int) $packageId)
                ->where($db->quoteName('extension_id') . ' = ' . (int) $id);
            $db->setQuery($query)->execute();
        }
    }

    /** Enable only three essential plugins for brand-new sites, as in previous releases. */
    private function enableEssentialPlugins(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach (self::AUTO_ENABLE_ON_FRESH_INSTALL as $folder) {
            [$type, $element] = self::PLUGINS[$folder];
            $id = $this->getExtensionId($type, $element, $folder);

            if ($id <= 0) {
                continue;
            }

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('extension_id') . ' = ' . (int) $id);
            $db->setQuery($query)->execute();
        }
    }

    private function getExtensionId(string $type, string $element, string $folder = ''): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element));

        if ($type === 'plugin') {
            $query->where($db->quoteName('folder') . ' = ' . $db->quote($folder));
        }

        return (int) $db->setQuery($query)->loadResult();
    }

    /** Joomla's autoload map may still reflect the previous component/plugin versions. */
    private function refreshNamespaceMap(): void
    {
        if (!class_exists('JNamespacePsr4Map')) {
            return;
        }

        try {
            $cacheFile = JPATH_CACHE . '/autoload_psr4.php';

            if (function_exists('opcache_invalidate') && is_file($cacheFile)) {
                @opcache_invalidate($cacheFile, true);
            }

            $map = new \JNamespacePsr4Map();
            $map->create();
            $map->load();
        } catch (\Throwable $e) {
            $this->warn('Could not rebuild the Joomla namespace map', $e);
        }
    }

    /** Install/update Joomla core mail templates without resetting edited templates. */
    private function updateMailTemplates(): void
    {
        $path = JPATH_ADMINISTRATOR . '/components/com_bbcomments/src/Helper/TemplateEmails.php';

        if (function_exists('opcache_invalidate') && is_file($path)) {
            @opcache_invalidate($path, true);
        }

        if (!class_exists(TemplateEmails::class)) {
            if (!is_file($path) || !is_readable($path)) {
                $this->warn('Missing BlackBrix mail template helper');
                return;
            }
            require_once $path;
        }

        try {
            TemplateEmails::updateAllTemplates();
        } catch (\Throwable $e) {
            $this->warn('Mail template refresh failed', $e);
        }
    }

    /** Remove only precisely known obsolete files; protect lowercase live views on case-insensitive FS. */
    private function removeKnownObsoleteFiles(): void
    {
        $root = rtrim(JPATH_ROOT, '/\\');

        foreach (self::OBSOLETE_FILES as $relative) {
            $this->removeFile($root . '/' . $relative);
        }

        $old = JPATH_SITE . '/components/com_bbcomments/tmpl/Comments';
        $live = JPATH_SITE . '/components/com_bbcomments/tmpl/comments';

        if (!is_dir($old)) {
            return;
        }

        // On Windows/macOS case-insensitive filesystems these two paths can
        // resolve to the SAME directory. Never touch its active templates.
        $oldReal = realpath($old);
        $liveReal = realpath($live);

        if (!$oldReal || ($liveReal !== false && $oldReal === $liveReal)) {
            return;
        }

        foreach (self::OLD_SITE_TEMPLATES as $filename) {
            $this->removeFile($old . '/' . $filename);
        }

        // Only remove the directory if it is now empty. Do not delete user files.
        @rmdir($old);
    }

    private function removeFile(string $path): void
    {
        if (!is_file($path) || is_link($path)) {
            return;
        }

        if (function_exists('opcache_invalidate') && str_ends_with($path, '.php')) {
            @opcache_invalidate($path, true);
        }

        if (!@unlink($path)) {
            $this->warn('Obsolete file could not be removed: ' . basename($path));
        }
    }

    private function warn(string $message, ?\Throwable $error = null): void
    {
        try {
            Log::add(
                'BlackBrix Comments installer: ' . $message
                . ($error ? ' (' . $error->getMessage() . ')' : ''),
                Log::WARNING,
                'com_bbcomments'
            );
        } catch (\Throwable $ignored) {
            // A logging problem must not corrupt an otherwise successful update.
        }
    }
}
