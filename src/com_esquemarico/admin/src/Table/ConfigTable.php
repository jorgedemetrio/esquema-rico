<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

/**
 * Tabela de configuração chave => params (#__esquemarico_config).
 *
 * Guarda os esquemas globais e as opções avançadas em uma única linha "config".
 */
class ConfigTable extends Table
{
    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__esquemarico_config', 'name', $db, $dispatcher);
    }

    public function check(): bool
    {
        if (empty($this->name)) {
            $this->setError('Chave de configuração ausente.');

            return false;
        }

        return true;
    }
}
