<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\View\Painel;

use Esquemarico\Core\Extension as ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Painel (dashboard) do componente.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * O plugin de sistema está habilitado?
     */
    public bool $pluginAtivo = false;

    public function display($tpl = null): void
    {
        @include_once JPATH_PLUGINS . '/system/esquemaricocore/autoload.php';

        $this->pluginAtivo = ExtensionHelper::pluginIsEnabled('esquemarico', 'system');

        $this->addToolbar();

        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_ESQUEMARICO_PAINEL_TITLE'), 'star');

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_esquemarico')) {
            ToolbarHelper::preferences('com_esquemarico');
        }
    }
}
