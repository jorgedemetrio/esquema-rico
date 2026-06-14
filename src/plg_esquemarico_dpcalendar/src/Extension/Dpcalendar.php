<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_esquemarico_dpcalendar
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Esquemarico\Dpcalendar\Extension;

use Esquemarico\Core\Extension as ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;
use Joomla\Component\Esquemarico\Administrator\Plugin\PluginBaseEvento;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Integração com o DPCalendar (eventos do componente com_dpcalendar).
 *
 * Os dados vêm de #__dpcalendar_events; a localização, de #__dpcalendar_locations.
 * Verifique os nomes contra a versão instalada.
 */
final class Dpcalendar extends PluginBaseEvento
{
    protected function fonteInstalada(): bool
    {
        return ExtensionHelper::componentIsEnabled('dpcalendar');
    }

    protected function passContext(): bool
    {
        return parent::passContext() && $this->getView() === 'event' && $this->getThingID() > 0;
    }

    public function viewEvent(): ?array
    {
        $event = $this->carregarEvento($this->getThingID());

        if ($event === null) {
            return null;
        }

        return [
            'id'           => $event->id,
            'alias'        => $event->alias ?? '',
            'headline'     => $event->title ?? '',
            'description'  => $event->description ?? '',
            'image'        => $this->resolverImagem($event),
            'imagetext'    => EsquemaRicoHelper::getFirstImageFromString($event->description ?? ''),
            'startDate'    => $this->dataEvento($event->start_date ?? null),
            'endDate'      => $this->dataEvento($event->end_date ?? null),
            'locationName' => $this->resolverLocal($event->location_ids ?? ''),
            'price'        => $event->price ?? '',
            'created'      => $event->created ?? null,
            'modified'     => $event->modified ?? null,
            'created_by'   => $event->created_by ?? 0,
            'publish_up'   => $event->created ?? null,
            'metadesc'     => $event->metadesc ?? '',
            'metakey'      => $event->metakey ?? '',
            'url'          => $event->url ?: Uri::current(),
            'organizerName' => EsquemaRicoHelper::getSiteName(),
        ];
    }

    public function onMapOptions(string $plugin, array &$options): void
    {
        if ($plugin !== $this->_name) {
            return;
        }

        MappingOptions::add($options, [
            'startDate'    => 'Data de início',
            'endDate'      => 'Data de término',
            'locationName' => 'Local',
            'price'        => 'Preço',
        ], 'ESR_GROUP_INTEGRATION', 'gsd.item.');
    }

    private function carregarEvento(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__dpcalendar_events'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->setLimit(1);

        $db->setQuery($query);

        try {
            return $db->loadObject() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extrai a imagem do campo JSON "images" (padrão com_content).
     */
    private function resolverImagem(object $event): ?string
    {
        if (empty($event->images)) {
            return null;
        }

        $images = new Registry($event->images);

        return $images->get('image_intro') ?: $images->get('image_full');
    }

    /**
     * Resolve o título da primeira localização a partir de location_ids.
     */
    private function resolverLocal(string $locationIds): ?string
    {
        $ids = array_filter(array_map('intval', explode(',', $locationIds)));

        if (!$ids) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $first = (int) reset($ids);
        $query = $db->getQuery(true)
            ->select('title')
            ->from($db->quoteName('#__dpcalendar_locations'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $first, ParameterType::INTEGER)
            ->setLimit(1);

        $db->setQuery($query);

        try {
            return $db->loadResult() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
