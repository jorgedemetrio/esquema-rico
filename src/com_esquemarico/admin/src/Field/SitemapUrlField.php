<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * Mostra a URL pública do índice de sitemap e a linha "Sitemap:" pronta para
 * colar no robots.txt. Campo somente-leitura (clicar seleciona o texto).
 */
class SitemapUrlField extends FormField
{
    protected $type = 'SitemapUrl';

    protected function getInput(): string
    {
        $index = rtrim(Uri::root(), '/') . '/index.php?option=com_esquemarico&view=sitemap&format=xml';
        $esc   = htmlspecialchars($index, ENT_QUOTES, 'UTF-8');

        $html   = [];
        $html[] = '<div class="esr-sitemap-urls">';
        $html[] = '<input type="text" class="form-control mb-2" readonly value="' . $esc . '" onclick="this.select();">';
        $html[] = '<p class="text-muted small mb-1">' . Text::_('ESR_CFG_SITEMAP_ROBOTS') . '</p>';
        $html[] = '<input type="text" class="form-control" readonly value="Sitemap: ' . $esc . '" onclick="this.select();">';
        $html[] = '</div>';

        return implode("\n", $html);
    }
}
