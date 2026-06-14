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

\defined('_JEXEC') or die;

/**
 * Condição: o componente ativo (option) está entre os selecionados?
 */
final class Componente extends Condition
{
    public function pass(): bool
    {
        $option = (string) $this->app->getInput()->get('option');

        return $this->passByOperator([$option]);
    }
}
