<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Seo;

\defined('_JEXEC') or die;

/**
 * Analisador de SEO de conteúdo (inspirado no Yoast SEO do WordPress).
 *
 * Recebe os dados do artigo e devolve uma pontuação (0–100) e uma lista de
 * verificações com status (good/ok/bad), usando regras de SEO conhecidas:
 * tamanho do título e da meta descrição, presença de keywords, volume de
 * conteúdo, palavra-chave de foco no título/descrição/URL/primeiro parágrafo/
 * subtítulos, densidade da palavra-chave, atributos alt em imagens, links e
 * subtítulos.
 *
 * Classe PURA (sem dependência do Joomla) — testável isoladamente. As mensagens
 * são devolvidas como CHAVES de idioma (traduzidas na camada de exibição).
 */
final class SeoAnalyzer
{
    private const WEIGHTS = [
        'good' => 1.0,
        'ok'   => 0.5,
        'bad'  => 0.0,
    ];

    /**
     * Analisa os dados do artigo.
     *
     * @param  array{title?: string, text?: string, metadesc?: string, metakey?: string, alias?: string, focus_keyword?: string}  $data
     *
     * @return array{score: int, rating: string, checks: array<int, array{id: string, status: string, key: string, params: array}>}
     */
    public function analyze(array $data): array
    {
        $title    = trim((string) ($data['title'] ?? ''));
        $html     = (string) ($data['text'] ?? '');
        $metadesc = trim((string) ($data['metadesc'] ?? ''));
        $metakey  = trim((string) ($data['metakey'] ?? ''));
        $alias    = trim((string) ($data['alias'] ?? ''));
        $fk       = trim((string) ($data['focus_keyword'] ?? ''));

        $plain = $this->plainText($html);
        $words = $this->wordCount($plain);

        $checks = [];
        $checks[] = $this->checkTitleLength($title);
        $checks[] = $this->checkMetadesc($metadesc);
        $checks[] = $this->checkMetakeywords($metakey);
        $checks[] = $this->checkContentLength($words);
        $checks[] = $this->checkImagesAlt($html);
        $checks[] = $this->checkLinks($html);
        $checks[] = $this->checkSubheadings($html);

        if ($fk === '') {
            $checks[] = ['id' => 'fk_set', 'status' => 'bad', 'key' => 'ESR_SEO_FK_SET_BAD', 'params' => []];
        } else {
            $checks[] = $this->checkBool('fk_in_title', $this->containsCI($title, $fk));
            $checks[] = $this->checkBool('fk_in_metadesc', $this->containsCI($metadesc, $fk));
            $checks[] = $this->checkBool('fk_in_url', $this->containsCI($alias, $this->slugify($fk)) || $this->containsCI($alias, $fk));
            $checks[] = $this->checkBool('fk_in_first_paragraph', $this->containsCI($this->firstWords($plain, 100), $fk));
            $checks[] = $this->checkDensity($plain, $words, $fk);
            $checks[] = $this->checkSubheadingFk($html, $fk);
        }

        return [
            'score'  => $this->score($checks),
            'rating' => $this->rating($this->score($checks)),
            'checks' => $checks,
        ];
    }

    /**
     * Pesos por verificação (algumas pesam mais).
     */
    private function weightOf(string $id): float
    {
        return match ($id) {
            'title_length', 'metadesc', 'content_length', 'fk_in_title', 'fk_density' => 2.0,
            default => 1.0,
        };
    }

    /**
     * @param  array<int, array{id: string, status: string}>  $checks
     */
    private function score(array $checks): int
    {
        $sum = 0.0;
        $tot = 0.0;

        foreach ($checks as $c) {
            $w    = $this->weightOf($c['id']);
            $sum += (self::WEIGHTS[$c['status']] ?? 0.0) * $w;
            $tot += $w;
        }

        return $tot > 0 ? (int) round($sum / $tot * 100) : 0;
    }

    private function rating(int $score): string
    {
        return match (true) {
            $score >= 70 => 'good',
            $score >= 45 => 'ok',
            default      => 'bad',
        };
    }

    /* ===================================================================
     *  Verificações
     * =================================================================== */

    private function checkTitleLength(string $title): array
    {
        $len    = $this->len($title);
        $status = match (true) {
            $len >= 40 && $len <= 60 => 'good',
            $len >= 30 && $len <= 70 => 'ok',
            default                  => 'bad',
        };

        return $this->mk('title_length', $status, ['len' => $len]);
    }

    private function checkMetadesc(string $metadesc): array
    {
        $len = $this->len($metadesc);

        if ($len === 0) {
            return $this->mk('metadesc', 'bad', ['len' => 0]);
        }

        $status = ($len >= 120 && $len <= 160) ? 'good' : 'ok';

        return $this->mk('metadesc', $status, ['len' => $len]);
    }

    private function checkMetakeywords(string $metakey): array
    {
        return $this->mk('metakeywords', $metakey === '' ? 'bad' : 'good', []);
    }

    private function checkContentLength(int $words): array
    {
        $status = match (true) {
            $words >= 600 => 'good',
            $words >= 300 => 'ok',
            default       => 'bad',
        };

        return $this->mk('content_length', $status, ['words' => $words]);
    }

    private function checkImagesAlt(string $html): array
    {
        preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
        $total = \count($imgs[0]);

        if ($total === 0) {
            return $this->mk('images_alt', 'ok', ['missing' => 0, 'total' => 0]);
        }

        $missing = 0;
        foreach ($imgs[0] as $img) {
            if (!preg_match('/\balt\s*=\s*("|\')\s*\S/i', $img)) {
                $missing++;
            }
        }

        return $this->mk('images_alt', $missing === 0 ? 'good' : 'bad', ['missing' => $missing, 'total' => $total]);
    }

    private function checkLinks(string $html): array
    {
        $n = preg_match_all('/<a\b[^>]*\bhref\s*=/i', $html);

        return $this->mk('links', $n > 0 ? 'good' : 'bad', ['n' => $n]);
    }

    private function checkSubheadings(string $html): array
    {
        $n = preg_match_all('/<h[2-6]\b/i', $html);

        return $this->mk('subheadings', $n > 0 ? 'good' : 'bad', ['n' => $n]);
    }

    private function checkDensity(string $plain, int $words, string $fk): array
    {
        if ($words === 0) {
            return $this->mk('fk_density', 'bad', ['density' => 0.0, 'occ' => 0]);
        }

        $occ     = $this->countCI($plain, $fk);
        $density = round($occ / $words * 100, 1);

        $status = match (true) {
            $density >= 0.5 && $density <= 2.5 => 'good',
            $density > 0 && $density <= 3.5    => 'ok',
            default                            => 'bad',
        };

        return $this->mk('fk_density', $status, ['density' => $density, 'occ' => $occ]);
    }

    private function checkSubheadingFk(string $html, string $fk): array
    {
        preg_match_all('/<h[2-6]\b[^>]*>(.*?)<\/h[2-6]>/is', $html, $m);
        $found = false;

        foreach ($m[1] ?? [] as $heading) {
            if ($this->containsCI(strip_tags($heading), $fk)) {
                $found = true;
                break;
            }
        }

        return $this->mk('fk_in_subheading', $found ? 'good' : 'ok', []);
    }

    private function checkBool(string $id, bool $ok): array
    {
        return $this->mk($id, $ok ? 'good' : 'bad', []);
    }

    /* ===================================================================
     *  Auxiliares
     * =================================================================== */

    private function mk(string $id, string $status, array $params): array
    {
        return [
            'id'     => $id,
            'status' => $status,
            'key'    => 'ESR_SEO_' . strtoupper($id) . '_' . strtoupper($status),
            'params' => $params,
        ];
    }

    private function plainText(string $html): string
    {
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html) ?? '';
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function wordCount(string $plain): int
    {
        if ($plain === '') {
            return 0;
        }

        return \count(preg_split('/\s+/u', $plain) ?: []);
    }

    private function firstWords(string $plain, int $n): string
    {
        $parts = preg_split('/\s+/u', $plain) ?: [];

        return implode(' ', \array_slice($parts, 0, $n));
    }

    private function len(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s) : \strlen($s);
    }

    private function containsCI(string $haystack, string $needle): bool
    {
        if ($haystack === '' || $needle === '') {
            return false;
        }

        return mb_stripos($haystack, $needle) !== false;
    }

    private function countCI(string $haystack, string $needle): int
    {
        if ($haystack === '' || $needle === '') {
            return 0;
        }

        return substr_count(mb_strtolower($haystack), mb_strtolower($needle));
    }

    private function slugify(string $s): string
    {
        $s = mb_strtolower(trim($s));

        return (string) preg_replace('/\s+/u', '-', $s);
    }
}
