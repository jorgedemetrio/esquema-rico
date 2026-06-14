<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Site\Helper;

\defined('_JEXEC') or die;

/**
 * Calcula o peso (priority 0.1–1.0) e a frequência (changefreq) de uma URL no
 * sitemap a partir da RECÊNCIA da data de modificação/criação.
 *
 * Regra de negócio: conteúdo alterado/criado recentemente é mais importante que
 * o antigo. Usamos um decaimento linear sobre uma janela (padrão 365 dias):
 *   - idade 0 (hoje)        -> prioridade máxima (1.0)
 *   - idade >= janela       -> prioridade mínima (0.1)
 *   - entre os dois         -> linear
 *
 * Classe pura (sem dependência do Joomla) — testável isoladamente.
 */
final class SitemapPriority
{
    public const DEFAULT_MAX    = 1.0;
    public const DEFAULT_MIN    = 0.1;
    public const DEFAULT_WINDOW = 365; // dias

    /**
     * Prioridade a partir da idade em dias.
     *
     * @param  array{max?: float, min?: float, window?: int}  $opts
     */
    public static function calculate(int $ageDays, array $opts = []): float
    {
        $max    = $opts['max'] ?? self::DEFAULT_MAX;
        $min    = $opts['min'] ?? self::DEFAULT_MIN;
        $window = max(1, (int) ($opts['window'] ?? self::DEFAULT_WINDOW));

        $ageDays = max(0, $ageDays);
        $factor  = min($ageDays / $window, 1.0);
        $value   = $max - ($max - $min) * $factor;

        return round(max($min, min($max, $value)), 1);
    }

    /**
     * Prioridade a partir das datas (usa a mais recente entre modificação e criação).
     *
     * @param  array{max?: float, min?: float, window?: int, default?: float}  $opts
     */
    public static function fromDates(?string $modified, ?string $created, int $now, array $opts = []): float
    {
        $ageDays = self::ageDays($modified, $created, $now);

        if ($ageDays === null) {
            return $opts['default'] ?? 0.5;
        }

        return self::calculate($ageDays, $opts);
    }

    /**
     * Frequência de alteração sugerida conforme a idade (em dias).
     */
    public static function changefreq(?int $ageDays): string
    {
        if ($ageDays === null) {
            return 'monthly';
        }

        return match (true) {
            $ageDays <= 1   => 'daily',
            $ageDays <= 7   => 'weekly',
            $ageDays <= 30  => 'weekly',
            $ageDays <= 180 => 'monthly',
            default         => 'yearly',
        };
    }

    /**
     * Idade em dias da data mais recente (modificação ou criação), ou null.
     */
    public static function ageDays(?string $modified, ?string $created, int $now): ?int
    {
        $ts = self::latestTimestamp([$modified, $created]);

        if ($ts === null) {
            return null;
        }

        return max(0, (int) floor(($now - $ts) / 86400));
    }

    /**
     * Maior timestamp válido dentre as datas SQL informadas (ou null).
     *
     * @param  array<int, ?string>  $dates
     */
    public static function latestTimestamp(array $dates): ?int
    {
        $best = null;

        foreach ($dates as $d) {
            if (!$d || $d === '0000-00-00 00:00:00') {
                continue;
            }

            $t = strtotime($d);

            if ($t !== false && ($best === null || $t > $best)) {
                $best = $t;
            }
        }

        return $best;
    }
}
