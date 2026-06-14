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
 * Campo de mapeamento para nomes de pessoa (autor/editor).
 *
 * Compartilha o comportamento do campo padrão; no modo "valor fixo" o usuário
 * pode informar um nome (um seletor de usuários será adicionado depois).
 */
class MapUserField extends MapField
{
    protected $type = 'MapUser';
}
