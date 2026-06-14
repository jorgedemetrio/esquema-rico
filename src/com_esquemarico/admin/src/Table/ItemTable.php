<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

/**
 * Tabela de um item de marcação (#__esquemarico).
 */
class ItemTable extends Table
{
    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_esquemarico.item';

        parent::__construct('#__esquemarico', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'state');
    }

    /**
     * Validação/normalização antes de gravar.
     */
    public function check(): bool
    {
        $this->title = trim((string) $this->title);

        if ($this->title === '') {
            $this->title = $this->contenttype !== '' ? ucfirst($this->contenttype) : 'Item';
        }

        if (empty($this->language)) {
            $this->language = '*';
        }

        if (empty($this->appview)) {
            $this->appview = '*';
        }

        return parent::check();
    }

    /**
     * Preenche datas e autoria na criação/edição.
     */
    public function store($updateNulls = true): bool
    {
        $now  = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();
        $uid  = $user ? (int) $user->id : 0;

        if (!$this->id) {
            $this->created    = $this->created ?: $now;
            $this->created_by = $this->created_by ?: $uid;
        } else {
            $this->modified    = $now;
            $this->modified_by = $uid;
        }

        return parent::store($updateNulls);
    }
}
