<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;

\defined('_JEXEC') or die;

/**
 * Campo de mapeamento.
 *
 * Permite ligar uma propriedade do schema a uma origem (opção de mapeamento),
 * a um valor fixo, a uma expressão personalizada com SmartTags ou desativá-la.
 *
 * O valor é armazenado como estrutura { option, fixed, custom }, que o
 * MappingOptions::prepare() resolve em tempo de renderização.
 */
class MapField extends FormField
{
    protected $type = 'Map';

    /**
     * Garante que o JS de alternância seja injetado uma única vez.
     */
    private static bool $scriptAdded = false;

    protected function getInput(): string
    {
        $name = $this->name;
        $id   = $this->id;

        // Normaliza o valor para a estrutura esperada.
        $value = $this->value;

        if (\is_object($value)) {
            $value = (array) $value;
        }

        if (!\is_array($value)) {
            // Valor escalar (ex.: vindo do default) é tratado como opção de origem.
            $value = ['option' => (string) $value, 'fixed' => '', 'custom' => ''];
        }

        $option = (string) ($value['option'] ?? '');
        $fixed  = (string) ($value['fixed'] ?? '');
        $custom = (string) ($value['custom'] ?? '');

        $this->injectScript();

        $html   = [];
        $html[] = '<div class="esr-map" data-esr-map>';
        $html[] = $this->buildSelect($name, $id, $option);
        $html[] = '<input type="text" class="form-control esr-map-fixed mt-1" name="' . $name . '[fixed]" value="'
            . htmlspecialchars($fixed, ENT_QUOTES, 'UTF-8') . '" placeholder="' . Text::_('ESR_MAP_FIXED_PLACEHOLDER') . '">';
        $html[] = '<input type="text" class="form-control esr-map-custom mt-1" name="' . $name . '[custom]" value="'
            . htmlspecialchars($custom, ENT_QUOTES, 'UTF-8') . '" placeholder="{gsd.item.headline}">';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Monta o <select> com as opções especiais e os grupos de origem.
     */
    private function buildSelect(string $name, string $id, string $selected): string
    {
        $specials = [
            'fixed'      => Text::_('ESR_MAP_MODE_FIXED'),
            '_custom_'   => Text::_('ESR_MAP_MODE_CUSTOM'),
            '_disabled_' => Text::_('ESR_MAP_MODE_DISABLED'),
        ];

        $out   = [];
        $out[] = '<select id="' . $id . '" class="form-select esr-map-option" name="' . $name . '[option]" data-esr-map-option>';

        $out[] = '<optgroup label="' . Text::_('ESR_MAP_MODE_GROUP') . '">';
        foreach ($specials as $val => $label) {
            $out[] = '<option value="' . $val . '"' . ($selected === $val ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
        }
        $out[] = '</optgroup>';

        foreach ($this->getMappingOptions() as $group => $options) {
            $out[] = '<optgroup label="' . htmlspecialchars(Text::_($group)) . '">';

            foreach ($options as $val => $label) {
                $out[] = '<option value="' . htmlspecialchars((string) $val) . '"' . ($selected === $val ? ' selected' : '') . '>'
                    . htmlspecialchars(Text::_($label)) . '</option>';
            }

            $out[] = '</optgroup>';
        }

        $out[] = '</select>';

        return implode('', $out);
    }

    /**
     * Reúne as opções de mapeamento (base + contribuições das integrações).
     *
     * @return array<string, array<string, string>>
     */
    protected function getMappingOptions(): array
    {
        $options = MappingOptions::$options;

        $plugin = (string) ($this->form?->getValue('plugin') ?? '');

        if ($plugin !== '') {
            EsquemaRicoHelper::event('onMapOptions', [$plugin, &$options]);
        }

        return $options;
    }

    /**
     * Injeta uma vez o JS que mostra/oculta os campos fixo/personalizado.
     */
    private function injectScript(): void
    {
        if (self::$scriptAdded) {
            return;
        }

        self::$scriptAdded = true;

        $js = <<<'JS'
document.addEventListener('change', function (e) {
    if (!e.target.matches('[data-esr-map-option]')) { return; }
    esrToggleMap(e.target);
});
function esrToggleMap(select) {
    var wrap = select.closest('[data-esr-map]');
    if (!wrap) { return; }
    var v = select.value;
    wrap.querySelector('.esr-map-fixed').style.display  = (v === 'fixed') ? '' : 'none';
    wrap.querySelector('.esr-map-custom').style.display = (v === '_custom_') ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-esr-map-option]').forEach(esrToggleMap);
});
JS;

        Factory::getApplication()->getDocument()->addScriptDeclaration($js);
    }
}
