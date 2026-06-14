<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core\Conditions\Conditions;

use Esquemarico\Core\Conditions\Condition;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Condição: o idioma ativo está entre os selecionados?
 */
final class Idioma extends Condition
{
    public function pass(): bool
    {
        $tag = Factory::getApplication()->getLanguage()->getTag();

        return $this->passByOperator([$tag]);
    }
}
