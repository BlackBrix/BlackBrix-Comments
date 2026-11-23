<?php
/** Copyright (c) 2026 BlackBrix. GPL-3.0-or-later. */
namespace BlackBrix\Component\Bbengageexport\Administrator\View\Export;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use RuntimeException;

final class HtmlView extends BaseHtmlView
{
    public int $count = 0;
    public string $error = '';
    public function display($tpl = null): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.admin')) throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        try {
            $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            if (!in_array($db->getPrefix() . 'engage_comments', $db->getTableList(), true)) {
                throw new RuntimeException('Table #__engage_comments not found.');
            }
            $q = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__engage_comments'));
            $this->count = (int)$db->setQuery($q)->loadResult();
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
        ToolbarHelper::title(Text::_('COM_BBENGAGEEXPORT'), 'download');
        parent::display($tpl);
    }
}
