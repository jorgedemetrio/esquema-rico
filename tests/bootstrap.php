<?php

/**
 * @package     Esquema Rico
 * @subpackage  Testes
 *
 * Bootstrap dos testes do motor JSON-LD.
 *
 * O motor (GeradorJsonLd) depende apenas de:
 *   - Joomla\Registry\Registry  (pacote standalone joomla/registry)
 *   - Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper
 *     (event/log) — substituído aqui por um stub mínimo.
 *
 * Assim conseguimos testá-lo fora de uma instalação completa do Joomla.
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

declare(strict_types=1);

namespace {
    \defined('_JEXEC') or \define('_JEXEC', 1);

    require __DIR__ . '/vendor/autoload.php';
}

namespace Joomla\Component\Esquemarico\Administrator\Helper {
    /**
     * Stub do helper: event() e log() são no-ops nos testes.
     */
    class EsquemaRicoHelper
    {
        /** @var array<int, mixed> */
        public static array $log = [];

        public static function event(string $name, array $args = []): array
        {
            return [];
        }

        public static function log(mixed $message): void
        {
            self::$log[] = $message;
        }
    }
}

namespace {
    require __DIR__ . '/../src/com_esquemarico/admin/src/Engine/GeradorJsonLd.php';
    require __DIR__ . '/../src/com_esquemarico/site/src/Helper/SitemapPriority.php';
    require __DIR__ . '/../src/com_esquemarico/site/src/Helper/SitemapBuilder.php';
    require __DIR__ . '/../src/com_esquemarico/admin/src/Seo/SeoAnalyzer.php';
}
