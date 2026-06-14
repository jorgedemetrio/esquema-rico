<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\View\Items;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Lista de itens de marcação (backend).
 */
class HtmlView extends BaseHtmlView
{
    /** @var array<int, object> */
    protected $items = [];

    protected $pagination;

    protected $state;

    public $filterForm;

    public $activeFilters = [];

    public function display($tpl = null): void
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_ESQUEMARICO') . ': ' . Text::_('COM_ESQUEMARICO_SUBMENU_ITEMS'), 'star');

        if ($user->authorise('core.create', 'com_esquemarico')) {
            ToolbarHelper::addNew('item.add');
        }

        if ($user->authorise('core.edit.state', 'com_esquemarico')) {
            ToolbarHelper::publish('items.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('items.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            ToolbarHelper::trash('items.trash');
        }

        if ($user->authorise('core.admin', 'com_esquemarico')) {
            ToolbarHelper::preferences('com_esquemarico');
        }
    }
}
