<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Site\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Document\Renderer\Html\ModuleRenderer;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

trait ModuleRenderAware
{
	/**
	 * Renders a module position and returns the HTML content
	 *
	 * @param   string  $position  The position name, e.g. "position-1"
	 * @param   array   $attribs   The attributes to use for module rendering.
	 *
	 * @return  string  The rendered module position
	 *
	 * @throws  \Exception
	 * @see     ModuleRenderer::render()  To understand how $attribs works.
	 * @since   5.0.0
	 */
	public function loadPosition(string $position, array $attribs = []): string
	{
		$app = Factory::getApplication();

		if (!($app instanceof CMSApplication))
		{
			return '';
		}

		$document = $app->getDocument();

		if (!($document instanceof Document) || !method_exists($document, 'loadRenderer'))
		{
			return '';
		}

		try
		{
			$renderer = $document->loadRenderer('module');
		}
		catch (\Exception $exc)
		{
			return '';
		}

		$attribs = array_merge_recursive([
			'params' => [],
		], $attribs);

		$attribs['params'] = is_array($attribs['params']) ? json_encode($attribs['params']) : $attribs['params'];
		$contents          = '';

		foreach (ModuleHelper::getModules($position) as $mod)
		{
			$contents .= $renderer->render($mod, $attribs);
		}

		return $contents;
	}
}