<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Component\Esquemarico\Site\Model\SitemapModel;

\defined('_JEXEC') or die;

/**
 * Entrada do frontend. Devolve os sitemaps XML diretamente.
 *
 * URLs:
 *   index.php?option=com_esquemarico&view=sitemap&format=xml                 -> índice
 *   index.php?option=com_esquemarico&view=sitemap&type=content&format=xml    -> artigos
 *   index.php?option=com_esquemarico&view=sitemap&type=categories&format=xml -> categorias
 *   index.php?option=com_esquemarico&view=sitemap&type=menu&format=xml       -> itens de menu
 *   index.php?option=com_esquemarico&view=sitemap&type=tags&format=xml       -> tags
 */
class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $type = $this->input->getCmd('type', '');

        /** @var SitemapModel $model */
        $model = $this->getModel('Sitemap', 'Site');

        $xml = match ($type) {
            'content'    => $model->buildContent(),
            'categories' => $model->buildCategories(),
            'menu'       => $model->buildMenu(),
            'tags'       => $model->buildTags(),
            default      => $model->buildIndex(),
        };

        $app = $this->app;
        $app->setHeader('Content-Type', 'application/xml; charset=utf-8', true);
        // O próprio sitemap não deve ser indexado como página.
        $app->setHeader('X-Robots-Tag', 'noindex', true);
        $app->sendHeaders();

        echo $xml;

        $app->close();

        return $this;
    }
}
