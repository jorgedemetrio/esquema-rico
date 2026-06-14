<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core\SmartTags;

use Esquemarico\Core\SmartTags\Tags\DataHora;
use Esquemarico\Core\SmartTags\Tags\Pagina;
use Esquemarico\Core\SmartTags\Tags\Site;
use Esquemarico\Core\SmartTags\Tags\Usuario;

\defined('_JEXEC') or die;

/**
 * Coletor e substituidor de SmartTags.
 *
 * Uma SmartTag é uma variável no formato `{namespace.chave}` resolvida em tempo
 * de renderização. Esta classe agrega:
 *   - valores explícitos adicionados via add() (ex.: o payload do item);
 *   - valores dinâmicos resolvidos por classes de tag (usuário, página, site, data).
 */
class SmartTags
{
    /**
     * Coleção achatada de tag => valor.
     *
     * @var array<string, scalar|null>
     */
    private array $collection = [];

    /**
     * Resolvedores dinâmicos (avaliados sob demanda).
     *
     * @var array<string, object>
     */
    private array $dynamic = [];

    /**
     * @param  array{user?: int|null}  $options
     */
    public function __construct(array $options = [])
    {
        $userId = $options['user'] ?? null;

        $this->dynamic = [
            'user' => new Usuario(\is_numeric($userId) ? (int) $userId : null),
            'page' => new Pagina(),
            'site' => new Site(),
            'date' => new DataHora(),
        ];
    }

    /**
     * Adiciona pares chave => valor à coleção, opcionalmente com um prefixo.
     *
     * @param  array<string, mixed>  $data
     */
    public function add(array $data, string $prefix = ''): static
    {
        foreach ($data as $key => $value) {
            if (\is_scalar($value) || $value === null) {
                $this->collection[$prefix . $key] = $value;
            }
        }

        return $this;
    }

    /**
     * Substitui todas as SmartTags presentes em $data (recursivamente).
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function replace(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (\is_string($value) && str_contains($value, '{')) {
                $value = $this->replaceString($value);
            }
        });

        return $data;
    }

    /**
     * Substitui SmartTags em uma única string.
     */
    public function replaceString(string $subject): string
    {
        return (string) preg_replace_callback(
            '/\{([a-z0-9_]+(?:\.[a-z0-9_\.\-]+)*)(?::([^}]*))?\}/i',
            fn (array $m): string => $this->resolve($m[1], $m[2] ?? null),
            $subject
        );
    }

    /**
     * Resolve uma única tag para o seu valor (string).
     */
    private function resolve(string $tag, ?string $argument): string
    {
        // 1) Valor explícito na coleção.
        if (\array_key_exists($tag, $this->collection)) {
            return (string) ($this->collection[$tag] ?? '');
        }

        // 2) Resolvedor dinâmico por namespace (ex.: user.name, page.title).
        $parts     = explode('.', $tag, 2);
        $namespace = $parts[0];
        $key       = $parts[1] ?? '';

        if (isset($this->dynamic[$namespace])) {
            $value = $this->dynamic[$namespace]->get($key !== '' ? $key : $namespace, $argument);

            if ($value !== null) {
                return (string) $value;
            }
        }

        // 3) Tag desconhecida: remove (string vazia).
        return '';
    }
}
