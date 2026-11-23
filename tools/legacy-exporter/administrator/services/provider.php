<?php
/** Copyright (c) 2026 BlackBrix. GPL-3.0-or-later. */
defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('BlackBrix\\Component\\Bbengageexport'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('BlackBrix\\Component\\Bbengageexport'));
        $container->set(ComponentInterface::class, static function (Container $c) {
            $component = new MVCComponent($c->get(ComponentDispatcherFactoryInterface::class));
            $component->setMVCFactory($c->get(MVCFactoryInterface::class));
            return $component;
        });
    }
};
