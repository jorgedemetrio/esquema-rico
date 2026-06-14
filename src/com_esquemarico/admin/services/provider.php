<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Component\Esquemarico\Administrator\Extension\EsquemaricoComponent;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        // Disponibiliza a biblioteca compartilhada o quanto antes.
        @include_once JPATH_PLUGINS . '/system/esquemaricocore/autoload.php';

        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\Esquemarico'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\Esquemarico'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new EsquemaricoComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );

                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
