<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_system_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\System\Esquemarico\Extension;

use Esquemarico\Core\Extension as ExtensionHelper;
use Esquemarico\Core\Functions;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Engine\GeradorJsonLd;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Helper\SchemaCleaner;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Plugin de sistema do Esquema Rico.
 *
 * Responsável por gerar os esquemas globais, orquestrar as integrações e
 * injetar o JSON-LD resultante na página, além de remover marcação duplicada.
 */
final class Esquemarico extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    /**
     * Motor de geração de JSON-LD.
     */
    private ?GeradorJsonLd $gerador = null;

    /**
     * Configurações globais (linha "config").
     */
    private ?Registry $globais = null;

    /**
     * Indica que a inicialização ocorreu com sucesso.
     */
    private bool $iniciado = false;

    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => 'aoCompilarCabecalho',
            'onAfterRender'       => 'aposRenderizar',
        ];
    }

    /**
     * Injeta o JSON-LD no <head> (modo padrão).
     */
    public function aoCompilarCabecalho(): void
    {
        if (!$this->iniciar()) {
            return;
        }

        if (!$this->params->get('wait_page_render', 0) && $markup = $this->montarMarkup()) {
            Factory::getApplication()->getDocument()->addCustomTag($markup);
        }

        $this->aplicarTemplateTitulo();
        $this->metaCanonicalERobos();
        $this->controleSnippetRobos();
        $this->metaOpenGraph();
    }

    /**
     * Remove duplicados, faz injeção tardia (se configurada) e exibe o debug.
     */
    public function aposRenderizar(): void
    {
        if (!$this->iniciar()) {
            return;
        }

        $this->removerMicrodados();

        if ($this->params->get('wait_page_render', 0) && $markup = $this->montarMarkup()) {
            $app    = Factory::getApplication();
            $buffer = $app->getBody();

            if (str_contains($buffer, '</body>')) {
                $buffer = str_replace('</body>', $markup . '</body>', $buffer);
            } else {
                $buffer .= $markup;
            }

            $app->setBody($buffer);
        }

        // Painel de depuração para administradores.
        $user = Factory::getApplication()->getIdentity();

        if ($this->params->get('debug', 0) && $user && $user->authorise('core.admin')) {
            echo '<pre data-esr-debug="1" style="direction:ltr;">'
                . htmlspecialchars(print_r(EsquemaRicoHelper::$log, true), ENT_QUOTES, 'UTF-8')
                . '</pre>';
        }
    }

    /**
     * Monta o markup completo (esquemas globais + integrações).
     */
    private function montarMarkup(): ?string
    {
        $blocos = [
            $this->jsonWebsite(),
            $this->jsonLogo(),
            $this->jsonPerfisSociais(),
            $this->jsonNegocioLocal(),
            $this->codigoPersonalizado(),
            $this->jsonBreadcrumbs(),
        ];

        // Plugins de integração contribuem com seus blocos.
        EsquemaRicoHelper::event('onEsquemaRicoBeforeRender', [&$blocos]);

        $blocos = array_filter($blocos);

        if ($this->globais->get('combine_graph', 0)) {
            $blocos = $this->combinarGraph($blocos);
        }

        $markup = implode("\n", $blocos);

        if (trim($markup) === '') {
            return null;
        }

        if ($this->params->get('minifyjson', 0)) {
            $markup = Functions::minify($markup);
        }

        return "\n<!-- Início: Esquema Rico -->\n" . $markup . "\n<!-- Fim: Esquema Rico -->\n";
    }

    /**
     * Consolida os blocos JSON-LD da extensão (data-type="esr") num único
     * <script> com @graph, deixando intactos os blocos que não são JSON
     * válido (ex.: código personalizado). Só consolida com 2+ nós.
     *
     * @param  string[]  $blocos
     * @return string[]
     */
    private function combinarGraph(array $blocos): array
    {
        $nodes  = [];
        $outros = [];

        foreach ($blocos as $bloco) {
            if (
                !str_contains($bloco, 'data-type="esr"')
                || !preg_match('#<script[^>]*>(.*?)</script>#is', $bloco, $m)
            ) {
                $outros[] = $bloco;

                continue;
            }

            $json = json_decode(trim($m[1]), true);

            if (!\is_array($json) || !isset($json['@type'])) {
                $outros[] = $bloco;

                continue;
            }

            unset($json['@context']);
            $nodes[] = $json;
        }

        if (\count($nodes) < 2) {
            return $blocos;
        }

        $graph = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $nodes],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        if ($graph === false) {
            return $blocos;
        }

        $combinado = "\n" . '<script type="application/ld+json" data-type="esr">' . "\n" . $graph . "\n" . '</script>';

        return array_merge([$combinado], $outros);
    }

    /* ===================================================================
     *  Esquemas globais
     * =================================================================== */

    private function jsonWebsite(): ?string
    {
        if (!EsquemaRicoHelper::isFrontPage()) {
            return null;
        }

        $search   = $this->globais->get('sitelinks_enabled', 0);
        $siteName = (bool) $this->globais->get('sitename_enabled', 1);

        if (!$search && !$siteName) {
            return null;
        }

        if ($search) {
            $search = match ((string) $search) {
                '1'     => EsquemaRicoHelper::route('index.php?option=com_search&searchphrase=all&searchword={search_term}'),
                '2'     => EsquemaRicoHelper::route('index.php?option=com_finder&view=search&q={search_term}'),
                '3'     => trim((string) $this->globais->get('sitelinks_search_custom_url')),
                default => null,
            };
        }

        return $this->gerador->setData([
            'contentType'       => 'website',
            'site_name_enabled' => $siteName,
            'site_name'         => EsquemaRicoHelper::getSiteName(),
            'site_name_alt'     => $this->globais->get('sitename_name_alt'),
            'site_url'          => EsquemaRicoHelper::getSiteURL(),
            'site_links_search' => $search,
        ])->generate();
    }

    private function jsonLogo(): ?string
    {
        if (!EsquemaRicoHelper::isFrontPage() || !$logo = EsquemaRicoHelper::getSiteLogo()) {
            return null;
        }

        return $this->gerador->setData([
            'contentType' => 'logo',
            'url'         => EsquemaRicoHelper::getSiteURL(),
            'logo'        => EsquemaRicoHelper::cleanImage($logo),
        ])->generate();
    }

    private function jsonPerfisSociais(): ?string
    {
        if (!EsquemaRicoHelper::isFrontPage()) {
            return null;
        }

        $predefinidos = [
            $this->globais->get('socialprofiles_facebook'),
            $this->globais->get('socialprofiles_twitter'),
            $this->globais->get('socialprofiles_instagram'),
            $this->globais->get('socialprofiles_youtube'),
            $this->globais->get('socialprofiles_linkedin'),
            $this->globais->get('socialprofiles_pinterest'),
        ];

        $outros = explode("\n", (string) $this->globais->get('socialprofiles_other', ''));
        $urls   = array_filter(array_merge($predefinidos, $outros));
        $urls   = array_map(static fn ($u): string => str_replace([' ', "\n", "\t", "\r"], '', (string) $u), $urls);

        if (\count($urls) === 0) {
            return null;
        }

        return $this->gerador->setData([
            'contentType' => 'socialprofiles',
            'type'        => $this->globais->get('socialprofiles_type', 'Organization'),
            'siteurl'     => EsquemaRicoHelper::getSiteURL(),
            'sitename'    => EsquemaRicoHelper::getSiteName(),
            'links'       => $urls,
        ])->generate();
    }

    private function jsonNegocioLocal(): ?string
    {
        if (!$this->globais->get('businesslisting_enabled', 0) || !EsquemaRicoHelper::isFrontPage()) {
            return null;
        }

        $coords = explode(',', (string) $this->globais->get('businesslisting_latlng', ''));

        return $this->gerador->setData([
            'contentType'     => 'localbusiness',
            'id'              => EsquemaRicoHelper::getSiteURL(),
            'name'            => EsquemaRicoHelper::getSiteName(),
            'image'           => EsquemaRicoHelper::getSiteLogo(),
            'type'            => $this->globais->get('businesslisting_type', 'LocalBusiness'),
            'priceRange'      => $this->globais->get('businesslisting_price_range'),
            'streetAddress'   => $this->globais->get('businesslisting_street_address'),
            'addressLocality' => $this->globais->get('businesslisting_address_locality'),
            'addressRegion'   => $this->globais->get('businesslisting_address_region'),
            'postalCode'      => $this->globais->get('businesslisting_postal_code'),
            'addressCountry'  => $this->globais->get('businesslisting_address_country'),
            'telephone'       => $this->globais->get('businesslisting_telephone'),
            'geo'             => \count($coords) === 2 ? array_map('trim', $coords) : null,
        ])->generate();
    }

    private function jsonBreadcrumbs(): ?string
    {
        if (!$this->globais->get('breadcrumbs_enabled', 1)) {
            return null;
        }

        $home = (string) $this->globais->get('breadcrumbs_home', Text::_('COM_ESQUEMARICO_BREADCRUMBS_HOME'));

        return $this->gerador->setData([
            'contentType' => 'breadcrumbs',
            'crumbs'      => EsquemaRicoHelper::getCrumbs($home, (bool) $this->globais->get('include_home', 1)),
        ])->generate();
    }

    private function codigoPersonalizado(): ?string
    {
        $code = trim((string) $this->globais->get('customcode'));

        return $code !== '' ? $code : null;
    }

    /* ===================================================================
     *  Pós-processamento
     * =================================================================== */

    private function removerMicrodados(): void
    {
        $default = [(object) ['name' => 'BreadcrumbList', 'enabled' => 1]];
        $tipos   = [];

        foreach ((array) $this->globais->get('removemicrodata', $default) as $item) {
            $item = (object) $item;

            if (!empty($item->enabled) && !empty($item->name)) {
                $tipos[] = preg_replace('/[^A-Za-z0-9]/', '', (string) $item->name);
            }
        }

        if ($tipos = array_filter($tipos)) {
            SchemaCleaner::remove($tipos);
        }
    }

    private function controleSnippetRobos(): void
    {
        $doc    = Factory::getApplication()->getDocument();
        $robots = (string) $doc->getMetaData('robots');

        if (Functions::strposArr(['noindex', 'nosnippet', 'max-'], $robots)) {
            return;
        }

        $value  = 'max-snippet:-1, max-image-preview:large, max-video-preview:-1';
        $robots = $robots === '' ? $value : $robots . ', ' . $value;

        $doc->setMetaData('robots', $robots);
    }

    /* ===================================================================
     *  Template de título (<title>)
     * =================================================================== */

    /**
     * Reescreve o <title> a partir de um template configurável, com variáveis
     * %title%, %sitename% e %sep%. A home pode ter um título próprio. Opt-in.
     *
     * Use com a opção "Nome do site nos títulos das páginas" do Joomla
     * desligada, para o nome do site não aparecer duplicado.
     */
    private function aplicarTemplateTitulo(): void
    {
        if (!$this->globais->get('title_template_enabled', 0)) {
            return;
        }

        $doc  = Factory::getApplication()->getDocument();
        $home = EsquemaRicoHelper::isFrontPage() ? trim((string) $this->globais->get('title_home', '')) : '';

        if ($home !== '') {
            $doc->setTitle($home);

            return;
        }

        $template = trim((string) $this->globais->get('title_template', '%title% %sep% %sitename%'));

        if ($template === '') {
            return;
        }

        $novo = strtr($template, [
            '%title%'    => (string) $doc->getTitle(),
            '%sitename%' => EsquemaRicoHelper::getSiteName(),
            '%sep%'      => (string) $this->globais->get('title_separator', '-'),
        ]);

        // Colapsa espaços duplicados (ex.: quando %title% resolve para vazio).
        $novo = trim((string) preg_replace('/\s{2,}/', ' ', $novo));

        if ($novo !== '') {
            $doc->setTitle($novo);
        }
    }

    /* ===================================================================
     *  Canonical e controle de indexação (robots)
     * =================================================================== */

    /**
     * Adiciona o canonical autorreferente (quando ausente e habilitado) e
     * aplica "noindex, follow" em páginas finas/duplicadas típicas do Joomla
     * (resultados de busca e, opcionalmente, páginas paginadas).
     */
    private function metaCanonicalERobos(): void
    {
        $app   = Factory::getApplication();
        $doc   = $app->getDocument();
        $input = $app->getInput();

        // Canonical autorreferente (opt-in; recomendado com URLs SEF).
        if ($this->globais->get('canonical_enabled', 0) && !$this->temCanonical($doc)) {
            $doc->addHeadLink(Uri::current(), 'canonical');
        }

        $noindex = false;

        if ($this->globais->get('noindex_search', 1)
            && \in_array($input->getCmd('option'), ['com_search', 'com_finder'], true)) {
            $noindex = true;
        }

        if (!$noindex && $this->globais->get('noindex_paginated', 0) && (int) $input->get('limitstart', 0) > 0) {
            $noindex = true;
        }

        if ($noindex) {
            $robots = trim((string) $doc->getMetaData('robots'));

            if ($robots === '') {
                $doc->setMetaData('robots', 'noindex, follow');
            } elseif (!Functions::strposArr(['noindex'], $robots)) {
                // Preserva as diretivas já presentes (nofollow, noarchive, max-*)
                // e apenas acrescenta noindex.
                $doc->setMetaData('robots', $robots . ', noindex');
            }
        }
    }

    /**
     * Indica se a página já possui um <link rel="canonical"> (do componente
     * ou do template) — para não duplicá-lo.
     */
    private function temCanonical(object $doc): bool
    {
        foreach ((array) ($doc->getHeadData()['links'] ?? []) as $data) {
            if (\is_array($data) && ($data['relation'] ?? '') === 'canonical') {
                return true;
            }
        }

        return false;
    }

    /* ===================================================================
     *  Open Graph / Twitter Cards
     * =================================================================== */

    /**
     * Gera as meta tags Open Graph e Twitter Cards a partir dos metadados da
     * página (título, descrição, URL) e da identidade do site. Não sobrescreve
     * tags já definidas pelo template ou por outra extensão.
     */
    private function metaOpenGraph(): void
    {
        if (!$this->globais->get('og_enabled', 1)) {
            return;
        }

        $app   = Factory::getApplication();
        $doc   = $app->getDocument();
        $title = (string) ($doc->getTitle() ?: EsquemaRicoHelper::getSiteName());
        $desc  = (string) $doc->getDescription();
        $image = (string) ($this->globais->get('og_default_image') ?: EsquemaRicoHelper::getSiteLogo());
        $image = $image !== '' ? (string) EsquemaRicoHelper::cleanImage($image) : '';

        $og = [
            'og:locale'      => str_replace('-', '_', $app->getLanguage()->getTag()),
            'og:type'        => EsquemaRicoHelper::isFrontPage() ? 'website' : 'article',
            'og:title'       => $title,
            'og:description' => $desc,
            'og:url'         => Uri::current(),
            'og:site_name'   => EsquemaRicoHelper::getSiteName(),
            'og:image'       => $image,
        ];

        foreach ($og as $prop => $value) {
            $this->definirMeta($doc, $prop, (string) $value, 'property');
        }

        // Twitter Cards espelham o Open Graph.
        $handle  = trim((string) $this->globais->get('og_twitter_site'));
        $twitter = [
            'twitter:card'        => (string) $this->globais->get('og_twitter_card', 'summary_large_image'),
            'twitter:title'       => $title,
            'twitter:description' => $desc,
            'twitter:image'       => $image,
        ];

        if ($handle !== '') {
            $twitter['twitter:site'] = str_starts_with($handle, '@') ? $handle : '@' . $handle;
        }

        foreach ($twitter as $name => $value) {
            $this->definirMeta($doc, $name, (string) $value, 'name');
        }
    }

    /**
     * Define uma meta tag apenas se houver valor e ela ainda não existir
     * (não sobrescreve o que o template ou outra extensão já definiu).
     */
    private function definirMeta(object $doc, string $key, string $value, string $attribute): void
    {
        if ($value === '' || (string) $doc->getMetaData($key, $attribute) !== '') {
            return;
        }

        $doc->setMetaData($key, $value, $attribute);
    }

    /* ===================================================================
     *  Inicialização (guarda)
     * =================================================================== */

    private function iniciar(): bool
    {
        if ($this->iniciado) {
            return true;
        }

        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return false;
        }

        $doc = $app->getDocument();

        if ($doc === null || $doc->getType() !== 'html') {
            return false;
        }

        if (Functions::isFeed() || $app->getInput()->getInt('print', 0)) {
            return false;
        }

        if (!ExtensionHelper::componentIsEnabled('esquemarico')) {
            return false;
        }

        $this->globais = EsquemaRicoHelper::getParams();
        $this->gerador = new GeradorJsonLd();

        return $this->iniciado = true;
    }
}
