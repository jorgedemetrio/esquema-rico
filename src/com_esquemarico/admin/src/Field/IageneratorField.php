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

/**
 * Campo customizado para renderizar o gerador de matérias por IA.
 */
class IageneratorField extends FormField
{
    protected $type = 'Iagenerator';

    protected function getInput(): string
    {
        Factory::getApplication()->getLanguage()->load('com_esquemarico', JPATH_ADMINISTRATOR);

        // Token CSRF do Joomla.
        $token = HTMLHelper::_('form.token');

        $html   = [];
        $html[] = '<div class="esr-ia-generator mb-3">';
        $html[] = '  <button type="button" class="btn btn-success me-2" id="btnIaGerar">';
        $html[] = '    <span class="icon-refresh icon-spin d-none" id="loaderIaGerar" aria-hidden="true"></span> ';
        $html[] = '    ' . Text::_('COM_ESQUEMARICO_IA_BTN_GERAR');
        $html[] = '  </button>';
        $html[] = '  <button type="button" class="btn btn-secondary d-none" id="btnIaRegerar">';
        $html[] = '    ' . Text::_('COM_ESQUEMARICO_IA_BTN_REGERAR');
        $html[] = '  </button>';
        $html[] = '</div>';

        // Modal de Geração
        $html[] = '
<div class="modal fade" id="modalIaGerar" tabindex="-1" aria-labelledby="modalIaGerarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalIaGerarLabel">' . Text::_('COM_ESQUEMARICO_IA_MODAL_GERAR_TITLE') . '</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="iaTema" class="form-label">' . Text::_('COM_ESQUEMARICO_IA_FIELD_TEMA') . '</label>
          <textarea class="form-control" id="iaTema" rows="4" placeholder="' . Text::_('COM_ESQUEMARICO_IA_FIELD_TEMA_PLACEHOLDER') . '"></textarea>
        </div>
        <div class="mb-3">
          <label for="iaArquivo" class="form-label">' . Text::_('COM_ESQUEMARICO_IA_FIELD_FILE') . '</label>
          <input class="form-control" type="file" id="iaArquivo" accept=".txt,.csv,.md,.json,.xml">
          <div class="form-text">' . Text::_('COM_ESQUEMARICO_IA_FIELD_FILE_HELP') . '</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JCANCEL') . '</button>
        <button type="button" class="btn btn-primary" id="btnIaSubmitGerar">' . Text::_('COM_ESQUEMARICO_IA_BTN_ENVIAR') . '</button>
      </div>
    </div>
  </div>
</div>';

        // Modal de Refinamento (Regerar)
        $html[] = '
<div class="modal fade" id="modalIaRegerar" tabindex="-1" aria-labelledby="modalIaRegerarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalIaRegerarLabel">' . Text::_('COM_ESQUEMARICO_IA_MODAL_REGERAR_TITLE') . '</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="iaRefinamento" class="form-label">' . Text::_('COM_ESQUEMARICO_IA_FIELD_REFINAR') . '</label>
          <textarea class="form-control" id="iaRefinamento" rows="4" placeholder="' . Text::_('COM_ESQUEMARICO_IA_FIELD_REFINAR_PLACEHOLDER') . '"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JCANCEL') . '</button>
        <button type="button" class="btn btn-primary" id="btnIaSubmitRegerar">' . Text::_('COM_ESQUEMARICO_IA_BTN_ENVIAR') . '</button>
      </div>
    </div>
  </div>
</div>';

        // Script JavaScript
        $html[] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    var btnGerar = document.getElementById("btnIaGerar");
    var btnRegerar = document.getElementById("btnIaRegerar");
    var loader = document.getElementById("loaderIaGerar");
    var modalGerarEl = document.getElementById("modalIaGerar");
    var modalRegerarEl = document.getElementById("modalIaRegerar");
    
    if (!btnGerar || !modalGerarEl || !modalRegerarEl) return;

    var modalGerar = new bootstrap.Modal(modalGerarEl);
    var modalRegerar = new bootstrap.Modal(modalRegerarEl);
    
    var lastGeneratedData = null;

    btnGerar.addEventListener("click", function() {
        modalGerar.show();
    });

    btnRegerar.addEventListener("click", function() {
        modalRegerar.show();
    });

    document.getElementById("btnIaSubmitGerar").addEventListener("click", function() {
        var tema = document.getElementById("iaTema").value;
        var fileInput = document.getElementById("iaArquivo");
        
        if (!tema.trim() && (!fileInput.files || fileInput.files.length === 0)) {
            alert("' . Text::_('COM_ESQUEMARICO_IA_ALERT_EMPTY') . '");
            return;
        }

        modalGerar.hide();
        setLoading(true);

        var formData = new FormData();
        formData.append("tema", tema);
        if (fileInput.files.length > 0) {
            formData.append("arquivo", fileInput.files[0]);
        }
        
        // CSRF Token
        var tokenInput = document.querySelector(\'input[name="\' + ' . json_encode(substr(trim($token), 13, 32)) . ' + \'"]\');
        if (tokenInput) {
            formData.append(tokenInput.name, tokenInput.value);
        } else {
            formData.append("token", "1");
        }

        fetch("index.php?option=com_esquemarico&task=ia.gerar&format=json", {
            method: "POST",
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            setLoading(false);
            if (json.success && json.data) {
                preencherArtigo(json.data);
                lastGeneratedData = json.data;
                btnRegerar.classList.remove("d-none");
            } else {
                alert(json.message || "' . Text::_('COM_ESQUEMARICO_IA_ALERT_ERROR') . '");
            }
        })
        .catch(function(err) {
            setLoading(false);
            console.error(err);
            alert("' . Text::_('COM_ESQUEMARICO_IA_ALERT_ERROR') . '");
        });
    });

    document.getElementById("btnIaSubmitRegerar").addEventListener("click", function() {
        var refinamento = document.getElementById("iaRefinamento").value;
        
        if (!refinamento.trim()) {
            alert("' . Text::_('COM_ESQUEMARICO_IA_ALERT_EMPTY_REFINAR') . '");
            return;
        }

        modalRegerar.hide();
        setLoading(true);

        var formData = new FormData();
        formData.append("refinamento", refinamento);
        if (lastGeneratedData) {
            formData.append("contexto", JSON.stringify(lastGeneratedData));
        }

        fetch("index.php?option=com_esquemarico&task=ia.regerar&format=json", {
            method: "POST",
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            setLoading(false);
            if (json.success && json.data) {
                preencherArtigo(json.data);
                lastGeneratedData = json.data;
            } else {
                alert(json.message || "' . Text::_('COM_ESQUEMARICO_IA_ALERT_ERROR') . '");
            }
        })
        .catch(function(err) {
            setLoading(false);
            console.error(err);
            alert("' . Text::_('COM_ESQUEMARICO_IA_ALERT_ERROR') . '");
        });
    });

    function setLoading(val) {
        if (val) {
            btnGerar.disabled = true;
            btnRegerar.disabled = true;
            loader.classList.remove("d-none");
        } else {
            btnGerar.disabled = false;
            btnRegerar.disabled = false;
            loader.classList.add("d-none");
        }
    }

    function preencherArtigo(data) {
        if (data.title) {
            var titleEl = document.getElementById("jform_title");
            if (titleEl) titleEl.value = data.title;
        }
        if (data.alias) {
            var aliasEl = document.getElementById("jform_alias");
            if (aliasEl) aliasEl.value = data.alias;
        }
        if (data.metadesc) {
            var metadescEl = document.getElementById("jform_metadesc");
            if (metadescEl) metadescEl.value = data.metadesc;
        }
        if (data.metakey) {
            var metakeyEl = document.getElementById("jform_metakey");
            if (metakeyEl) metakeyEl.value = data.metakey;
        }
        
        // Editor de texto
        if (data.articletext) {
            if (window.tinymce && tinymce.get("jform_articletext")) {
                tinymce.get("jform_articletext").setContent(data.articletext);
            } else if (window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances["jform_articletext"]) {
                Joomla.editors.instances["jform_articletext"].setValue(data.articletext);
            } else {
                var txt = document.getElementById("jform_articletext");
                if (txt) txt.value = data.articletext;
            }
        }

        // Tags
        if (data.tags && Array.isArray(data.tags)) {
            var tagsEl = document.getElementById("jform_tags");
            if (tagsEl) {
                tagsEl.innerHTML = "";
                data.tags.forEach(function(t) {
                    var opt = document.createElement("option");
                    opt.value = t;
                    opt.textContent = t;
                    opt.selected = true;
                    tagsEl.appendChild(opt);
                });
                tagsEl.dispatchEvent(new Event("change"));
                if (tagsEl.tomselect) {
                    tagsEl.tomselect.sync();
                }
            }
        }
    }
});
</script>';

        return implode("\n", $html);
    }

    protected function getLabel(): string
    {
        return '';
    }
}
