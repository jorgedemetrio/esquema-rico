<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core;

use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Consulta a tabela #__extensions para descobrir se uma extensão de terceiros
 * está instalada/habilitada e qual a sua versão. Usado pelas integrações para
 * só atuarem quando a fonte correspondente existe.
 */
final class Extension
{
    /**
     * Cache estático das linhas de #__extensions já consultadas.
     *
     * @var array<string, ?object>
     */
    private static array $cache = [];

    /**
     * Retorna a linha de #__extensions para a extensão, ou null se não existir.
     */
    public static function get(string $element, string $type = 'component', ?string $folder = null): ?object
    {
        $key = $type . '.' . ($folder ?? '') . '.' . $element;

        if (\array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':type', $type)
            ->bind(':element', $element)
            ->setLimit(1);

        if ($folder !== null) {
            $query->where($db->quoteName('folder') . ' = :folder')
                ->bind(':folder', $folder);
        }

        $db->setQuery($query);

        return self::$cache[$key] = $db->loadObject() ?: null;
    }

    /**
     * A extensão está instalada?
     */
    public static function isInstalled(string $element, string $type = 'component', ?string $folder = null): bool
    {
        return self::get($element, $type, $folder) !== null;
    }

    /**
     * A extensão está habilitada (enabled = 1)?
     */
    public static function isEnabled(string $element, string $type = 'component', ?string $folder = null): bool
    {
        $ext = self::get($element, $type, $folder);

        return $ext !== null && (int) $ext->enabled === 1;
    }

    /**
     * Atalho: o componente está habilitado?
     *
     * Aceita tanto "esquemarico" quanto "com_esquemarico".
     */
    public static function componentIsEnabled(string $element): bool
    {
        if (!str_starts_with($element, 'com_')) {
            $element = 'com_' . $element;
        }

        return self::isEnabled($element, 'component');
    }

    /**
     * Atalho: o plugin está habilitado?
     */
    public static function pluginIsEnabled(string $element, string $folder): bool
    {
        return self::isEnabled($element, 'plugin', $folder);
    }

    /**
     * Versão declarada no manifesto da extensão (ou null).
     */
    public static function getVersion(string $element, string $type = 'component', ?string $folder = null): ?string
    {
        $ext = self::get($element, $type, $folder);

        if ($ext === null || empty($ext->manifest_cache)) {
            return null;
        }

        $manifest = json_decode((string) $ext->manifest_cache, true);

        return $manifest['version'] ?? null;
    }
}
