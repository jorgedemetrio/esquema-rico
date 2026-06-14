<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core\SmartTags\Tags;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;

\defined('_JEXEC') or die;

/**
 * Resolve SmartTags da família `user.*`.
 */
final class Usuario
{
    private ?User $user = null;

    public function __construct(?int $userId = null)
    {
        try {
            if ($userId !== null && $userId > 0) {
                $this->user = Factory::getContainer()
                    ->get(UserFactoryInterface::class)
                    ->loadUserById($userId);
            } else {
                $this->user = Factory::getApplication()->getIdentity();
            }
        } catch (\Throwable) {
            $this->user = null;
        }
    }

    public function get(string $key, ?string $argument = null): ?string
    {
        if ($this->user === null) {
            return null;
        }

        $name  = (string) $this->user->name;
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return match ($key) {
            'id'        => (string) $this->user->id,
            'name'      => $name,
            'firstname' => $parts[0] ?? '',
            'lastname'  => \count($parts) > 1 ? (string) end($parts) : '',
            'login', 'username' => (string) $this->user->username,
            'email'     => (string) $this->user->email,
            default     => null,
        };
    }
}
