<?php
/** BlackBrix Comments import view. Copyright (c) 2026 BlackBrix. GPLv3+. */
namespace BlackBrix\Component\Bbcomments\Administrator\View\Import;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use RuntimeException;

final class HtmlView extends BaseHtmlView
{
    public array $preview = [];

    public function display($tpl = null): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_bbcomments')) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $p = Factory::getApplication()->getSession()->get('bbcomments.migration', []);
        $this->preview = is_array($p) ? $p : [];
        ToolbarHelper::title(Text::_('COM_BBCOMMENTS_IMPORT_TITLE'), 'upload');
        parent::display($tpl);
    }
}
