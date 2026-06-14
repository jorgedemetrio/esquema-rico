<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Utilitários gerais reutilizados por toda a família Esquema Rico.
 */
final class Functions
{
    /**
     * Procura qualquer uma das agulhas dentro do palheiro.
     *
     * @param  string[]  $needles
     */
    public static function strposArr(array $needles, ?string $haystack, bool $caseInsensitive = true): bool
    {
        if ($haystack === null || $haystack === '') {
            return false;
        }

        $fn = $caseInsensitive ? 'stripos' : 'strpos';

        foreach ($needles as $needle) {
            if ($needle !== '' && $fn($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converte qualquer valor em array (string com quebras de linha vira lista).
     */
    public static function makeArray(mixed $subject): array
    {
        if (\is_array($subject)) {
            return $subject;
        }

        if ($subject === null || $subject === '') {
            return [];
        }

        if (\is_string($subject) && str_contains($subject, "\n")) {
            return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $subject) ?: [])));
        }

        return [$subject];
    }

    /**
     * Insere $new dentro de $original preservando chaves, na posição $offset.
     *
     * Útil para inserir opções de mapeamento em um ponto específico da lista.
     */
    public static function arraySpliceAssoc(array $original, array $new, int $offset): array
    {
        $offset = max(0, min($offset, \count($original)));

        return array_slice($original, 0, $offset, true)
            + $new
            + array_slice($original, $offset, null, true);
    }

    /**
     * Estamos servindo um feed (RSS/Atom)?
     */
    public static function isFeed(): bool
    {
        $app = Factory::getApplication();

        if (method_exists($app, 'getDocument')) {
            $doc = $app->getDocument();

            if ($doc !== null && \in_array($doc->getType(), ['feed', 'opensearch'], true)) {
                return true;
            }
        }

        $format = $app->getInput()->get('format', 'html');

        return \in_array($format, ['feed', 'rss', 'atom'], true);
    }

    /**
     * Converte uma data (na zona do site) para UTC, no formato SQL.
     */
    public static function dateToUTC(?string $date): ?string
    {
        $date = $date !== null ? trim($date) : '';

        if ($date === '' || $date === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            $tz   = new \DateTimeZone(Factory::getApplication()->get('offset', 'UTC'));
            $dObj = new Date($date, $tz);

            return $dObj->toSql(true);
        } catch (\Exception) {
            return $date;
        }
    }

    /**
     * Transforma uma data para o formato ISO 8601 com o fuso do site,
     * exigido pelos schemas de data do Google.
     */
    public static function dateToISO8601(?string $date): ?string
    {
        $date = $date !== null ? trim($date) : '';

        if ($date === '' || $date === '0000-00-00 00:00:00') {
            return null;
        }

        // Já está em ISO 8601.
        if (str_contains($date, 'T')) {
            return $date;
        }

        try {
            $tz   = new \DateTimeZone(Factory::getApplication()->get('offset', 'UTC'));
            $dObj = new Date($date, $tz);

            return $dObj->toISO8601(true);
        } catch (\Exception) {
            return $date;
        }
    }

    /**
     * Colapsa espaços em branco repetidos em um único espaço.
     */
    public static function minify(?string $string): string
    {
        if ($string === null || $string === '') {
            return '';
        }

        return trim((string) preg_replace('/\s+/s', ' ', $string));
    }

    /**
     * Converte uma duração simples ("30") em ISO 8601 ("PT30M"),
     * deixando intactos valores já no formato ISO.
     */
    public static function toISO8601Duration(?string $value, string $unit = 'M'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, 'P')) {
            return $value;
        }

        return 'PT' . $value . $unit;
    }
}
