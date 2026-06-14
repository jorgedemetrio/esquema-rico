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
 * Condição: o usuário atual tem algum dos níveis de acesso selecionados?
 */
final class NivelAcesso extends Condition
{
    public function pass(): bool
    {
        $user   = $this->app->getIdentity();
        $levels = $user ? $user->getAuthorisedViewLevels() : [];

        return $this->passByOperator($levels);
    }
}
