<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Schema;

\defined('_JEXEC') or die;

/**
 * Fábrica de classes de preparação de schema.
 *
 * Dado o alias de um tipo de conteúdo (ex.: "article"), devolve a classe de
 * preparação específica (Schema\Tipos\Article) ou a Base genérica quando não
 * houver especialização.
 */
final class SchemaHelper
{
    public static function getInstance(string $contentType): Base
    {
        $class = __NAMESPACE__ . '\\Tipos\\' . self::toClassName($contentType);

        if (class_exists($class)) {
            return new $class();
        }

        return new Base();
    }

    /**
     * Converte "custom_code" -> "Custom_Code", "localbusiness" -> "Localbusiness".
     */
    private static function toClassName(string $contentType): string
    {
        $parts = explode('_', $contentType);
        $parts = array_map('ucfirst', $parts);

        return implode('_', $parts);
    }
}
