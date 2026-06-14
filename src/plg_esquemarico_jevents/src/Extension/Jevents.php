<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_esquemarico_jevents
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Esquemarico\Jevents\Extension;

use Esquemarico\Core\Extension as ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;
use Joomla\Component\Esquemarico\Administrator\Plugin\PluginBaseEvento;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Integração com o JEvents (eventos do componente com_jevents).
 *
 * O parâmetro de URL "evid" identifica a repetição do evento (rp_id). Os dados
 * vêm das tabelas #__jevents_repetition / #__jevents_vevent / #__jevents_vevdetail
 * (modelo do JEvents 3.x). Verifique os nomes contra a versão instalada.
 */
final class Jevents extends PluginBaseEvento
{
    protected string $thingRequestIdName = 'evid';

    protected function fonteInstalada(): bool
    {
        return ExtensionHelper::componentIsEnabled('jevents');
    }

    /**
     * Esta integração tem uma única view.
     */
    protected function getView(): string
    {
        return 'event';
    }

    protected function passContext(): bool
    {
        return parent::passContext() && $this->getThingID() > 0;
    }

    /**
     * Payload do evento atual.
     */
    public function viewEvent(): ?array
    {
        $event = $this->carregarEvento($this->getThingID());

        if ($event === null) {
            return null;
        }

        return [
            'id'           => $this->getThingID(),
            'headline'     => $event->summary ?? '',
            'description'  => $event->description ?? '',
            'imagetext'    => EsquemaRicoHelper::getFirstImageFromString($event->description ?? ''),
            'startDate'    => $this->dataEvento($event->startrepeat ?? null),
            'endDate'      => $this->dataEvento($event->endrepeat ?? null),
            'locationName' => $event->location ?? '',
            'created'      => $event->created ?? null,
            'modified'     => $event->modified ?? null,
            'created_by'   => $event->created_by ?? 0,
            'publish_up'   => $event->created ?? null,
            'url'          => Uri::current(),
            'organizerName' => EsquemaRicoHelper::getSiteName(),
        ];
    }

    /**
     * Remove opções de mapeamento que não se aplicam a eventos.
     */
    public function onMapOptions(string $plugin, array &$options): void
    {
        if ($plugin !== $this->_name) {
            return;
        }

        MappingOptions::add($options, [
            'startDate'    => 'Data de início',
            'endDate'      => 'Data de término',
            'locationName' => 'Local',
        ], 'ESR_GROUP_INTEGRATION', 'gsd.item.');
    }

    /**
     * Carrega o evento (repetição + evento + detalhe).
     */
    private function carregarEvento(int $rpId): ?object
    {
        if ($rpId <= 0) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                'd.summary', 'd.description', 'd.location', 'd.contact',
                'd.created', 'd.modified', 'd.created_by',
                'r.startrepeat', 'r.endrepeat', 'v.catid',
            ])
            ->from($db->quoteName('#__jevents_repetition', 'r'))
            ->join('INNER', $db->quoteName('#__jevents_vevent', 'v') . ' ON v.ev_id = r.eventid')
            ->join('INNER', $db->quoteName('#__jevents_vevdetail', 'd') . ' ON d.evdet_id = v.detail_id')
            ->where('r.rp_id = :id')
            ->bind(':id', $rpId, ParameterType::INTEGER)
            ->setLimit(1);

        $db->setQuery($query);

        try {
            return $db->loadObject() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
