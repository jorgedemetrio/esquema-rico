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
 * Corrige a ausência da meta keywords nas páginas de artigo.
 *
 * Em vários templates/versões o Joomla deixou de emitir
 * `<meta name="keywords">` a partir das palavras-chave do artigo. Este plugin
 * garante a emissão, pegando as keywords da matéria (e, opcionalmente, suas
 * tags) e adicionando-as ao documento. O Google pode não usar, mas outros
 * buscadores ainda consideram.
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

        $keywords = $this->coletarKeywords($item);

        if ($keywords === '') {
            return;
        }

        $doc      = $app->getDocument();
        $existing = (string) $doc->getMetaData('keywords');

        // Não sobrescreve uma meta keywords já presente; complementa apenas se vazia.
        if (trim($existing) === '') {
            $doc->setMetaData('keywords', $keywords);
        }
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
