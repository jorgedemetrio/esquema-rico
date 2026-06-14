<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_content_esquemaricokeywords
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Content\Esquemaricokeywords\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * Completa metadados ausentes nas páginas de artigo: meta keywords e meta
 * description.
 *
 * Em vários templates/versões o Joomla deixou de emitir
 * `<meta name="keywords">` a partir das palavras-chave do artigo; este plugin
 * garante a emissão (metakey + tags). Além disso, quando o artigo não tem
 * descrição, gera uma a partir do início do conteúdo — o que também alimenta o
 * og:description. Nunca sobrescreve metadados já presentes.
 */
final class EsquemaricoKeywords extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'aoPrepararConteudo'];
    }

    public function aoPrepararConteudo($event): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $context = method_exists($event, 'getContext') ? $event->getContext() : $event->getArgument('context');
        $item    = method_exists($event, 'getItem') ? $event->getItem() : $event->getArgument('subject');

        // Apenas na página de um único artigo.
        if ($context !== 'com_content.article' || $app->getInput()->get('view') !== 'article') {
            return;
        }

        if (!\is_object($item)) {
            return;
        }

        $doc = $app->getDocument();

        // Meta keywords: completa quando ausente (não sobrescreve).
        $keywords = $this->coletarKeywords($item);

        if ($keywords !== '' && trim((string) $doc->getMetaData('keywords')) === '') {
            $doc->setMetaData('keywords', $keywords);
        }

        // Meta description: gera a partir do conteúdo quando ausente.
        $this->preencherDescricao($doc, $item);
    }

    /**
     * Gera a meta description a partir do conteúdo do artigo quando não há uma
     * (do artigo ou do template). Também alimenta o og:description.
     */
    private function preencherDescricao(object $doc, object $item): void
    {
        if (!$this->params->get('auto_description', 1) || trim((string) $doc->getDescription()) !== '') {
            return;
        }

        $texto = trim((string) ($item->introtext ?? '') . ' ' . (string) ($item->fulltext ?? ''));

        if ($texto === '') {
            $texto = (string) ($item->text ?? '');
        }

        $desc = $this->resumir($texto, 160);

        if ($desc !== '') {
            $doc->setDescription($desc);
        }
    }

    /**
     * Resume um trecho de HTML em texto plano, truncado em ~$max caracteres
     * num limite de palavra.
     */
    private function resumir(string $html, int $max): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $sp  = mb_strrpos($cut, ' ');

        if ($sp !== false && $sp > 0) {
            $cut = mb_substr($cut, 0, $sp);
        }

        return rtrim($cut, " ,.;:-") . '…';
    }

    /**
     * Reúne as palavras-chave do artigo (metakey) e, se habilitado, as tags.
     */
    private function coletarKeywords(object $item): string
    {
        $termos = [];

        if (!empty($item->metakey)) {
            $termos = array_merge($termos, $this->dividir((string) $item->metakey));
        }

        if ($this->params->get('include_tags', 1) && !empty($item->id)) {
            $termos = array_merge($termos, $this->tagsDoArtigo((int) $item->id));
        }

        // Remove vazios e duplicados (case-insensitive), preservando a ordem.
        $vistos = [];
        $saida  = [];

        foreach ($termos as $t) {
            $t   = trim($t);
            $key = mb_strtolower($t);

            if ($t === '' || isset($vistos[$key])) {
                continue;
            }

            $vistos[$key] = true;
            $saida[]      = $t;
        }

        return implode(', ', $saida);
    }

    /**
     * @return string[]
     */
    private function dividir(string $csv): array
    {
        return array_filter(array_map('trim', explode(',', $csv)));
    }

    /**
     * Títulos das tags atribuídas ao artigo.
     *
     * @return string[]
     */
    private function tagsDoArtigo(int $articleId): array
    {
        try {
            $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('t.title'))
                ->from($db->quoteName('#__contentitem_tag_map', 'm'))
                ->join('INNER', $db->quoteName('#__tags', 't') . ' ON t.id = m.tag_id')
                ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
                ->where($db->quoteName('m.content_item_id') . ' = :id')
                ->where($db->quoteName('t.published') . ' = 1')
                ->bind(':id', $articleId, ParameterType::INTEGER);

            $db->setQuery($query);

            return $db->loadColumn() ?: [];
        } catch (\Throwable) {
            return [];
        }
    }
}
