<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Esquemarico\Administrator\View\Item\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$hasType = !empty($this->item->contenttype);

// Fieldsets de condições = todos exceto "geral" e "contenttype".
$conditionFieldsets = [];

foreach (array_keys($this->form->getFieldsets()) as $fsName) {
    if (!\in_array($fsName, ['geral', 'contenttype', 'preview'], true)) {
        $conditionFieldsets[] = $fsName;
    }
}
?>
<form action="<?php echo Route::_('index.php?option=com_esquemarico&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="item-form" class="form-validate">

    <div class="row">
        <div class="col-lg-10">
            <?php echo HTMLHelper::_('uitab.startTabSet', 'itemTab', ['active' => 'geral']); ?>

            <?php echo HTMLHelper::_('uitab.addTab', 'itemTab', 'geral', Text::_('ESR_ITEM_FIELDSET_GENERAL')); ?>
                <div class="card card-body mb-3">
                    <?php foreach ($this->form->getFieldset('geral') as $field) : ?>
                        <?php echo $field->renderField(); ?>
                    <?php endforeach; ?>
                </div>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>

            <?php echo HTMLHelper::_('uitab.addTab', 'itemTab', 'contenttype', Text::_('ESR_ITEM_FIELDSET_MAPPING')); ?>
                <div class="card card-body mb-3">
                    <?php if ($hasType) : ?>
                        <p class="text-muted"><?php echo Text::_('ESR_ITEM_MAPPING_INTRO'); ?></p>
                        <?php foreach ($this->form->getFieldset('contenttype') as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="alert alert-info"><?php echo Text::_('ESR_ITEM_SELECT_TYPE_FIRST'); ?></div>
                    <?php endif; ?>
                </div>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>

            <?php if ($hasType) : ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'itemTab', 'preview', Text::_('ESR_ITEM_FIELDSET_PREVIEW')); ?>
                    <div class="card card-body mb-3">
                        <?php foreach ($this->form->getFieldset('preview') as $field) : ?>
                            <?php echo $field->input; ?>
                        <?php endforeach; ?>
                    </div>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>
            <?php endif; ?>

            <?php if ($hasType && $conditionFieldsets) : ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'itemTab', 'conditions', Text::_('ESR_ITEM_FIELDSET_CONDITIONS')); ?>
                    <div class="card card-body mb-3">
                        <p class="text-muted"><?php echo Text::_('ESR_ITEM_CONDITIONS_INTRO'); ?></p>
                        <?php foreach ($conditionFieldsets as $fsName) : ?>
                            <fieldset class="mb-3">
                                <?php foreach ($this->form->getFieldset($fsName) as $field) : ?>
                                    <?php echo $field->renderField(); ?>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>
            <?php endif; ?>

            <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
