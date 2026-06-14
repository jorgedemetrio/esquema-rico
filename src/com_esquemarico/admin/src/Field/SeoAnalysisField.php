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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Esquemarico\Administrator\Seo\SeoAnalyzer;

\defined('_JEXEC') or die;

/**
 * Renderiza o painel de análise de SEO (estilo Yoast) do artigo em edição.
 *
 * A análise autoritativa é feita no servidor (SeoAnalyzer, testado) sobre os
 * dados salvos; um JS leve atualiza os contadores ao vivo. O painel se
 * reanalisa por completo ao salvar.
 */
class SeoAnalysisField extends FormField
{
    protected $type = 'SeoAnalysis';

    protected function getInput(): string
    {
        Factory::getApplication()->getLanguage()->load('com_esquemarico', JPATH_ADMINISTRATOR);

        $result = (new SeoAnalyzer())->analyze($this->coletarDados());

        HTMLHelper::_('stylesheet', 'com_esquemarico/seo.css', ['relative' => true]);
        HTMLHelper::_('script', 'com_esquemarico/seo.js', ['relative' => true]);

        $score  = (int) $result['score'];
        $rating = (string) $result['rating'];

        $html   = [];
        $html[] = '<div class="esr-seo" data-esr-seo>';

        // Medidor de pontuação.
        $html[] = '<div class="esr-seo-gauge esr-seo-' . $rating . '">';
        $html[] = '<span class="esr-seo-score" data-esr-seo-score>' . $score . '</span><span class="esr-seo-max">/100</span>';
        $html[] = '<div class="esr-seo-rating">' . Text::_('ESR_SEO_RATING_' . strtoupper($rating)) . '</div>';
        $html[] = '</div>';

        // Lista de verificações, agrupada por status (problemas primeiro).
        $ordem  = ['bad' => 0, 'ok' => 1, 'good' => 2];
        $checks = $result['checks'];
        usort($checks, static fn ($a, $b) => ($ordem[$a['status']] ?? 9) <=> ($ordem[$b['status']] ?? 9));

        $html[] = '<ul class="esr-seo-checks list-unstyled">';
        foreach ($checks as $c) {
            $msg = Text::sprintf($c['key'], ...array_values($c['params']));
            $html[] = '<li class="esr-seo-check esr-seo-' . $c['status'] . '">'
                . '<span class="esr-seo-dot" aria-hidden="true"></span> ' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html[] = '</ul>';

        $html[] = '<p class="esr-seo-hint text-muted small">' . Text::_('ESR_SEO_REANALYZE_HINT') . '</p>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    protected function getLabel(): string
    {
        return '';
    }

    /**
     * Reúne os dados do artigo a partir do formulário em edição.
     *
     * @return array<string, string>
     */
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
