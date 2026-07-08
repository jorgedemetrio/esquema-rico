<?php
/**
 * @package     Esquema Rico
 * @subpackage  mod_esquemarico_adsense
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Module\EsquemaricoAdsense\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;

/**
 * Despachante do módulo mod_esquemarico_adsense.
 */
class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data       = parent::getLayoutData();
        $data['cx'] = $this->params->get('cx', '');

        return $data;
    }
}
