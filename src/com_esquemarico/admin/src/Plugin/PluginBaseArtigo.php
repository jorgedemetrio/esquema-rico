<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Plugin;

use Joomla\Component\Esquemarico\Administrator\Helper\SchemaCleaner;

\defined('_JEXEC') or die;

/**
 * Base das integrações de artigos/blogs.
 *
 * Além do ciclo padrão, remove no onAfterRender os schemas baseados em artigo
 * gerados pelo template/extensão para a página atual, evitando duplicidade.
 */
abstract class PluginBaseArtigo extends PluginBase
{
    public function onAfterRender(): void
    {
        if ($this->app()->isClient('administrator') || !$this->passContext()) {
            return;
        }

        if (!$this->params->get('remove_default_schema', 1)) {
            return;
        }

        SchemaCleaner::remove([
            'BlogPosting',
            'Article',
            'NewsArticle',
            'Blog',
            'AggregateRating',
            'Person',
        ], false);
    }
}
