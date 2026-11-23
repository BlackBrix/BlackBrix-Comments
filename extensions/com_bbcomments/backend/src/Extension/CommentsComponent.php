<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\Extension;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Service\CacheCleaner;
use BlackBrix\Component\Bbcomments\Administrator\Service\ComponentParameters;
use BlackBrix\Component\Bbcomments\Administrator\Service\Html\Comments;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Psr\Container\ContainerInterface;

class CommentsComponent extends MVCComponent implements BootableExtensionInterface
{
	use HTMLRegistryAwareTrait;

	/**
	 * The container we were created with
	 *
	 * @since 3.2.0
	 * @var   Container
	 */
	private $container;

	/** @inheritdoc */
	public function boot(ContainerInterface $container)
	{
		$this->container = $container;
		$db              = $container->get(DatabaseInterface::class);
		$this->getRegistry()->register('bbcomments', new Comments($db));
	}

	/**
	 * Get the Cache Cleaner service
	 *
	 * @return  CacheCleaner
	 *
	 * @since   3.2.0
	 */
	public function getCacheCleanerService(): CacheCleaner
	{
		return $this->container->get(CacheCleaner::class);
	}

	/**
	 * Get the Component Parameters service
	 *
	 * @return  ComponentParameters
	 *
	 * @since   3.2.0
	 */
	public function getComponentParametersService(): ComponentParameters
	{
		return $this->container->get(ComponentParameters::class);
	}

}