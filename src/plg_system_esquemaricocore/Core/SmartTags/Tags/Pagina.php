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

\defined('_JEXEC') or die;

/**
 * Resolve SmartTags da família `page.*` (metadados do documento atual).
 */
final class Pagina
{
    public function get(string $key, ?string $argument = null): ?string
    {
        $doc = Factory::getApplication()->getDocument();

        if ($doc === null) {
            return null;
        }

        return match ($key) {
            'title', 'browsertitle' => (string) $doc->getTitle(),
            'desc'        => (string) $doc->getDescription(),
            'keywords'    => (string) $doc->getMetaData('keywords'),
            'lang'        => (string) $doc->getLanguage(),
            'generator'   => (string) $doc->getGenerator(),
            default       => null,
        };
    }
}
