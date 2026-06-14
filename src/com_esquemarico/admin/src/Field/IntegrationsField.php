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
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;

\defined('_JEXEC') or die;

/**
 * Lista as integrações (plugins do grupo "esquemarico") instaladas e habilitadas.
 */
class IntegrationsField extends ListField
{
    protected $type = 'Integrations';

    protected function getOptions(): array
    {
        $options = parent::getOptions();

        // Cada plugin de integração responde com {name, alias}.
        $plugins = array_filter((array) EsquemaRicoHelper::event('onEsquemaRicoGetType'));

        foreach ($plugins as $plugin) {
            if (!\is_array($plugin) || !isset($plugin['alias'])) {
                continue;
            }

            $options[] = (object) [
                'value'   => $plugin['alias'],
                'text'    => $plugin['name'] ?? $plugin['alias'],
                'disable' => false,
            ];
        }

        return $options;
    }
}
