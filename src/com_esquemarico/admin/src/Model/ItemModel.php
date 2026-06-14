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
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Modelo de um item de marcação (CRUD).
 */
class ItemModel extends AdminModel
{
    protected $text_prefix = 'COM_ESQUEMARICO';

    public function getTable($name = 'Item', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_esquemarico.item', 'item', ['control' => 'jform', 'load_data' => $loadData]);

        return $form ?: false;
    }

    /**
     * Carrega dinamicamente os campos do tipo de conteúdo e as condições.
     */
    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        $type = \is_object($data) ? ($data->contenttype ?? '') : ($data['contenttype'] ?? '');

        if ($type !== '') {
            $file = JPATH_ADMINISTRATOR . '/components/com_esquemarico/forms/contenttypes/' . $type . '.xml';

            if (is_file($file)) {
                $form->loadFile($file, false);
            }
        }

        // Dispara os plugins de integração (grupo "esquemarico"), que injetam
        // as condições de publicação (form/assignments.xml).
        parent::preprocessForm($form, $data, 'esquemarico');
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_esquemarico.edit.item.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Expande o JSON de params nas estruturas esperadas pelo formulário.
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if (!$item || empty($item->id)) {
            return $item;
        }

        $params = new Registry(\is_string($item->params) ? $item->params : '');

        // Dados do tipo de conteúdo, expostos sob a chave <contenttype>.
        if (!empty($item->contenttype)) {
            $item->{$item->contenttype} = $params->get($item->contenttype, new \stdClass());
        }

        // Condições de publicação, sob params > assignments.
        $item->params = ['assignments' => $params->get('assignments', new \stdClass())];

        return $item;
    }

    /**
     * Agrega os dados do tipo e as condições no campo params antes de gravar.
     */
    public function save($data)
    {
        $type = $data['contenttype'] ?? '';

        $params = [];

        if ($type !== '' && isset($data[$type])) {
            $params[$type] = $data[$type];
            unset($data[$type]);
        }

        if (isset($data['params']['assignments'])) {
            $params['assignments'] = $data['params']['assignments'];
        }

        $data['params'] = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return parent::save($data);
    }
}
