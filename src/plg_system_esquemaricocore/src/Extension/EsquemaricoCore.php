<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_system_esquemaricocore
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\System\EsquemaricoCore\Extension;

use Joomla\CMS\Plugin\CMSPlugin;

\defined('_JEXEC') or die;

/**
 * Plugin de biblioteca compartilhada.
 *
 * Não tem comportamento próprio: existe apenas para entregar e registrar o
 * namespace `Esquemarico\Core`. O registro efetivo é feito por autoload.php,
 * incluído pelo provider deste plugin e pelos demais artefatos da família.
 */
final class EsquemaricoCore extends CMSPlugin
{
}
