<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\View\Emailtemplates;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public function display($tpl = null)
	{
		ToolbarHelper::title(sprintf(Text::_('COM_BBCOMMENTS_TITLE_EMAILTEMPLATES')), 'icon-bbcomments');
		ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_bbcomments&view=comments', false));

		parent::display($tpl);
	}

}