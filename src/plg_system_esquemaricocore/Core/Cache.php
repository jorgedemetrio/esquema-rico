<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core;

\defined('_JEXEC') or die;

/**
 * Cache em memória por requisição (memoização).
 *
 * Evita recomputar valores caros (leitura de XML, campos personalizados,
 * consultas a #__extensions) dentro de um mesmo request.
 */
final class Cache
{
    /**
     * Armazenamento interno chave => valor.
     *
     * @var array<string, mixed>
     */
    private static array $store = [];

    /**
     * Indica se uma chave existe no cache.
     */
    public static function has(string $hash): bool
    {
        return \array_key_exists($hash, self::$store);
    }

    /**
     * Retorna o valor de uma chave (ou $default se ausente).
     */
    public static function get(string $hash, mixed $default = null): mixed
    {
        return self::$store[$hash] ?? $default;
    }

    /**
     * Armazena um valor e o devolve (para encadeamento em returns).
     */
    public static function set(string $hash, mixed $data): mixed
    {
        self::$store[$hash] = $data;

        return $data;
    }

    /**
     * Memoização: executa o callback uma única vez por chave.
     *
     * @param  callable():mixed  $callback
     */
    public static function memo(string $hash, callable $callback): mixed
    {
        if (self::has($hash)) {
            return self::get($hash);
        }

        return self::set($hash, $callback());
    }

    /**
     * Remove uma chave (ou esvazia tudo se $hash for nulo).
     */
    public static function clear(?string $hash = null): void
    {
        if ($hash === null) {
            self::$store = [];

            return;
        }

        unset(self::$store[$hash]);
    }
}
