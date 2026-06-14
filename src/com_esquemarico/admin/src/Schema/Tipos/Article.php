<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Schema\Tipos;

use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Schema\Base;

\defined('_JEXEC') or die;

/**
 * Preparação específica do schema Article.
 *
 * Garante valores padrão de publisher (nome e logo do site) e permite que o
 * texto da descrição mantenha parágrafos básicos.
 */
final class Article extends Base
{
    protected function initProps(): void
    {
        $this->data->merge(new \Joomla\Registry\Registry([
            'publisherName' => $this->data->get('publisher_name', EsquemaRicoHelper::getSiteName()),
            'publisherLogo' => EsquemaRicoHelper::cleanImage(
                EsquemaRicoHelper::absUrl($this->data->get('publisher_logo', EsquemaRicoHelper::getSiteLogo()))
            ),
        ]));

        parent::initProps();
    }
}
