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
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Funções de domínio do Esquema Rico: configuração, breadcrumbs, URLs,
 * informações do site, eventos e log.
 */
final class EsquemaRicoHelper
{
    /**
     * Parâmetros globais (linha "config" de #__esquemarico_config).
     */
    private static ?Registry $params = null;

    /**
     * Mensagens de log acumuladas (exibidas no modo debug).
     *
     * @var array<int, mixed>
     */
    public static array $log = [];

    /**
     * Carrega os parâmetros globais da extensão.
     */
    public static function getParams(): Registry
    {
        if (self::$params !== null) {
            return self::$params;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__esquemarico_config'))
            ->where($db->quoteName('name') . ' = ' . $db->quote('config'));

        $db->setQuery($query);

        return self::$params = new Registry($db->loadResult() ?: '{}');
    }

    /**
     * Nome do site (preferencial das configurações, com fallback no Joomla).
     */
    public static function getSiteName(): string
    {
        return (string) self::getParams()->get('sitename_name', Factory::getApplication()->get('sitename'));
    }

    /**
     * URL canônica do site.
     */
    public static function getSiteURL(): string
    {
        return (string) self::getParams()->get('sitename_url', Uri::root());
    }

    /**
     * URL do logo configurado (ou null).
     */
    public static function getSiteLogo(): ?string
    {
        $logo = self::getParams()->get('logo_file');

        return $logo ? Uri::root() . $logo : null;
    }

    /**
     * Estamos na página inicial?
     */
    public static function isFrontPage(): bool
    {
        $menu = Factory::getApplication()->getMenu();

        if ($menu === null) {
            return false;
        }

        $lang = Factory::getApplication()->getLanguage()->getTag();

        return $menu->getActive() === $menu->getDefault($lang);
    }

    /**
     * Converte uma URL relativa em absoluta, respeitando URLs externas.
     */
    public static function absUrl(?string $url): string
    {
        if (!\is_string($url) || $url === '') {
            return '';
        }

        $uri = Uri::getInstance($url);

        if (\in_array($uri->getScheme(), ['http', 'https'], true)) {
            return $uri->toString();
        }

        $clean = str_replace([Uri::root(), Uri::root(true)], '', $uri->toString());

        return Uri::root() . ltrim($clean, '/');
    }

    /**
     * Limpa o caminho de imagem do campo de mídia do Joomla (remove #joomlaImage…).
     */
    public static function cleanImage(?string $path): string
    {
        $path = self::absUrl($path);

        return $path === '' ? '' : MediaHelper::getCleanMediaFieldValue($path);
    }

    /**
     * Gera rota respeitando a configuração de SSL forçado.
     */
    public static function route(string $route, bool $xhtml = true): string
    {
        $forceSsl = (int) Factory::getApplication()->get('force_ssl');
        $secure   = ($forceSsl === 2 || (($_SERVER['HTTPS'] ?? 'off') !== 'off')) ? 1 : 2;

        return Route::_($route, $xhtml, $secure);
    }

    /**
     * Monta a lista de breadcrumbs a partir do pathway do Joomla.
     *
     * @return array<int, \stdClass>|false
     */
    public static function getCrumbs(string $homeText, bool $addHome = true): array|false
    {
        $app   = Factory::getApplication();
        $items = $app->getPathway()->getPathway();

        if (!$items) {
            return false;
        }

        $crumbs = self::pathwayCrumbs($items);

        if ($addHome) {
            $crumbs = self::prependHome($crumbs, $homeText, $app);
        }

        // Corrige a URL ausente do último item (página atual).
        if ($crumbs) {
            $last = end($crumbs);

            if (empty($last->link)) {
                $crumbs[key($crumbs)]->link = Uri::current();
            }
        }

        // Converte URLs relativas em absolutas.
        foreach ($crumbs as $crumb) {
            if (\is_string($crumb->link) && $crumb->link !== '') {
                $crumb->link = self::absUrl($crumb->link);
            }
        }

        return $crumbs;
    }

    /**
     * Constrói os crumbs a partir dos itens do pathway (ignora itens sem link/nome).
     *
     * @param  array<int, \stdClass>  $items
     *
     * @return array<int, \stdClass>
     */
    private static function pathwayCrumbs(array $items): array
    {
        $crumbs = [];

        foreach ($items as $item) {
            if ($item->link === null || !$item->name) {
                continue;
            }

            $name = stripslashes(htmlspecialchars(strip_tags((string) $item->name), ENT_COMPAT, 'UTF-8'));
            $name = (string) preg_replace('#\[icon\].*?\[/icon\]#', '', $name);

            $crumbs[] = (object) [
                'name' => trim($name),
                'link' => self::route((string) $item->link),
            ];
        }

        return $crumbs;
    }

    /**
     * Prepende o item "início" à lista de crumbs, quando existe menu padrão.
     *
     * @param  array<int, \stdClass>  $crumbs
     *
     * @return array<int, \stdClass>
     */
    private static function prependHome(array $crumbs, string $homeText, object $app): array
    {
        $menu = $app->getMenu();
        $home = Multilanguage::isEnabled() ? $menu->getDefault($app->getLanguage()->getTag()) : $menu->getDefault();

        if ($home !== null) {
            array_unshift($crumbs, (object) [
                'name' => htmlspecialchars($homeText),
                'link' => self::route('index.php?Itemid=' . $home->id),
            ]);
        }

        return $crumbs;
    }

    /**
     * Retorna a primeira imagem encontrada em um trecho de HTML.
     */
    public static function getFirstImageFromString(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        if (preg_match('/<img.+?src=[\'"](?P<src>.+?)[\'"].*?>/i', $text, $m)) {
            return $m['src'] ?? null;
        }

        return null;
    }

    /**
     * Retorna o alias do componente ativo (ex.: "content" para com_content).
     */
    public static function getComponentAlias(): ?string
    {
        $option = Factory::getApplication()->getInput()->get('option');

        if (!$option) {
            return null;
        }

        $parts = explode('_', $option);

        return $parts[1] ?? null;
    }

    /**
     * Dispara um evento para os plugins do grupo "esquemarico" e "system".
     */
    public static function event(string $name, array $arguments = []): mixed
    {
        PluginHelper::importPlugin('esquemarico');
        PluginHelper::importPlugin('system');

        return Factory::getApplication()->triggerEvent($name, $arguments);
    }

    /**
     * Lista de dias da semana.
     *
     * @return string[]
     */
    public static function getWeekdays(bool $capitalize = false): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return $capitalize ? array_map('ucfirst', $days) : $days;
    }

    /**
     * Acrescenta uma mensagem ao log de depuração.
     */
    public static function log(mixed $message): void
    {
        self::$log[] = $message;
    }
}
