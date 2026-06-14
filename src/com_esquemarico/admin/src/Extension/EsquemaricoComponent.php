<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Extension;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Psr\Container\ContainerInterface;

\defined('_JEXEC') or die;

/**
 * Componente Esquema Rico.
 *
 * Ao inicializar (boot), garante o registro do namespace da biblioteca
 * compartilhada `Esquemarico\Core`, de modo que o motor JSON-LD e os
 * utilitários estejam disponíveis tanto no backend quanto no frontend.
 */
class EsquemaricoComponent extends MVCComponent implements BootableExtensionInterface
{
    public function boot(ContainerInterface $container): void
    {
        @include_once JPATH_PLUGINS . '/system/esquemaricocore/autoload.php';
    }
}
