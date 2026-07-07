<?php
/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Esquemarico\Administrator\Seo\SeoAnalyzer;

/**
 * Campo customizado para renderizar a análise estilo AIOSEO.
 */
class AioseoAnalysisField extends FormField
{
    protected $type = 'AioseoAnalysis';

    protected function getInput(): string
    {
        Factory::getApplication()->getLanguage()->load('com_esquemarico', JPATH_ADMINISTRATOR);

        $analyzer = new SeoAnalyzer();
        $dados    = $this->coletarDados();
        $result   = $analyzer->analyze($dados);
        $leg      = $analyzer->readability($dados);

        HTMLHelper::_('stylesheet', 'com_esquemarico/seo.css', ['relative' => true]);
        HTMLHelper::_('script', 'com_esquemarico/seo.js', ['relative' => true]);

        // Pontuação Geral AIOSEO (peso 60% SEO, 40% Legibilidade)
        $score  = (int) round(($result['score'] * 0.6) + ($leg['score'] * 0.4));
        $rating = $score >= 70 ? 'good' : ($score >= 45 ? 'ok' : 'bad');

        // Separando as checagens por áreas do AIOSEO
        $basicChecks   = [];
        $titleChecks   = [];
        $contentChecks = [];

        $basicIds   = ['fk_set', 'fk_in_metadesc', 'fk_in_url', 'fk_in_first_paragraph', 'fk_density', 'content_length', 'metadesc', 'metakeywords'];
        $titleIds   = ['title_length', 'fk_in_title'];
        $contentIds = ['flesch', 'sentence_length', 'paragraph_length', 'subheading_distribution', 'images_alt', 'links', 'subheadings', 'fk_in_subheading'];

        foreach ($result['checks'] as $c) {
            if (\in_array($c['id'], $basicIds, true)) {
                $basicChecks[] = $c;
            } elseif (\in_array($c['id'], $titleIds, true)) {
                $titleChecks[] = $c;
            } else {
                $contentChecks[] = $c;
            }
        }

        foreach ($leg['checks'] as $c) {
            if (\in_array($c['id'], $contentIds, true)) {
                $contentChecks[] = $c;
            } else {
                $basicChecks[] = $c;
            }
        }

        // Ordenação por status (bad -> ok -> good)
        $ordem  = ['bad' => 0, 'ok' => 1, 'good' => 2];
        $sortFn = static fn ($a, $b) => ($ordem[$a['status']] ?? 9) <=> ($ordem[$b['status']] ?? 9);
        usort($basicChecks, $sortFn);
        usort($titleChecks, $sortFn);
        usort($contentChecks, $sortFn);

        $html   = [];
        $html[] = '<div class="esr-seo esr-aioseo" data-esr-seo>';
        
        $html[] = '<style>
            .esr-aioseo .esr-seo-rating-aioseo { font-weight: bold; color: #495057; font-size: 1.1rem; }
            .esr-aioseo .accordion-button:not(.collapsed) { background-color: #f0f4f8; color: #0f4c81; }
            .esr-aioseo .accordion-button { font-weight: 600; color: #495057; }
        </style>';

        $html[] = '<h4 class="esr-seo-section text-primary mb-3">' . Text::_('ESR_AIOSEO_TITLE') . '</h4>';

        // Medidor de pontuação geral estilo circular
        $html[] = '<div class="esr-seo-gauge esr-seo-' . $rating . ' mb-4">';
        $html[] = '<span class="esr-seo-score">' . $score . '</span><span class="esr-seo-max">/100</span>';
        $html[] = '<div class="esr-seo-rating-aioseo">' . Text::_('ESR_AIOSEO_RATING_' . strtoupper($rating)) . '</div>';
        $html[] = '</div>';

        // Seções Accordion Bootstrap 5
        $html[] = '<div class="accordion" id="aioseoAccordion">';

        // 1. SEO Básico
        $goodBasicCount = \count(array_filter($basicChecks, fn($c) => $c['status'] === 'good'));
        $html[] = '  <div class="accordion-item mb-2 border rounded shadow-sm">';
        $html[] = '    <h2 class="accordion-header" id="headingBasic">';
        $html[] = '      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBasic" aria-expanded="true" aria-controls="collapseBasic">';
        $html[] = '        ' . Text::_('ESR_AIOSEO_SEC_BASIC') . ' (' . $goodBasicCount . '/' . \count($basicChecks) . ')';
        $html[] = '      </button>';
        $html[] = '    </h2>';
        $html[] = '    <div id="collapseBasic" class="accordion-collapse collapse show" aria-labelledby="headingBasic" data-bs-parent="#aioseoAccordion">';
        $html[] = '      <div class="accordion-body">';
        $html[] = $this->renderChecksList($basicChecks);
        $html[] = '      </div>';
        $html[] = '    </div>';
        $html[] = '  </div>';

        // 2. Legibilidade do Título
        $goodTitleCount = \count(array_filter($titleChecks, fn($c) => $c['status'] === 'good'));
        $html[] = '  <div class="accordion-item mb-2 border rounded shadow-sm">';
        $html[] = '    <h2 class="accordion-header" id="headingTitle">';
        $html[] = '      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTitle" aria-expanded="false" aria-controls="collapseTitle">';
        $html[] = '        ' . Text::_('ESR_AIOSEO_SEC_TITLE') . ' (' . $goodTitleCount . '/' . \count($titleChecks) . ')';
        $html[] = '      </button>';
        $html[] = '    </h2>';
        $html[] = '    <div id="collapseTitle" class="accordion-collapse collapse" aria-labelledby="headingTitle" data-bs-parent="#aioseoAccordion">';
        $html[] = '      <div class="accordion-body">';
        $html[] = $this->renderChecksList($titleChecks);
        $html[] = '      </div>';
        $html[] = '    </div>';
        $html[] = '  </div>';

        // 3. Legibilidade do Conteúdo
        $goodContentCount = \count(array_filter($contentChecks, fn($c) => $c['status'] === 'good'));
        $html[] = '  <div class="accordion-item mb-2 border rounded shadow-sm">';
        $html[] = '    <h2 class="accordion-header" id="headingContent">';
        $html[] = '      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContent" aria-expanded="false" aria-controls="collapseContent">';
        $html[] = '        ' . Text::_('ESR_AIOSEO_SEC_CONTENT') . ' (' . $goodContentCount . '/' . \count($contentChecks) . ')';
        $html[] = '      </button>';
        $html[] = '    </h2>';
        $html[] = '    <div id="collapseContent" class="accordion-collapse collapse" aria-labelledby="headingContent" data-bs-parent="#aioseoAccordion">';
        $html[] = '      <div class="accordion-body">';
        $html[] = $this->renderChecksList($contentChecks);
        $html[] = '      </div>';
        $html[] = '    </div>';
        $html[] = '  </div>';

        $html[] = '</div>'; // .accordion

        $html[] = '<p class="esr-seo-hint text-muted small mt-3">' . Text::_('ESR_SEO_REANALYZE_HINT') . '</p>';
        $html[] = '</div>'; // .esr-aioseo

        return implode("\n", $html);
    }

    private function renderChecksList(array $checks): string
    {
        $html = ['<ul class="esr-seo-checks list-unstyled mb-0">'];

        foreach ($checks as $c) {
            $msg    = Text::sprintf($c['key'], ...array_values($c['params']));
            $html[] = '<li class="esr-seo-check esr-seo-' . $c['status'] . ' mb-2">'
                . '<span class="esr-seo-dot" aria-hidden="true"></span> ' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</li>';
        }

        $html[] = '</ul>';

        return implode("\n", $html);
    }

    protected function getLabel(): string
    {
        return '';
    }

    private function coletarDados(): array
    {
        $form = $this->form;
        $text = (string) $form?->getValue('articletext');

        if ($text === '') {
            $text = trim((string) $form?->getValue('introtext') . ' ' . (string) $form?->getValue('fulltext'));
        }

        return [
            'title'         => (string) $form?->getValue('title'),
            'alias'         => (string) $form?->getValue('alias'),
            'text'          => $text,
            'metadesc'      => (string) $form?->getValue('metadesc', 'metadata'),
            'metakey'       => (string) $form?->getValue('metakey', 'metadata'),
            'focus_keyword' => (string) $form?->getValue('esr_focus_keyword', 'attribs'),
        ];
    }
}
