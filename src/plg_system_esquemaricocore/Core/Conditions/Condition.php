<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core\Conditions;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Classe-base de uma condição de publicação.
 *
 * Subclasses implementam pass(), comparando o CONTEXTO ATUAL da requisição com
 * a SELEÇÃO feita pelo usuário, segundo o operador (inclui / não inclui).
 */
abstract class Condition
{
    /**
     * Valores selecionados pelo usuário.
     *
     * @var array<int|string, mixed>
     */
    protected array $selection = [];

    /**
     * Operador: "includes" ou "not_includes".
     */
    protected string $operator = 'includes';

    /**
     * Parâmetros adicionais específicos da condição.
     */
    protected object $params;

    protected CMSApplicationInterface $app;

    public function __construct()
    {
        $this->app    = Factory::getApplication();
        $this->params = new \stdClass();
    }

    /**
     * @param  array<int|string, mixed>|string|null  $selection
     */
    public function setSelection(array|string|null $selection): static
    {
        if (\is_string($selection)) {
            $selection = $selection === '' ? [] : [$selection];
        }

        $this->selection = (array) ($selection ?? []);

        return $this;
    }

    public function setOperator(string $operator): static
    {
        $this->operator = $operator === 'not_includes' ? 'not_includes' : 'includes';

        return $this;
    }

    public function setParams(object|array|null $params): static
    {
        $this->params = (object) ($params ?? []);

        return $this;
    }

    /**
     * Avalia a condição. DEVE ser implementada pela subclasse.
     */
    abstract public function pass(): bool;

    /**
     * Compara os valores do contexto atual com a seleção, aplicando o operador.
     *
     * @param  array<int|string, mixed>  $currentValues
     */
    protected function passByOperator(array $currentValues): bool
    {
        $intersects = \count(array_intersect(
            array_map('strval', $currentValues),
            array_map('strval', $this->selection)
        )) > 0;

        return $this->operator === 'not_includes' ? !$intersects : $intersects;
    }
}
