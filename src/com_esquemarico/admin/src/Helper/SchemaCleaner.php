<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\String\StringHelper;

\defined('_JEXEC') or die;

/**
 * Remove JSON-LD e microdados de tipos de schema gerados por templates ou
 * extensões de terceiros, evitando marcação duplicada. Opera sobre o buffer
 * do documento já montado (chamar no onAfterRender).
 *
 * Os blocos da própria extensão (data-type="esr") nunca são removidos.
 */
final class SchemaCleaner
{
    /**
     * @param  string|string[]  $schemaType
     */
    public static function remove(string|array $schemaType, bool $removeJson = true, bool $removeMicrodata = true): void
    {
        if (empty($schemaType)) {
            return;
        }

        $app   = Factory::getApplication();
        $body  = $app->getBody();
        $count = 0;
        $types = (array) $schemaType;

        if ($removeJson) {
            foreach ($types as $type) {
                $count += self::removeJsonSchema($body, strtolower((string) $type));
            }
        }

        if ($removeMicrodata) {
            foreach ($types as $type) {
                $count += self::removeMicrodata($body, strtolower((string) $type));
            }
        }

        if ($count > 0) {
            $app->setBody($body);
        }
    }

    /**
     * Remove scripts JSON-LD de um tipo específico (exceto os nossos).
     */
    private static function removeJsonSchema(string &$text, string $schemaType): int
    {
        if (StringHelper::strpos($text, '//schema.org/') === false) {
            return 0;
        }

        $re = '/<script[^>]*type="application\/ld\+json"[^>]*>([\s\S]*?)<\/script>/msi';

        if (!preg_match_all($re, $text, $matches, PREG_SET_ORDER)) {
            return 0;
        }

        $count = 0;

        foreach ($matches as $match) {
            // Não tocar nos blocos da própria extensão.
            if (str_contains($match[0], 'data-type="esr"')) {
                continue;
            }

            if ($schemaType !== '' && !preg_match('/"@type"\s*:\s*"' . preg_quote($schemaType, '/') . '"/si', $match[1])) {
                continue;
            }

            $text = str_replace($match[0], '', $text);
            $count++;
        }

        return $count;
    }

    /**
     * Remove microdados (itemscope/itemtype/itemprop) de um tipo específico.
     */
    private static function removeMicrodata(string &$text, string $schemaType): int
    {
        if (StringHelper::strpos($text, 'itemtype') === false) {
            return 0;
        }

        $patterns = ['/(itemscope)? itemtype=(\'|")?http(s?):\/\/(www.)?schema.org\/' . $schemaType . '(\'|")?/msi'];

        if ($schemaType === 'all') {
            $patterns = [
                '/(itemscope)? itemtype=(\'|")?http(s?):\/\/(.*?)schema.org\/(.*?(\'|"))(\'|")?/msi',
                '/<meta(.*?)(itemscope|itemprop)(.*?)\/?>/',
                '/itemprop=("|\')(.*?)("|\')/',
            ];
        }

        $extra = match ($schemaType) {
            'event' => ['/<meta itemprop="(url|startDate|addressRegion|postalCode|latitude|longitude|streetAddress|addressLocality)"[^>]+>/'],
            'article' => [
                '/itemprop="(url|name|author|headline|image|keywords|articleBody|datePublished)"/',
                '/<meta itemprop="(inLanguage|datePublished)"[^>]+>/',
            ],
            'product' => [
                '/<meta itemprop="(price|priceCurrency)"[^>]+>/',
                '/itemprop="(sku|description|offers|name)"/',
                '/<link itemprop="availability" href="http(s?):\/\/schema.org\/InStock" \/>/',
            ],
            'breadcrumblist' => [
                '/itemprop="(itemListElement|position|item)"/',
                '/itemscope itemtype="http(s?):\/\/schema.org\/ListItem"/',
            ],
            'aggregaterating' => [
                '/itemprop="(aggregateRating|ratingValue|bestRating)"/',
                '/<meta itemprop="(ratingCount|bestRating|worstRating)"[^>]+>/',
            ],
            default => [],
        };

        $patterns = array_merge($patterns, $extra);

        $text = (string) preg_replace($patterns, '', $text, -1, $count);

        return (int) $count;
    }
}
