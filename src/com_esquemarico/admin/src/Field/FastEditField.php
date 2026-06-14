<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Edição rápida embutida no editor da fonte (ex.: artigo).
 *
 * v1: painel com acesso de um clique ao gerenciamento/criação de itens de
 * marcação, sem sair do editor. O editor totalmente inline (com AJAX para
 * carregar os campos do tipo e salvar junto com o artigo) é uma evolução futura.
 */
class FastEditField extends FormField
{
    protected $type = 'FastEdit';

    protected function getInput(): string
    {
        // Garante que as strings do componente estejam carregadas neste contexto.
        Factory::getApplication()->getLanguage()->load('com_esquemarico', JPATH_ADMINISTRATOR);

        $manageUrl = Route::_('index.php?option=com_esquemarico&view=items');
        $newUrl    = Route::_('index.php?option=com_esquemarico&task=item.add');

        $html   = [];
        $html[] = '<div class="esr-fastedit card card-body">';
        $html[] = '<p class="mb-2">' . Text::_('COM_ESQUEMARICO_FASTEDIT_INTRO') . '</p>';
        $html[] = '<div class="btn-toolbar" role="toolbar">';
        $html[] = '<a class="btn btn-primary me-2" href="' . $newUrl . '" target="_blank" rel="noopener">'
            . '<span class="icon-new" aria-hidden="true"></span> ' . Text::_('COM_ESQUEMARICO_FASTEDIT_CREATE') . '</a>';
        $html[] = '<a class="btn btn-secondary" href="' . $manageUrl . '" target="_blank" rel="noopener">'
            . '<span class="icon-list" aria-hidden="true"></span> ' . Text::_('COM_ESQUEMARICO_FASTEDIT_MANAGE') . '</a>';
        $html[] = '</div>';
        $html[] = '<p class="text-muted small mt-2 mb-0">' . Text::_('COM_ESQUEMARICO_FASTEDIT_HINT') . '</p>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Campo apenas informativo: não renderiza rótulo padrão.
     */
    protected function getLabel(): string
    {
        return '';
    }
}
