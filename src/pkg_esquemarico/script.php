<?php

/**
 * @package     Esquema Rico
 * @subpackage  pkg_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;

/**
 * Script de instalação do pacote.
 *
 * Após instalar todas as extensões, habilita automaticamente os plugins
 * necessários (biblioteca, sistema e integrações), poupando o usuário de
 * ativá-los manualmente.
 */
return new class () implements InstallerScriptInterface {
    /**
     * Plugins a habilitar após a instalação: [element, folder].
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private array $autoEnable = [
        ['esquemaricocore', 'system'],
        ['esquemarico', 'system'],
        ['content', 'esquemarico'],
        ['menus', 'esquemarico'],
        ['k2', 'esquemarico'],
        ['virtuemart', 'esquemarico'],
        ['jevents', 'esquemarico'],
        ['hikashop', 'esquemarico'],
        ['dpcalendar', 'esquemarico'],
        ['esquemaricokeywords', 'content'],
        ['esquemaricoseo', 'content'],
    ];

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
        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($this->autoEnable as [$element, $folder]) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('element') . ' = ' . $db->quote($element))
                ->where($db->quoteName('folder') . ' = ' . $db->quote($folder));

            try {
                $db->setQuery($query)->execute();
            } catch (\Throwable) {
                // Não interrompe a instalação por falha ao auto-habilitar.
            }
        }

        return true;
    }
};
