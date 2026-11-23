<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\Provider;

defined('_JEXEC') or die;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class ComponentParameters implements ServiceProviderInterface
{
	private string $defaultExtension;

	public function __construct(string $defaultExtension)
	{
		$this->defaultExtension = $defaultExtension;
	}

	public function register(Container $container)
	{
		$container->set(
			\BlackBrix\Component\Bbcomments\Administrator\Service\ComponentParameters::class,
			function (Container $container) {
				return new \BlackBrix\Component\Bbcomments\Administrator\Service\ComponentParameters(
					$container->get(\BlackBrix\Component\Bbcomments\Administrator\Service\CacheCleaner::class),
					$this->defaultExtension
				);
			}
		);
	}
}