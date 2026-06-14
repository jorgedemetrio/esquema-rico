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

        $this->controleSnippetRobos();
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

        $markup = implode("\n", array_filter($blocos));

        if (trim($markup) === '') {
            return null;
        }

        if ($this->params->get('minifyjson', 0)) {
            $markup = Functions::minify($markup);
        }

        return "\n<!-- Início: Esquema Rico -->\n" . $markup . "\n<!-- Fim: Esquema Rico -->\n";
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
