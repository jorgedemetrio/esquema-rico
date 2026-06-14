<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Esquemarico\Administrator\Exception\PluginNotFoundException;

\defined('_JEXEC') or die;

/**
 * Inicializa (boota) um plugin de integração pelo seu alias, devolvendo a
 * instância pronta para uso. Útil no backend para consultar uma integração
 * específica (ex.: opções de mapeamento, views suportadas).
 */
final class Apps
{
    public static function getApp(string $name): object
    {
        $plugin = PluginHelper::getPlugin('esquemarico', $name);

        if (!$plugin) {
            throw new PluginNotFoundException(Text::sprintf('COM_ESQUEMARICO_PLUGIN_NOT_FOUND', $name));
        }

        return Factory::getApplication()->bootPlugin($plugin->name, $plugin->type);
    }
}
