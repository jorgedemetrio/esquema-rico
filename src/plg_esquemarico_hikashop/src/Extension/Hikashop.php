<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_esquemarico_hikashop
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Esquemarico\Hikashop\Extension;

use Esquemarico\Core\Extension as ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;
use Joomla\Component\Esquemarico\Administrator\Plugin\PluginBaseProduto;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Integração com o HikaShop (produtos do componente com_hikashop).
 *
 * O HikaShop usa ctrl=product&task=show com o id em "cid" (ou "id"). Os dados
 * vêm de #__hikashop_product / #__hikashop_price / #__hikashop_file.
 * Verifique os nomes contra a versão instalada.
 */
final class Hikashop extends PluginBaseProduto
{
    protected function fonteInstalada(): bool
    {
        return ExtensionHelper::componentIsEnabled('hikashop');
    }

    protected function getThingID(): int
    {
        $input = $this->app()->getInput();
        $cid   = $input->get('cid', null, 'raw');

        if (\is_array($cid)) {
            $cid = reset($cid);
        }

        return (int) ($cid ?: $input->getInt('id'));
    }

    protected function getView(): string
    {
        $input = $this->app()->getInput();

        return ($input->getCmd('ctrl') === 'product' && $input->getCmd('task') === 'show') ? 'product' : '';
    }

    protected function passContext(): bool
    {
        return parent::passContext() && $this->getView() === 'product' && $this->getThingID() > 0;
    }

    public function viewProduct(): ?array
    {
        $id      = $this->getThingID();
        $product = $this->carregarProduto($id);

        if ($product === null) {
            return null;
        }

        $emEstoque = (int) ($product->product_quantity ?? -1) !== 0;

        return [
            'id'                => $product->product_id,
            'headline'          => $product->product_name ?? '',
            'description'       => $product->product_description ?? '',
            'image'             => $this->resolverImagem($id),
            'sku'               => $product->product_code ?? '',
            'weight'            => $product->product_weight ?? '',
            'weightUnit'        => $product->product_weight_unit ?? '',
            'offerPrice'        => $this->formatarPreco($product->price_value ?? 0),
            'offerAvailability' => $this->disponibilidade($emEstoque),
            'metadesc'          => $product->product_meta_description ?? '',
            'metakey'           => $product->product_keywords ?? '',
            'url'               => Uri::current(),
        ];
    }

    public function onMapOptions(string $plugin, array &$options): void
    {
        if ($plugin !== $this->_name) {
            return;
        }

        MappingOptions::add($options, [
            'sku'               => 'SKU',
            'weight'            => 'Peso',
            'offerPrice'        => 'Preço',
            'offerAvailability' => 'Disponibilidade',
        ], 'ESR_GROUP_INTEGRATION', 'gsd.item.');
    }

    private function carregarProduto(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                'p.product_id', 'p.product_code', 'p.product_name', 'p.product_description',
                'p.product_quantity', 'p.product_weight', 'p.product_weight_unit',
                'p.product_meta_description', 'p.product_keywords',
                'pr.price_value',
            ])
            ->from($db->quoteName('#__hikashop_product', 'p'))
            ->join('LEFT', $db->quoteName('#__hikashop_price', 'pr') . ' ON pr.price_product_id = p.product_id')
            ->where('p.product_id = :id')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->setLimit(1);

        $db->setQuery($query);

        try {
            return $db->loadObject() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolverImagem(int $id): ?string
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('f.file_path')
            ->from($db->quoteName('#__hikashop_file', 'f'))
            ->where('f.file_ref_id = :id')
            ->where('f.file_type = ' . $db->quote('product'))
            ->bind(':id', $id, ParameterType::INTEGER)
            ->order('f.file_ordering ASC')
            ->setLimit(1);

        $db->setQuery($query);

        try {
            $path = $db->loadResult();
        } catch (\Throwable) {
            $path = null;
        }

        if (!$path) {
            return null;
        }

        // O HikaShop guarda caminhos relativos à pasta de upload (geralmente media/com_hikashop/upload).
        return Uri::root() . 'media/com_hikashop/upload/' . ltrim((string) $path, '/');
    }
}
