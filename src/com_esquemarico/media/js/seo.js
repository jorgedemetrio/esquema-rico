/**
 * Esquema Rico - Análise de SEO (contadores ao vivo)
 * Copyright (C) 2026 Esquema Rico. GPL v3 ou posterior.
 *
 * A análise completa (pontuação + verificações) é feita no servidor pelo
 * SeoAnalyzer e atualizada ao salvar. Este script apenas adiciona contadores
 * de caracteres ao vivo nos campos de título e meta descrição, e na palavra-
 * chave de foco, como dica imediata. Falha em silêncio se os campos não existirem.
 */
(function () {
    'use strict';

    function byName(name) {
        return document.querySelector('[name="' + name + '"]');
    }

    function badge(field, fn) {
        if (!field) { return; }
        var tag = document.createElement('span');
        tag.className = 'esr-seo-live';
        field.parentNode.appendChild(tag);
        var update = function () { tag.textContent = fn(field.value || ''); };
        field.addEventListener('input', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Faixa ideal de título: 40–60; meta descrição: 120–160.
        badge(byName('jform[title]'), function (v) {
            return v.length + ' / 40–60';
        });
        badge(byName('jform[metadata][metadesc]'), function (v) {
            return v.length + ' / 120–160';
        });
        badge(byName('jform[attribs][esr_focus_keyword]'), function (v) {
            return v.trim() ? '' : '⚠';
        });
    });
}());
