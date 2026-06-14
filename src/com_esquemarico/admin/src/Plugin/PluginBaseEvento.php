<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Plugin;

use Esquemarico\Core\Functions;

\defined('_JEXEC') or die;

/**
 * Base das integrações de eventos/calendários.
 *
 * Fornece auxiliares de datas de início/fim que as integrações concretas
 * (JEvents, DPCalendar, iCagenda…) reutilizam ao montar o payload.
 */
abstract class PluginBaseEvento extends PluginBase
{
    /**
     * Normaliza uma data de evento para UTC (formato SQL).
     */
    protected function dataEvento(?string $data): ?string
    {
        return Functions::dateToUTC($data);
    }
}
