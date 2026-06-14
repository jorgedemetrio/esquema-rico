<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\View\Config;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Configurações globais (backend).
 */
class HtmlView extends BaseHtmlView
{
    protected $form;

    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        ToolbarHelper::title(Text::_('COM_ESQUEMARICO') . ': ' . Text::_('COM_ESQUEMARICO_SUBMENU_CONFIG'), 'cog');
        ToolbarHelper::apply('config.save');
        ToolbarHelper::save('config.save');
        ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CLOSE');
    }
}
