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

\defined('_JEXEC') or die;

/**
 * Campo de horário de funcionamento (Negócio Local).
 *
 * Armazena uma estrutura { mode, <dia>: { enabled, start, end, start1, end1 } }
 * que o GeradorJsonLd::getOpeningHours() converte em OpeningHoursSpecification.
 * A chave do modo é "mode" (não "option") para não colidir com o mapeamento.
 */
class OpeningHoursField extends FormField
{
    protected $type = 'OpeningHours';

    private static bool $scriptAdded = false;

    /**
     * Dias da semana: chave interna => rótulo.
     *
     * @var array<string, string>
     */
    private const DIAS = [
        'monday'    => 'ESR_OH_MONDAY',
        'tuesday'   => 'ESR_OH_TUESDAY',
        'wednesday' => 'ESR_OH_WEDNESDAY',
        'thursday'  => 'ESR_OH_THURSDAY',
        'friday'    => 'ESR_OH_FRIDAY',
        'saturday'  => 'ESR_OH_SATURDAY',
        'sunday'    => 'ESR_OH_SUNDAY',
    ];

    protected function getInput(): string
    {
        $value = $this->value;

        if (\is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        if (!\is_array($value)) {
            $value = [];
        }

        $mode = (string) ($value['mode'] ?? '0');
        $name = $this->name;

        $this->injectScript();

        $html   = [];
        $html[] = '<div class="esr-oh" data-esr-oh>';

        // Seletor de modo.
        $html[] = '<select class="form-select esr-oh-mode" name="' . $name . '[mode]" data-esr-oh-mode>';
        foreach (['0' => 'ESR_OH_MODE_NONE', '1' => 'ESR_OH_MODE_ALWAYS', '2' => 'ESR_OH_MODE_SPECIFIC'] as $v => $label) {
            $html[] = '<option value="' . $v . '"' . ($mode === $v ? ' selected' : '') . '>' . htmlspecialchars(Text::_($label)) . '</option>';
        }
        $html[] = '</select>';

        // Tabela de dias (visível apenas no modo "horários específicos").
        $html[] = '<table class="table table-sm mt-2 esr-oh-table"' . ($mode === '2' ? '' : ' style="display:none;"') . '>';
        $html[] = '<thead><tr>'
            . '<th>' . Text::_('ESR_OH_DAY') . '</th>'
            . '<th>' . Text::_('JENABLED') . '</th>'
            . '<th>' . Text::_('ESR_OH_OPEN') . '</th>'
            . '<th>' . Text::_('ESR_OH_CLOSE') . '</th>'
            . '<th>' . Text::_('ESR_OH_OPEN2') . '</th>'
            . '<th>' . Text::_('ESR_OH_CLOSE2') . '</th>'
            . '</tr></thead><tbody>';

        foreach (self::DIAS as $key => $label) {
            $d        = $value[$key] ?? [];
            $enabled  = !empty($d['enabled']);
            $html[]   = '<tr>'
                . '<td>' . htmlspecialchars(Text::_($label)) . '</td>'
                . '<td><input type="checkbox" value="1" name="' . $name . '[' . $key . '][enabled]"' . ($enabled ? ' checked' : '') . '></td>'
                . '<td>' . $this->timeInput($name, $key, 'start', $d) . '</td>'
                . '<td>' . $this->timeInput($name, $key, 'end', $d) . '</td>'
                . '<td>' . $this->timeInput($name, $key, 'start1', $d) . '</td>'
                . '<td>' . $this->timeInput($name, $key, 'end1', $d) . '</td>'
                . '</tr>';
        }

        $html[] = '</tbody></table>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private function timeInput(string $name, string $day, string $field, array $d): string
    {
        $val = htmlspecialchars((string) ($d[$field] ?? ''), ENT_QUOTES, 'UTF-8');

        return '<input type="time" class="form-control form-control-sm" name="' . $name . '[' . $day . '][' . $field . ']" value="' . $val . '">';
    }

    private function injectScript(): void
    {
        if (self::$scriptAdded) {
            return;
        }

        self::$scriptAdded = true;

        $js = <<<'JS'
document.addEventListener('change', function (e) {
    if (!e.target.matches('[data-esr-oh-mode]')) { return; }
    var wrap = e.target.closest('[data-esr-oh]');
    var table = wrap ? wrap.querySelector('.esr-oh-table') : null;
    if (table) { table.style.display = (e.target.value === '2') ? '' : 'none'; }
});
JS;

        Factory::getApplication()->getDocument()->addScriptDeclaration($js);
    }
}
