<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_system_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Esquemarico\Extension\Esquemarico;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        // Disponibiliza a biblioteca compartilhada.
        @include_once JPATH_PLUGINS . '/system/esquemaricocore/autoload.php';

        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Esquemarico(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'esquemarico')
                );
                $plugin->setApplication(\Joomla\CMS\Factory::getApplication());

                return $plugin;
            }
        );
    }
};
