<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Controlador das configurações globais.
 */
class ConfigController extends BaseController
{
    /**
     * Grava as configurações globais.
     */
    public function save(): void
    {
        $this->checkToken();

        $app   = $this->app;
        $model = $this->getModel('Config');
        $form  = $model->getForm();
        $data  = $this->input->post->get('jform', [], 'array');

        $validated = $model->validate($form, $data);

        if ($validated === false) {
            $app->setUserState('com_esquemarico.config.data', $data);

            foreach ($model->getErrors() as $error) {
                $app->enqueueMessage($error instanceof \Throwable ? $error->getMessage() : (string) $error, 'warning');
            }

            $this->setRedirect(Route::_('index.php?option=com_esquemarico&view=config&layout=edit', false));

            return;
        }

        $model->save($validated);
        $app->setUserState('com_esquemarico.config.data', null);

        $app->enqueueMessage(Text::_('COM_ESQUEMARICO_CONFIG_SAVED'), 'message');
        $this->setRedirect(Route::_('index.php?option=com_esquemarico&view=config&layout=edit', false));
    }

    /**
     * Cancela e volta ao painel.
     */
    public function cancel(): void
    {
        $this->app->setUserState('com_esquemarico.config.data', null);
        $this->setRedirect(Route::_('index.php?option=com_esquemarico', false));
    }
}
