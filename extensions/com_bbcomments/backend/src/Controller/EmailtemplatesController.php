<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\Controller;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Controller\Mixin\CustomACL;
use BlackBrix\Component\Bbcomments\Administrator\Helper\TemplateEmails;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\ControllerEventsTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class EmailtemplatesController extends BaseController
{
	use ControllerEventsTrait;

	public function updateEmails($cachable = false, $urlparams = [])
	{
		$this->checkToken('get');

		$returnURL = Route::_('index.php?option=com_bbcomments&view=Emailtemplates', false);
		$this->setRedirect($returnURL);

		$affected = TemplateEmails::updateAllTemplates();

		$message = ($affected > 0) ?
			Text::plural('COM_BBCOMMENTS_EMAILTEMPLATES_LBL_N_UPDATED', $affected) :
			Text::_('COM_BBCOMMENTS_EMAILTEMPLATES_ERR_NOUPDATE');

		$this->setMessage($message, ($affected > 0) ? 'success' : 'warning');
	}

	public function resetEmails($cachable = false, $urlparams = [])
	{
		$this->checkToken('get');

		$returnURL = Route::_('index.php?option=com_bbcomments&view=Emailtemplates', false);
		$this->setRedirect($returnURL);

		$affected = TemplateEmails::resetAllTemplates();

		$message = ($affected > 0) ?
			Text::plural('COM_BBCOMMENTS_EMAILTEMPLATES_LBL_N_RESET', $affected) :
			Text::_('COM_ADMINTOOLS_EMAILTEMPLATES_ERR_RESET');

		$this->setMessage($message, ($affected > 0) ? 'success' : 'error');
	}

}