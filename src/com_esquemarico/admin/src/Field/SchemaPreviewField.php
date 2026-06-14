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
use Joomla\Component\Esquemarico\Administrator\Engine\GeradorJsonLd;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;
use Joomla\Component\Esquemarico\Administrator\Schema\SchemaHelper;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Pré-visualiza e valida o JSON-LD do item em edição.
 *
 * Gera no servidor o JSON-LD sobre os dados SALVOS (passando pela mesma
 * preparação do Base usada na renderização), faz um lint estrutural e oferece
 * um atalho para o teste de Rich Results do Google. As SmartTags só são
 * resolvidas na página publicada, então aqui aparecem literais.
 */
class SchemaPreviewField extends FormField
{
    protected $type = 'SchemaPreview';

    protected function getInput(): string
    {
        Factory::getApplication()->getLanguage()->load('com_esquemarico', JPATH_ADMINISTRATOR);

        $json   = $this->gerarJsonLd();
        $html   = [];
        $html[] = '<div class="esr-preview">';

        if ($json === null) {
            $html[] = '<div class="alert alert-warning">' . Text::_('ESR_PREVIEW_EMPTY') . '</div>';
        } else {
            $issues = $this->validar($json);

            if ($issues === []) {
                $html[] = '<div class="alert alert-success">' . Text::_('ESR_PREVIEW_VALID') . '</div>';
            } else {
                $html[] = '<div class="alert alert-warning">' . Text::_('ESR_PREVIEW_WARNINGS') . '<ul class="mb-0 mt-2">';

                foreach ($issues as $issue) {
                    $html[] = '<li>' . htmlspecialchars($issue, ENT_QUOTES, 'UTF-8') . '</li>';
                }

                $html[] = '</ul></div>';
            }

            $pretty = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $html[] = '<pre class="esr-preview-code" style="max-height:420px;overflow:auto;background:#f8f9fa;padding:.75rem;border-radius:.375rem;">'
                . htmlspecialchars((string) $pretty, ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        $html[] = '<p class="text-muted small">' . Text::_('ESR_PREVIEW_HINT') . '</p>';
        $html[] = '<a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"'
            . ' href="https://search.google.com/test/rich-results">' . Text::_('ESR_PREVIEW_GOOGLE_TEST') . '</a>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Gera e decodifica o JSON-LD do item, ou null se não houver schema.
     *
     * @return array<string, mixed>|null
     */
    private function gerarJsonLd(): ?array
    {
        $type = (string) ($this->form?->getValue('contenttype') ?? '');

        if ($type === '') {
            return null;
        }

        try {
            $grupo   = $this->form?->getData()->get($type);
            $snippet = new Registry(\is_array($grupo) || \is_object($grupo) ? (array) $grupo : []);
            $snippet->set('contentType', $type);

            // Mesma preparação do frontend (PluginBase::preparePayload), porém sem
            // fonte de conteúdo no admin: resolve os modos de mapeamento
            // ({option, fixed, custom}) e as SmartTags em vez de enviar a estrutura
            // crua do MapField ao gerador. Campos mapeados ao conteúdo ficam vazios
            // aqui (o valor vem da página publicada).
            $payload = new Registry();
            $schema  = SchemaHelper::getInstance($type);
            $schema->onPayloadPrepare($payload);

            MappingOptions::prepare($snippet);
            $merged = (clone $payload)->merge($snippet, false);
            $merged = MappingOptions::replace($merged, $payload);

            $prepared = $schema->setData($merged)->get();
            $prepared->set('contentType', $type);

            $generated = (new GeradorJsonLd($prepared))->generate();
        } catch (\Throwable) {
            return null;
        }

        if (!\is_string($generated)) {
            return null;
        }

        $inner = preg_replace('#</?script[^>]*>#', '', $generated);
        $json  = json_decode(trim((string) $inner), true);

        return \is_array($json) ? $json : null;
    }

    /**
     * Lint estrutural mínimo do JSON-LD.
     *
     * @param  array<string, mixed>  $json
     * @return string[]
     */
    private function validar(array $json): array
    {
        $issues = [];

        if (!isset($json['@context'])) {
            $issues[] = Text::_('ESR_PREVIEW_NO_CONTEXT');
        }

        if (!isset($json['@type']) && !isset($json['@graph'])) {
            $issues[] = Text::_('ESR_PREVIEW_NO_TYPE');
        }

        if (\count($json) <= 2) {
            $issues[] = Text::_('ESR_PREVIEW_THIN');
        }

        return $issues;
    }

    protected function getLabel(): string
    {
        return '';
    }
}
