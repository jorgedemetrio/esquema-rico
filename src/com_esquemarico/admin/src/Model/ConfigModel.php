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
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') or die;

/**
 * Modelo das configurações globais (linha "config" de #__esquemarico_config).
 */
class ConfigModel extends FormModel
{
    private const CONFIG_KEY = 'config';

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_esquemarico.config', 'config', ['control' => 'jform', 'load_data' => $loadData]) ?: false;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_esquemarico.config.data', []);

        if (empty($data)) {
            $data = $this->getConfigData();
        }

        return $data;
    }

    /**
     * Lê os parâmetros globais como array.
     */
    public function getConfigData(): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__esquemarico_config'))
            ->where($db->quoteName('name') . ' = :name')
            ->bind(':name', $name, \Joomla\Database\ParameterType::STRING);

        $name = self::CONFIG_KEY;
        $db->setQuery($query);

        return json_decode($db->loadResult() ?: '{}', true) ?: [];
    }

    /**
     * Grava (upsert) os parâmetros globais.
     */
    public function save(array $data): bool
    {
        $db   = Factory::getContainer()->get(DatabaseInterface::class);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $name = self::CONFIG_KEY;

        // Existe a linha?
        $check = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__esquemarico_config'))
            ->where($db->quoteName('name') . ' = :name')
            ->bind(':name', $name, \Joomla\Database\ParameterType::STRING);
        $db->setQuery($check);
        $exists = (int) $db->loadResult() > 0;

        $row = (object) ['name' => $name, 'params' => $json];

        if ($exists) {
            return $db->updateObject('#__esquemarico_config', $row, 'name');
        }

        return $db->insertObject('#__esquemarico_config', $row);
    }
}
