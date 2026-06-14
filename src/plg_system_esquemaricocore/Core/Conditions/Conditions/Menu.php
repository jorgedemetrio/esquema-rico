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
 * Condição: o item de menu ativo está entre os selecionados?
 *
 * Parâmetro `include_children`: quando "1", também passa se o item ativo for
 * descendente de um item selecionado.
 */
final class Menu extends Condition
{
    public function pass(): bool
    {
        $menu = $this->app->getMenu();

        if ($menu === null) {
            return false;
        }

        $active = $menu->getActive();

        if ($active === null) {
            return $this->operator === 'not_includes';
        }

        $current = [$active->id];

        // Incluir ancestrais quando "incluir filhos" estiver ligado.
        if (!empty($this->params->include_children)) {
            $tree = $active->tree ?? [];

            if (\is_array($tree)) {
                $current = array_merge($current, $tree);
            }
        }

        return $this->passByOperator($current);
    }
}
