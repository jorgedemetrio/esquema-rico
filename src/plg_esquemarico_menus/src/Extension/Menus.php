<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_esquemarico_menus
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Esquemarico\Menus\Extension;

use Joomla\Component\Esquemarico\Administrator\Plugin\PluginBase;

\defined('_JEXEC') or die;

/**
 * Integração de itens de menu.
 *
 * Permite marcar manualmente qualquer página de menu com um tipo de schema —
 * útil para páginas que não pertencem a uma fonte específica (home, landing
 * pages, páginas institucionais). Os dados básicos vêm dos parâmetros do menu.
 */
final class Menus extends PluginBase
{
    /**
     * Item de menu ativo.
     */
    private ?object $menu = null;

    protected function passContext(): bool
    {
        $menuObj    = $this->app()->getMenu();
        $this->menu = $menuObj ? $menuObj->getActive() : null;

        return \is_object($this->menu) && isset($this->menu->id);
    }

    protected function getThingID(): int
    {
        return (int) ($this->menu->id ?? 0);
    }

    /**
     * Esta integração tem uma única view.
     */
    protected function getView(): string
    {
        return $this->_name;
    }

    /**
     * Payload do item de menu ativo.
     */
    public function viewMenus(): array
    {
        $params = $this->menu->getParams();

        return [
            'id'          => $this->menu->id,
            'alias'       => $this->menu->alias ?? '',
            'headline'    => $params->get('page_title') ?: $this->menu->title,
            'description' => $params->get('menu-meta_description'),
            'image'       => $params->get('menu_image'),
            'metakey'     => $params->get('menu-meta_keywords'),
            'metadesc'    => $params->get('menu-meta_description'),
        ];
    }

    /**
     * Itens de menu não têm autor/datas/avaliação de conteúdo: removemos essas
     * opções de mapeamento para não confundir.
     */
    public function onMapOptions(string $plugin, array &$options): void
    {
        if ($plugin !== $this->_name) {
            return;
        }

        $remover = [
            'user.name', 'user.email', 'gsd.item.created', 'gsd.item.publish_up',
            'gsd.item.modified', 'gsd.item.ratingValue', 'gsd.item.reviewCount',
            'gsd.item.introtext', 'gsd.item.fulltext',
        ];

        if (isset($options['ESR_GROUP_INTEGRATION'])) {
            foreach ($remover as $key) {
                unset($options['ESR_GROUP_INTEGRATION'][$key]);
            }
        }
    }
}
