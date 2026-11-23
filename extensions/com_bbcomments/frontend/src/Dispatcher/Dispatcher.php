<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Site\Dispatcher;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Dispatcher\Dispatcher as AdminDispatcher;
use Joomla\CMS\Component\ComponentHelper;

class Dispatcher extends AdminDispatcher
{
	/**
	 * Keys of common media files to load.
	 *
	 * The prefixes of each string can be preset, style or script.
	 *
	 * @var   string[]
	 * @since 3.0.0
	 */
	protected $commonMediaKeys = ['preset:com_bbcomments.frontend'];

	protected function onBeforeDispatch(): void
	{
		$cParams = ComponentHelper::getParams('com_bbcomments');

		if ($cParams->get('loadCustomCss', 0) == 1)
		{
			$this->commonMediaKeys[] = 'style:com_bbcomments.comments';
		}

		parent::onBeforeDispatch();
	}

	protected function applyViewAndController(): void
	{
		parent::applyViewAndController();

		$controller = $this->input->get('controller', null);

		if (!empty($controller))
		{
			$this->input->set('view', null);
		}
	}


}