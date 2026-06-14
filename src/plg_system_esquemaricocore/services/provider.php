<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_system_esquemaricocore
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
use Joomla\Plugin\System\EsquemaricoCore\Extension\EsquemaricoCore;

return new class () implements ServiceProviderInterface {
    /**
     * Registra o serviço do plugin no contêiner de DI.
     */
    public function register(Container $container): void
    {
        // Garante que a biblioteca compartilhada esteja registrada o quanto antes.
        @include_once __DIR__ . '/../autoload.php';

        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new EsquemaricoCore(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'esquemaricocore')
                );

                return $plugin;
            }
        );
    }
};
