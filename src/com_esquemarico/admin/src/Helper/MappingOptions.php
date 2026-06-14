<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Helper;

use Esquemarico\Core\SmartTags\SmartTags;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Resolve os modos de mapeamento de cada propriedade e substitui as SmartTags.
 *
 * Modos suportados por propriedade:
 *   - "fixed"           valor fixo digitado
 *   - "_custom_"        expressão livre (pode conter SmartTags)
 *   - "_css_selector_"  extração via seletor CSS (delegada à integração)
 *   - "_disabled_"      propriedade não emitida
 *   - <origem>          uma SmartTag de origem (ex.: gsd.item.headline)
 */
final class MappingOptions
{
    /**
     * Opções de mapeamento fixas disponíveis no seletor do backend.
     *
     * @var array<string, array<string, string>>
     */
    public static array $options = [
        'ESR_GROUP_INTEGRATION' => [
            'gsd.item.id'           => 'ID',
            'gsd.item.alias'        => 'ESR_OPT_ALIAS',
            'gsd.item.headline'     => 'ESR_OPT_TITLE',
            'gsd.item.description'  => 'ESR_OPT_TEXT',
            'gsd.item.introtext'    => 'ESR_OPT_INTROTEXT',
            'gsd.item.fulltext'     => 'ESR_OPT_FULLTEXT',
            'gsd.item.image'        => 'ESR_OPT_IMAGE',
            'gsd.item.imagetext'    => 'ESR_OPT_IMAGE_FROM_TEXT',
            'url'                   => 'ESR_OPT_URL',
            'user.name'             => 'ESR_OPT_AUTHOR_NAME',
            'user.email'            => 'ESR_OPT_AUTHOR_EMAIL',
            'gsd.item.created'      => 'ESR_OPT_DATE_CREATED',
            'gsd.item.publish_up'   => 'ESR_OPT_DATE_PUBLISH_UP',
            'gsd.item.modified'     => 'ESR_OPT_DATE_MODIFIED',
            'gsd.item.ratingValue'  => 'ESR_OPT_RATING_VALUE',
            'gsd.item.reviewCount'  => 'ESR_OPT_REVIEW_COUNT',
            'gsd.item.metakey'      => 'ESR_OPT_METAKEY',
            'gsd.item.metadesc'     => 'ESR_OPT_METADESC',
        ],
        'ESR_GROUP_PAGE' => [
            'page.title'    => 'ESR_OPT_PAGE_TITLE',
            'page.desc'     => 'ESR_OPT_PAGE_DESC',
            'page.keywords' => 'ESR_OPT_PAGE_KEYWORDS',
            'page.lang'     => 'ESR_OPT_PAGE_LANG',
        ],
        'ESR_GROUP_SITE' => [
            'gsd.sitename' => 'ESR_OPT_SITE_NAME',
            'gsd.siteurl'  => 'ESR_OPT_SITE_URL',
            'gsd.sitelogo' => 'ESR_OPT_SITE_LOGO',
            'site.email'   => 'ESR_OPT_SITE_EMAIL',
        ],
    ];

    /**
     * Embrulha uma string como SmartTag: "headline" -> "{headline}".
     */
    public static function make(?string $string): ?string
    {
        return ($string === null || $string === '') ? null : '{' . $string . '}';
    }

    /**
     * Resolve os modos de mapeamento das propriedades (in-place).
     */
    public static function prepare(Registry $properties): void
    {
        foreach ($properties->toArray() as $key => $property) {
            if (!\is_object($property) && !\is_array($property)) {
                continue;
            }

            $property = (object) $property;

            if (!isset($property->option)) {
                continue;
            }

            $value = match ($property->option) {
                'fixed' => self::resolveFixed($key, $property),
                '_custom_' => $property->custom ?? null,
                '_disabled_' => false,
                '_css_selector_' => null, // delegado às integrações
                default => self::make((string) $property->option),
            };

            $properties->set($key, $value);
        }
    }

    /**
     * Substitui as SmartTags do snippet usando o payload e as configs globais.
     */
    public static function replace(Registry $snippet, Registry $payload): Registry
    {
        $payloadArray = $payload->toArray();

        // Nulos viram string vazia para que a tag seja removida.
        foreach ($payloadArray as $k => $v) {
            if ($v === null) {
                $payloadArray[$k] = '';
            }
        }

        $smartTags = new SmartTags([
            'user' => $payloadArray['created_by'] ?? null,
        ]);

        $smartTags->add($payloadArray, 'gsd.item.');
        $smartTags->add([
            'sitename' => EsquemaRicoHelper::getSiteName(),
            'siteurl'  => EsquemaRicoHelper::getSiteURL(),
            'sitelogo' => EsquemaRicoHelper::getSiteLogo(),
        ], 'gsd.');

        // Tag sem prefixo {url} = URL canônica da página atual (onde o schema é
        // renderizado), usada como padrão em vários tipos de conteúdo.
        $smartTags->add(['url' => Uri::current()], '');

        return new Registry($smartTags->replace($snippet->toArray()));
    }

    /**
     * Acrescenta novas opções de mapeamento (usado pelas integrações).
     */
    public static function add(array &$options, array $newOptions, string $group = 'ESR_GROUP_CUSTOM_FIELDS', string $prefix = 'gsd.item.cf.'): void
    {
        $prefixed = [];

        foreach ($newOptions as $key => $label) {
            $prefixed[$prefix . $key] = $label;
        }

        $options = array_merge_recursive($options, [$group => $prefixed]);
    }

    /**
     * Resolve o modo "fixed", traduzindo IDs de usuário em nomes quando aplicável.
     */
    private static function resolveFixed(string $key, object $property): mixed
    {
        $fixed = $property->fixed ?? null;

        if (\in_array($key, ['author', 'publisher_name'], true) && is_numeric($fixed)) {
            $user = Factory::getContainer()
                ->get(UserFactoryInterface::class)
                ->loadUserById((int) $fixed);

            if ($user && $user->id) {
                return $user->name;
            }
        }

        return $fixed;
    }
}
