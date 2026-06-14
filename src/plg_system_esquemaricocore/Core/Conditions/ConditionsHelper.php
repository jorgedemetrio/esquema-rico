<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core\Conditions;

\defined('_JEXEC') or die;

/**
 * Avalia conjuntos de condições de publicação.
 *
 * Estrutura esperada de um GRUPO de regras:
 *   [
 *     'matching_method' => 'all' | 'any',   // E lógico | OU lógico
 *     'rules' => [
 *       ['alias' => 'menu', 'operator' => 'includes', 'value' => [...], 'params' => {...}],
 *       ...
 *     ]
 *   ]
 *
 * Vários grupos são combinados com OU (passSets).
 */
final class ConditionsHelper
{
    /**
     * Mapa alias => classe da condição.
     *
     * @var array<string, class-string<Condition>>
     */
    private const MAP = [
        'menu'        => Conditions\Menu::class,
        'idioma'      => Conditions\Idioma::class,
        'language'    => Conditions\Idioma::class,
        'componente'  => Conditions\Componente::class,
        'dispositivo' => Conditions\Dispositivo::class,
        'grupousuario' => Conditions\GrupoUsuario::class,
        'nivelacesso' => Conditions\NivelAcesso::class,
    ];

    /**
     * Avalia múltiplos grupos. Passa se QUALQUER grupo passar (OU).
     *
     * @param  array<int, array>  $groups
     */
    public function passSets(array $groups): bool
    {
        if (empty($groups)) {
            return true;
        }

        foreach ($groups as $group) {
            $rules  = $group['rules'] ?? [];
            $method = $group['matching_method'] ?? 'all';

            if ($this->passSet($rules, $method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Avalia um único grupo de regras.
     *
     * @param  array<int, array>  $rules
     */
    public function passSet(array $rules, string $matchingMethod = 'all'): bool
    {
        if (empty($rules)) {
            return true;
        }

        $any = $matchingMethod === 'any';

        foreach ($rules as $rule) {
            $alias     = (string) ($rule['alias'] ?? '');
            $selection = $rule['value'] ?? ($rule['selection'] ?? []);

            // Regra sem seleção não bloqueia.
            if (empty($selection)) {
                continue;
            }

            $condition = $this->make($alias);

            if ($condition === null) {
                continue;
            }

            $condition
                ->setSelection($selection)
                ->setOperator((string) ($rule['operator'] ?? 'includes'))
                ->setParams($rule['params'] ?? []);

            $passed = $condition->pass();

            if ($any && $passed) {
                return true;
            }

            if (!$any && !$passed) {
                return false;
            }
        }

        // No modo "all" chegamos aqui sem falhas => passou.
        // No modo "any" chegamos aqui sem sucessos => não passou.
        return !$any;
    }

    /**
     * Instancia a condição pelo alias, ou null se desconhecida.
     */
    private function make(string $alias): ?Condition
    {
        $class = self::MAP[$alias] ?? null;

        if ($class === null || !class_exists($class)) {
            return null;
        }

        return new $class();
    }
}
