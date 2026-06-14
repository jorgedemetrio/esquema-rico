<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Field;

\defined('_JEXEC') or die;

/**
 * Campo de mapeamento para datas.
 *
 * Compartilha o comportamento do campo padrão; no modo "valor fixo" o usuário
 * pode informar uma data (um seletor de calendário será adicionado depois).
 */
class MapDateField extends MapField
{
    protected $type = 'MapDate';
}
