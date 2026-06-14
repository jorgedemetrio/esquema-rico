<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core\SmartTags\Tags;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * Resolve SmartTags da família `site.*` (configuração global do Joomla).
 */
final class Site
{
    public function get(string $key, ?string $argument = null): ?string
    {
        $app = Factory::getApplication();

        return match ($key) {
            'name'  => (string) $app->get('sitename'),
            'email' => (string) $app->get('mailfrom'),
            'url'   => Uri::root(),
            default => null,
        };
    }
}
