<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Component\Esquemarico\Administrator\Engine\GeradorJsonLd;

\defined('_JEXEC') or die;

/**
 * Lista os tipos de conteúdo (schemas) disponíveis.
 */
class ContentTypesField extends ListField
{
    protected $type = 'ContentTypes';

    protected function getOptions(): array
    {
        $options = parent::getOptions();

        foreach ((new GeradorJsonLd())->getContentTypes() as $type) {
            $key       = 'ESR_CONTENT_TYPE_' . strtoupper($type);
            $label     = Text::_($key);
            $options[] = (object) [
                'value'    => $type,
                'text'     => $label === $key ? ucfirst(str_replace('_', ' ', $type)) : $label,
                'disable'  => false,
            ];
        }

        return $options;
    }
}
