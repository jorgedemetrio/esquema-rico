<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Site\Helper\SitemapBuilder;
use Joomla\Component\Esquemarico\Site\Helper\SitemapPriority;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Gera os sitemaps XML (conteúdo, categorias, menu, tags) e o índice.
 *
 * Cada URL recebe um peso (priority) e uma frequência (changefreq) calculados
 * pela recência da data de modificação/criação (ver SitemapPriority).
 */
class SitemapModel extends BaseDatabaseModel
{
    private const ORDER_DESC = ' DESC';

    private int $now;

    /** @var array{max: float, min: float, window: int} */
    private array $opts;

    /** @var int[] */
    private array $levels;

    /** @var string[] */
    private array $langs;

    private function init(): void
    {
        if (isset($this->now)) {
            return;
        }

        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        $this->now    = Factory::getDate('now')->getTimestamp();
        $this->levels = $user ? $user->getAuthorisedViewLevels() : [1];
        $this->langs  = ['*', $app->getLanguage()->getTag()];

        $params = EsquemaRicoHelper::getParams();
        $this->opts = [
            'max'    => (float) $params->get('sitemap_priority_max', SitemapPriority::DEFAULT_MAX),
            'min'    => (float) $params->get('sitemap_priority_min', SitemapPriority::DEFAULT_MIN),
            'window' => (int) $params->get('sitemap_priority_window', SitemapPriority::DEFAULT_WINDOW),
        ];
    }

    /* ===================================================================
     *  Sitemaps
     * =================================================================== */

    /**
     * Índice que aponta para os sub-sitemaps.
     */
    public function buildIndex(): string
    {
        $this->init();
        $lastmod = date('c', $this->now);
        $sitemaps = [];

        foreach (['content', 'categories', 'menu', 'tags'] as $type) {
            $sitemaps[] = [
                'loc'     => $this->absolute('index.php?option=com_esquemarico&view=sitemap&type=' . $type . '&format=xml'),
                'lastmod' => $lastmod,
            ];
        }

        return SitemapBuilder::index($sitemaps);
    }

    /**
     * Sitemap dos artigos (com_content).
     */
    public function buildContent(): string
    {
        $this->init();
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['c.id', 'c.alias', 'c.catid', 'c.created', 'c.modified', 'c.language', 'c.images'])
            ->from($db->quoteName('#__content', 'c'))
            ->where($db->quoteName('c.state') . ' = 1')
            ->whereIn($db->quoteName('c.access'), $this->levels)
            ->whereIn($db->quoteName('c.language'), $this->langs, ParameterType::STRING)
            ->order($db->quoteName('c.modified') . self::ORDER_DESC);

        $entries = [];

        foreach ($this->loadRows($query) as $row) {
            $url   = 'index.php?option=com_content&view=article&id=' . (int) $row->id . ':' . $row->alias . '&catid=' . (int) $row->catid;
            $entry = $this->entry($url, $row->modified, $row->created);

            if ($images = $this->articleImages($row->images ?? null)) {
                $entry['images'] = $images;
            }

            $entries[] = $entry;
        }

        return SitemapBuilder::urlset($entries);
    }

    /**
     * Extrai as imagens (intro e fulltext) de um artigo como URLs absolutas,
     * para o sitemap de imagens. Lê o campo JSON "images" do com_content.
     *
     * @return string[]
     */
    private function articleImages(?string $imagesJson): array
    {
        if (!$imagesJson) {
            return [];
        }

        $data = json_decode($imagesJson, true);

        if (!\is_array($data)) {
            return [];
        }

        $images = [];

        foreach (['image_intro', 'image_fulltext'] as $key) {
            $src = trim((string) ($data[$key] ?? ''));

            if ($src !== '') {
                // Chaveado pela origem para deduplicar intro == fulltext.
                $images[$src] = (string) EsquemaRicoHelper::cleanImage($src);
            }
        }

        return array_values(array_filter($images));
    }

    /**
     * Sitemap das categorias de conteúdo.
     */
    public function buildCategories(): string
    {
        $this->init();
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['c.id', 'c.alias', 'c.created_time', 'c.modified_time', 'c.language'])
            ->from($db->quoteName('#__categories', 'c'))
            ->where($db->quoteName('c.extension') . ' = ' . $db->quote('com_content'))
            ->where($db->quoteName('c.published') . ' = 1')
            ->where($db->quoteName('c.id') . ' <> 1')
            ->whereIn($db->quoteName('c.access'), $this->levels)
            ->whereIn($db->quoteName('c.language'), $this->langs, ParameterType::STRING)
            ->order($db->quoteName('c.modified_time') . self::ORDER_DESC);

        $entries = [];

        foreach ($this->loadRows($query) as $row) {
            $url = 'index.php?option=com_content&view=category&id=' . (int) $row->id . ':' . $row->alias;
            $entries[] = $this->entry($url, $row->modified_time, $row->created_time);
        }

        return SitemapBuilder::urlset($entries);
    }

    /**
     * Sitemap dos itens de menu (site).
     */
    public function buildMenu(): string
    {
        $this->init();
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['m.id', 'm.link', 'm.type', 'm.home', 'm.publish_up', 'm.language'])
            ->from($db->quoteName('#__menu', 'm'))
            ->where($db->quoteName('m.published') . ' = 1')
            ->where($db->quoteName('m.client_id') . ' = 0')
            ->whereIn($db->quoteName('m.access'), $this->levels)
            ->whereIn($db->quoteName('m.type'), ['component', 'url', 'alias'], ParameterType::STRING)
            ->whereIn($db->quoteName('m.language'), $this->langs, ParameterType::STRING)
            ->order($db->quoteName('m.lft') . ' ASC');

        $entries = [];

        foreach ($this->loadRows($query) as $row) {
            // A home aponta para a raiz e recebe a prioridade máxima.
            if ((int) $row->home === 1) {
                $entries[] = [
                    'loc'        => Uri::root(),
                    'lastmod'    => null,
                    'changefreq' => 'daily',
                    'priority'   => $this->opts['max'],
                ];

                continue;
            }

            // Itens de menu não têm data de modificação: usamos publish_up.
            $entries[] = $this->entry(
                'index.php?Itemid=' . (int) $row->id,
                $row->publish_up,
                null,
                ['default' => 0.7]
            );
        }

        return SitemapBuilder::urlset($entries);
    }

    /**
     * Sitemap das tags (com_tags).
     */
    public function buildTags(): string
    {
        $this->init();
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['t.id', 't.alias', 't.created_time', 't.modified_time', 't.language'])
            ->from($db->quoteName('#__tags', 't'))
            ->where($db->quoteName('t.published') . ' = 1')
            ->where($db->quoteName('t.parent_id') . ' <> 0')
            ->whereIn($db->quoteName('t.access'), $this->levels)
            ->whereIn($db->quoteName('t.language'), $this->langs, ParameterType::STRING)
            ->order($db->quoteName('t.modified_time') . self::ORDER_DESC);

        $entries = [];

        foreach ($this->loadRows($query) as $row) {
            $url = 'index.php?option=com_tags&view=tag&id[0]=' . (int) $row->id . '&alias[0]=' . $row->alias;
            $entries[] = $this->entry($url, $row->modified_time, $row->created_time);
        }

        return SitemapBuilder::urlset($entries);
    }

    /* ===================================================================
     *  Auxiliares
     * =================================================================== */

    /**
     * Monta uma entrada de URL com peso e frequência por recência.
     *
     * @param  array{default?: float}  $opts
     * @return array{loc: string, lastmod: ?string, changefreq: string, priority: float}
     */
    private function entry(string $internalUrl, ?string $modified, ?string $created, array $opts = []): array
    {
        $opts    = array_merge($this->opts, $opts);
        $age     = SitemapPriority::ageDays($modified, $created, $this->now);
        $ts      = SitemapPriority::latestTimestamp([$modified, $created]);

        return [
            'loc'        => $this->absolute($internalUrl),
            'lastmod'    => $ts !== null ? date('c', $ts) : null,
            'changefreq' => SitemapPriority::changefreq($age),
            'priority'   => SitemapPriority::fromDates($modified, $created, $this->now, $opts),
        ];
    }

    /**
     * URL absoluta e roteada (SEF quando ativo) a partir de uma URL interna.
     */
    private function absolute(string $internalUrl): string
    {
        return Route::link('site', $internalUrl, true, Route::TLS_IGNORE, true);
    }

    /**
     * Executa a query com tratamento de erro (tabela ausente => lista vazia).
     *
     * @return array<int, object>
     */
    private function loadRows($query): array
    {
        try {
            $this->getDatabase()->setQuery($query);

            return $this->getDatabase()->loadObjectList() ?: [];
        } catch (\Throwable) {
            return [];
        }
    }
}
