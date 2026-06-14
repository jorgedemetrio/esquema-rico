<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Plugin;

\defined('_JEXEC') or die;

/**
 * Base das integrações de e-commerce.
 *
 * Fornece auxiliares de preço, disponibilidade e avaliações que as integrações
 * concretas (VirtueMart, HikaShop, J2Store…) reutilizam ao montar o payload.
 */
abstract class PluginBaseProduto extends PluginBase
{
    /**
     * Constante de disponibilidade Schema.org conforme estoque.
     */
    protected function disponibilidade(bool $emEstoque): string
    {
        return $emEstoque ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
    }

    /**
     * Normaliza um preço para o formato Schema.org (2 casas, ponto decimal).
     */
    protected function formatarPreco(mixed $preco): string
    {
        $preco = str_replace(',', '.', (string) $preco);

        return number_format((float) $preco, 2, '.', '');
    }
}
