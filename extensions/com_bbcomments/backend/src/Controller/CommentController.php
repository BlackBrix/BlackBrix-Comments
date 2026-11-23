<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\Controller;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Mixin\ControllerEventsTrait;
use Joomla\CMS\MVC\Controller\FormController;

class CommentController extends FormController
{
	use ControllerEventsTrait;

	/** @inheritdoc */
	protected $text_prefix = 'COM_BBCOMMENTS_COMMENT';

}