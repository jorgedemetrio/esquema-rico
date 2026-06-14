<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Exception;

\defined('_JEXEC') or die;

/**
 * Lançada quando um plugin de integração do grupo "esquemarico" não é
 * encontrado ao tentar bootá-lo pelo alias.
 */
final class PluginNotFoundException extends \RuntimeException
{
}
