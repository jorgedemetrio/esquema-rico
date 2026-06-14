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
 * Monta o XML do protocolo Sitemaps 0.9 (urlset e sitemapindex).
 *
 * Classe pura (sem dependência do Joomla) — testável isoladamente.
 * https://www.sitemaps.org/protocol.html
 */
final class SitemapBuilder
{
    private const NS       = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    private const NS_IMAGE = 'http://www.google.com/schemas/sitemap-image/1.1';

    /**
     * Monta um <urlset> a partir das entradas.
     *
     * @param  array<int, array{loc: string, lastmod?: ?string, changefreq?: ?string, priority?: float|null, images?: string[]}>  $entries
     */
    public static function urlset(array $entries): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="' . self::NS . '" xmlns:image="' . self::NS_IMAGE . '">' . "\n";

        foreach ($entries as $e) {
            if (empty($e['loc'])) {
                continue;
            }

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . self::esc($e['loc']) . "</loc>\n";

            if (!empty($e['lastmod'])) {
                $xml .= '    <lastmod>' . self::esc($e['lastmod']) . "</lastmod>\n";
            }

            if (!empty($e['changefreq'])) {
                $xml .= '    <changefreq>' . self::esc($e['changefreq']) . "</changefreq>\n";
            }

            if (isset($e['priority']) && $e['priority'] !== null) {
                $xml .= '    <priority>' . number_format((float) $e['priority'], 1, '.', '') . "</priority>\n";
            }

            foreach ($e['images'] ?? [] as $img) {
                if ((string) $img === '') {
                    continue;
                }

                $xml .= '    <image:image><image:loc>' . self::esc((string) $img) . "</image:loc></image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }

    /**
     * Monta um <sitemapindex> a partir dos sitemaps.
     *
     * @param  array<int, array{loc: string, lastmod?: ?string}>  $sitemaps
     */
    public static function index(array $sitemaps): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="' . self::NS . '">' . "\n";

        foreach ($sitemaps as $s) {
            if (empty($s['loc'])) {
                continue;
            }

            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . self::esc($s['loc']) . "</loc>\n";

            if (!empty($s['lastmod'])) {
                $xml .= '    <lastmod>' . self::esc($s['lastmod']) . "</lastmod>\n";
            }

            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>' . "\n";

        return $xml;
    }

    /**
     * Escapa um valor para uso seguro em XML.
     */
    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
