<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\DI\Container;

/**
 * Script de instalação do componente.
 *
 * Faz a verificação de pré-requisitos (Joomla, PHP, MySQL) e exibe uma
 * mensagem de boas-vindas. A criação/remoção das tabelas é feita pelos
 * arquivos SQL declarados no manifesto.
 */
return new class () implements InstallerScriptInterface {
    private string $minimumJoomla = '5.0.0';
    private string $minimumPhp     = '8.1.0';

    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_ESQUEMARICO_INSTALL_PHP_TOO_OLD', $this->minimumPhp, PHP_VERSION),
                'error'
            );

            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_ESQUEMARICO_INSTALL_JOOMLA_TOO_OLD', $this->minimumJoomla, JVERSION),
                'error'
            );

            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'install') {
            Factory::getApplication()->enqueueMessage(
                Text::_('COM_ESQUEMARICO_INSTALL_WELCOME'),
                'message'
            );
        }

        return true;
    }
};
