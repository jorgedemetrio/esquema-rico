<?php

/**
 * @package     Esquema Rico
 * @subpackage  Esquemarico\Core
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Esquemarico\Core\SmartTags\Tags;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Resolve SmartTags de data/hora (`{date}`, `{year}`, `{month}`, `{time}`).
 *
 * O namespace é "date", então `{date}` chega aqui com a chave "date" e
 * `{date:Y-m-d}` chega com a chave "date" e o argumento "Y-m-d".
 */
final class DataHora
{
    private Date $now;

    public function __construct()
    {
        $tz        = new \DateTimeZone(Factory::getApplication()->get('offset', 'UTC'));
        $this->now = Factory::getDate('now', $tz);
    }

    public function get(string $key, ?string $argument = null): ?string
    {
        return match ($key) {
            'date'  => $this->now->format($argument ?: 'Y-m-d'),
            'year'  => $this->now->format('Y'),
            'month' => $this->now->format('m'),
            'day'   => $this->now->format('d'),
            'time'  => $this->now->format($argument ?: 'H:i'),
            default => null,
        };
    }
}
