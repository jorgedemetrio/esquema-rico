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
 * Condição: o dispositivo atual (mobile/tablet/desktop) está entre os selecionados?
 *
 * Detecção simples baseada no User-Agent.
 */
final class Dispositivo extends Condition
{
    public function pass(): bool
    {
        return $this->passByOperator([$this->detectar()]);
    }

    private function detectar(): string
    {
        $ua = (string) ($this->app->getInput()->server->get('HTTP_USER_AGENT', '', 'string'));

        if ($ua === '') {
            return 'desktop';
        }

        if (preg_match('/(tablet|ipad|playbook|silk)/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|android|iphone|ipod|blackberry|iemobile|opera mini)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
