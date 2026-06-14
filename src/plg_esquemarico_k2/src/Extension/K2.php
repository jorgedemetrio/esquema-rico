<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_esquemarico_k2
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Esquemarico\K2\Extension;

use Esquemarico\Core\Extension as ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Plugin\PluginBaseArtigo;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Integração com o K2 (itens de conteúdo K2 do componente com_k2).
 */
final class K2 extends PluginBaseArtigo
{
    protected function fonteInstalada(): bool
    {
        return ExtensionHelper::componentIsEnabled('k2');
    }

    protected function passContext(): bool
    {
        if (!$this->app()->getInput()->getInt('id')) {
            return false;
        }

        return parent::passContext() && $this->getView() === 'item';
    }

    /**
     * Payload do item K2 atual.
     */
    public function viewItem(): ?array
    {
        $id   = $this->getThingID();
        $item = $this->carregarItem($id);

        if ($item === null) {
            return null;
        }

        $texto = !empty($item->introtext) ? $item->introtext : ($item->fulltext ?? '');

        return [
            'id'               => $item->id,
            'alias'            => $item->alias,
            'headline'         => $item->title,
            'description'      => $texto,
            'introtext'        => $item->introtext,
            'fulltext'         => $item->fulltext,
            'image'            => $this->resolverImagem($item),
            'imagetext'        => EsquemaRicoHelper::getFirstImageFromString(($item->introtext ?? '') . ($item->fulltext ?? '')),
            'created_by'       => $item->created_by,
            'created_by_alias' => $item->created_by_alias,
            'created'          => $item->created,
            'modified'         => $item->modified ?? $item->created,
            'publish_up'       => $item->publish_up,
            'publish_down'     => $item->publish_down,
            'metakey'          => $item->metakey,
            'metadesc'         => $item->metadesc,
            'category.id'      => $item->catid,
        ];
    }

    /**
     * Carrega o item K2 do banco (#__k2_items).
     */
    private function carregarItem(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__k2_items'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('published') . ' = 1')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->setLimit(1);

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }

    /**
     * Resolve a imagem do item (cache do K2 ou primeira imagem do texto).
     */
    private function resolverImagem(object $item): ?string
    {
        // O K2 gera miniaturas em media/k2/items/cache/<md5('Image'.id)>_XL.jpg.
        $hash = md5('Image' . $item->id);
        $rel  = 'media/k2/items/cache/' . $hash . '_XL.jpg';

        if (is_file(JPATH_ROOT . '/' . $rel)) {
            return Uri::root() . $rel;
        }

        return EsquemaRicoHelper::getFirstImageFromString(($item->introtext ?? '') . ($item->fulltext ?? ''));
    }
}
