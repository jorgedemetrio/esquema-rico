<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_esquemarico_virtuemart
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Esquemarico\Virtuemart\Extension;

use Esquemarico\Core\Extension as ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;
use Joomla\Component\Esquemarico\Administrator\Plugin\PluginBaseProduto;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Integração com o VirtueMart (produtos do componente com_virtuemart).
 *
 * Lê os dados do produto diretamente do banco. As tabelas multilíngues do
 * VirtueMart usam o sufixo do idioma (ex.: #__virtuemart_products_pt_br);
 * o sufixo é derivado do idioma ativo. Verifique os nomes de tabela/coluna
 * contra a versão do VirtueMart instalada.
 */
final class Virtuemart extends PluginBaseProduto
{
    protected string $thingRequestIdName = 'virtuemart_product_id';

    protected function fonteInstalada(): bool
    {
        return ExtensionHelper::componentIsEnabled('virtuemart');
    }

    protected function passContext(): bool
    {
        return parent::passContext() && $this->getThingID() > 0;
    }

    /**
     * View de detalhe do produto (com_virtuemart&view=productdetails).
     */
    public function viewProductDetails(): ?array
    {
        $id      = $this->getThingID();
        $product = $this->carregarProduto($id);

        if ($product === null) {
            return null;
        }

        $emEstoque = (int) ($product->product_in_stock ?? 0) > 0;

        return [
            'id'               => $product->virtuemart_product_id,
            'alias'            => $product->slug ?? '',
            'headline'         => $product->product_name ?? '',
            'description'      => $product->product_s_desc ?: ($product->product_desc ?? ''),
            'introtext'        => $product->product_s_desc ?? '',
            'fulltext'         => $product->product_desc ?? '',
            'image'            => $this->resolverImagem($id),
            'sku'              => $product->product_sku ?? '',
            'mpn'              => $product->product_mpn ?? '',
            'gtin'             => $product->product_gtin ?? '',
            'brand'            => $product->mf_name ?? '',
            'weight'           => $product->product_weight ?? '',
            'weightUnit'       => $product->product_weight_uom ?? '',
            'offerPrice'       => $this->formatarPreco($product->product_price ?? 0),
            'offerAvailability' => $this->disponibilidade($emEstoque),
            'metakey'          => $product->metakey ?? '',
            'metadesc'         => $product->metadesc ?? '',
            'url'              => Uri::current(),
        ];
    }

    /**
     * Acrescenta as opções de mapeamento específicas do produto.
     */
    public function onMapOptions(string $plugin, array &$options): void
    {
        if ($plugin !== $this->_name) {
            return;
        }

        MappingOptions::add($options, [
            'sku'               => 'SKU',
            'mpn'               => 'MPN',
            'gtin'              => 'GTIN',
            'brand'             => 'Marca',
            'weight'            => 'Peso',
            'offerPrice'        => 'Preço',
            'offerAvailability' => 'Disponibilidade',
        ], 'ESR_GROUP_INTEGRATION', 'gsd.item.');
    }

    /**
     * Carrega o produto: dados base + tradução + preço + fabricante (marca).
     */
    private function carregarProduto(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $suffix = strtolower(str_replace('-', '_', Factory::getApplication()->getLanguage()->getTag()));

        $langTable = '#__virtuemart_products_' . $suffix;
        $mfLang    = '#__virtuemart_manufacturers_' . $suffix;

        $query = $db->getQuery(true)
            ->select([
                'p.virtuemart_product_id', 'p.product_sku', 'p.product_mpn', 'p.product_gtin',
                'p.product_weight', 'p.product_weight_uom', 'p.product_in_stock',
                'l.product_name', 'l.product_s_desc', 'l.product_desc', 'l.slug',
                'l.metakey', 'l.metadesc',
                'pr.product_price',
                'mfl.mf_name',
            ])
            ->from($db->quoteName('#__virtuemart_products', 'p'))
            ->join('LEFT', $db->quoteName($langTable, 'l') . ' ON l.virtuemart_product_id = p.virtuemart_product_id')
            ->join('LEFT', $db->quoteName('#__virtuemart_product_prices', 'pr') . ' ON pr.virtuemart_product_id = p.virtuemart_product_id')
            ->join('LEFT', $db->quoteName('#__virtuemart_product_manufacturers', 'pm') . ' ON pm.virtuemart_product_id = p.virtuemart_product_id')
            ->join('LEFT', $db->quoteName($mfLang, 'mfl') . ' ON mfl.virtuemart_manufacturer_id = pm.virtuemart_manufacturer_id')
            ->where('p.virtuemart_product_id = :id')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->setLimit(1);

        $db->setQuery($query);

        try {
            return $db->loadObject() ?: null;
        } catch (\Throwable) {
            // Esquema de banco divergente da versão instalada.
            return null;
        }
    }

    /**
     * Resolve a imagem principal do produto via mídia do VirtueMart.
     */
    private function resolverImagem(int $id): ?string
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('m.file_url')
            ->from($db->quoteName('#__virtuemart_product_medias', 'pm'))
            ->join('INNER', $db->quoteName('#__virtuemart_medias', 'm') . ' ON m.virtuemart_media_id = pm.virtuemart_media_id')
            ->where('pm.virtuemart_product_id = :id')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->order('pm.ordering ASC')
            ->setLimit(1);

        $db->setQuery($query);

        try {
            $file = $db->loadResult();
        } catch (\Throwable) {
            $file = null;
        }

        return $file ? Uri::root() . ltrim((string) $file, '/') : null;
    }
}
