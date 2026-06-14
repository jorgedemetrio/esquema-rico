<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;

\defined('_JEXEC') or die;

/**
 * Modelo da lista de itens de marcação.
 */
class ItemsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'plugin', 'a.plugin',
                'contenttype', 'a.contenttype',
                'state', 'a.state',
                'language', 'a.language',
                'ordering', 'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        $app = Factory::getApplication();

        $this->setState('filter.search', $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.published', $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string'));
        $this->setState('filter.plugin', $app->getUserStateFromRequest($this->context . '.filter.plugin', 'filter_plugin', '', 'string'));
        $this->setState('filter.contenttype', $app->getUserStateFromRequest($this->context . '.filter.contenttype', 'filter_contenttype', '', 'string'));
        $this->setState('filter.language', $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string'));

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.plugin');
        $id .= ':' . $this->getState('filter.contenttype');
        $id .= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*')
            ->from($db->quoteName('#__esquemarico', 'a'));

        // Estado.
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $published, \Joomla\Database\ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->where($db->quoteName('a.state') . ' IN (0, 1)');
        }

        // Integração.
        if ($plugin = (string) $this->getState('filter.plugin')) {
            $query->where($db->quoteName('a.plugin') . ' = :plugin')->bind(':plugin', $plugin);
        }

        // Tipo de conteúdo.
        if ($type = (string) $this->getState('filter.contenttype')) {
            $query->where($db->quoteName('a.contenttype') . ' = :ctype')->bind(':ctype', $type);
        }

        // Idioma.
        if ($language = (string) $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = :language')->bind(':language', $language);
        }

        // Busca textual.
        if ($search = (string) $this->getState('filter.search')) {
            $like = '%' . $search . '%';
            $query->where('(' . $db->quoteName('a.title') . ' LIKE :search OR ' . $db->quoteName('a.note') . ' LIKE :search2)')
                ->bind(':search', $like)
                ->bind(':search2', $like);
        }

        $orderCol = $this->getState('list.ordering', 'a.ordering');
        $orderDir = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDir));

        return $query;
    }
}
