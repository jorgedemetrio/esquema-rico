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
 * Campo de mapeamento para imagens.
 *
 * Por ora compartilha o comportamento do campo de mapeamento padrão; um seletor
 * de mídia para o modo "valor fixo" será adicionado em uma próxima iteração.
 */
class MapImageField extends MapField
{
    protected $type = 'MapImage';
}
