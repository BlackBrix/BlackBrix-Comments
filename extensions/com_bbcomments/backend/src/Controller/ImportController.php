<?php
/** BlackBrix Comments JSON import controller. Copyright (c) 2026 BlackBrix. GPLv3+. */
namespace BlackBrix\Component\Bbcomments\Administrator\Controller;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Helper\MigrationImport;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use RuntimeException;

final class ImportController extends BaseController
{
    private function requireAdmin(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_bbcomments')) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    public function display($cachable = false, $urlparams = []): self
    {
        $this->requireAdmin();
        $this->input->set('view', 'import');
        parent::display($cachable, $urlparams);
        return $this;
    }

    public function preview(): void
    {
        $this->requireAdmin();
        if (!Session::checkToken('post')) throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        $app = Factory::getApplication();
        $session = $app->getSession();
        try {
            $this->cleanup();
            $file = $this->input->files->get('import_file', null, 'raw');
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || !is_uploaded_file($file['tmp_name'] ?? '') || (int) ($file['size'] ?? 0) < 1
                || (int) $file['size'] > MigrationImport::MAX_BYTES) {
                throw new RuntimeException(Text::_('COM_BBCOMMENTS_MIGRATION_UPLOAD_INVALID'));
            }
            $folder = realpath(sys_get_temp_dir());
            $root = realpath(JPATH_ROOT);
            if (!$folder || ($root && ($folder === $root || str_starts_with($folder . '/', $root . '/')))) {
                throw new RuntimeException(Text::_('COM_BBCOMMENTS_MIGRATION_TEMP_DIR'));
            }
            $path = $folder . '/bbcomments-import-' . bin2hex(random_bytes(20)) . '.tmp';
            if (!move_uploaded_file($file['tmp_name'], $path)) {
                throw new RuntimeException(Text::_('COM_BBCOMMENTS_MIGRATION_TEMP_WRITE'));
            }
            @chmod($path, 0600);
            $json = file_get_contents($path);
            if (!is_string($json)) throw new RuntimeException(Text::_('COM_BBCOMMENTS_MIGRATION_UPLOAD_READ'));
            $stats = MigrationImport::inspect($json);
            $session->set('bbcomments.migration', [
                'path'=>$path, 'sha256'=>hash('sha256',$json), 'when'=>time(),
                'count'=>$stats['count'], 'articles'=>$stats['articles'],
                'unsubscribe_count'=>$stats['unsubscribe_count'],
                'changed_html'=>$stats['changed_html'], 'users_missing'=>$stats['users_missing'],
            ]);
            $app->enqueueMessage(Text::sprintf('COM_BBCOMMENTS_IMPORT_READY', $stats['count'], $stats['articles']), 'message');
        } catch (\Throwable $e) {
            if (isset($path) && is_file($path)) @unlink($path);
            $session->remove('bbcomments.migration');
            $app->enqueueMessage(Text::_('COM_BBCOMMENTS_IMPORT_ERROR') . ': ' . $e->getMessage(), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_bbcomments&view=import', false));
    }

    public function runImport(): void
    {
        $this->requireAdmin();
        if (!Session::checkToken('post')) throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        $app = Factory::getApplication();
        try {
            MigrationImport::assertEngageRemoved();
            $json = $this->loadPending();
            $count = MigrationImport::perform($json);
            $this->cleanup();
            $app->enqueueMessage(Text::sprintf('COM_BBCOMMENTS_IMPORT_SUCCESS', $count), 'message');
        } catch (\Throwable $e) {
            $app->enqueueMessage(Text::_('COM_BBCOMMENTS_IMPORT_ERROR') . ': ' . $e->getMessage(), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_bbcomments&view=import', false));
    }

    public function cancel(): void
    {
        $this->requireAdmin();
        if (!Session::checkToken('post')) throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        $this->cleanup();
        $this->setRedirect(Route::_('index.php?option=com_bbcomments&view=import', false));
    }

    private function loadPending(): string
    {
        $state = Factory::getApplication()->getSession()->get('bbcomments.migration', []);
        if (!is_array($state) || !isset($state['path'], $state['sha256'], $state['when']) || time() - $state['when'] > 3600) {
            throw new RuntimeException(Text::_('COM_BBCOMMENTS_MIGRATION_PREVIEW_EXPIRED'));
        }
        $path = $state['path'];
        $folder = realpath(sys_get_temp_dir());
        if (!is_string($path) || !$folder || dirname($path) !== $folder
            || !preg_match('/^bbcomments-import-[0-9a-f]{40}\.tmp$/', basename($path))
            || !is_file($path) || is_link($path)) {
            throw new RuntimeException(Text::_('COM_BBCOMMENTS_MIGRATION_TEMP_UNSAFE'));
        }
        $json = file_get_contents($path);
        if (!is_string($json) || strlen($json) > MigrationImport::MAX_BYTES
            || !hash_equals($state['sha256'], hash('sha256', $json))) {
            throw new RuntimeException(Text::_('COM_BBCOMMENTS_MIGRATION_TEMP_CHANGED'));
        }
        return $json;
    }

    private function cleanup(): void
    {
        $session = Factory::getApplication()->getSession();
        $state = $session->get('bbcomments.migration', []);
        $session->remove('bbcomments.migration');
        $path = is_array($state) ? ($state['path'] ?? '') : '';
        $folder = realpath(sys_get_temp_dir());
        if (is_string($path) && $folder && dirname($path) === $folder
            && preg_match('/^bbcomments-import-[0-9a-f]{40}\.tmp$/', basename($path))
            && is_file($path) && !is_link($path)) {
            @unlink($path);
        }
    }
}
