<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\Provider;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Service\CacheCleaner as CacheCleanerService;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * Cache Cleaner service provider
 *
 * @since  3.2.0
 */
class CacheCleaner implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->set(
			CacheCleanerService::class,
			function (Container $container) {
				$app = Factory::getApplication();

				return new CacheCleanerService(
					$app,
					$container->get(CacheControllerFactoryInterface::class)
				);
			}
		);
	}

}