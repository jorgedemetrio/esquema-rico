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

/** @var \Joomla\Component\Esquemarico\Administrator\View\Config\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$tabs = [
    'sitename'    => 'ESR_CFG_SITENAME',
    'breadcrumbs' => 'ESR_CFG_BREADCRUMBS',
    'sitelinks'   => 'ESR_CFG_SITELINKS',
    'logo'        => 'ESR_CFG_LOGO',
    'social'      => 'ESR_CFG_SOCIAL',
    'business'    => 'ESR_CFG_BUSINESS',
    'sitemap'     => 'ESR_CFG_SITEMAP',
    'advanced'    => 'ESR_CFG_ADVANCED',
];
?>
<form action="<?php echo Route::_('index.php?option=com_esquemarico&view=config'); ?>"
      method="post" name="adminForm" id="config-form" class="form-validate">

    <?php echo HTMLHelper::_('uitab.startTabSet', 'cfgTab', ['active' => 'sitename']); ?>
    <?php foreach ($tabs as $name => $label) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'cfgTab', $name, Text::_($label)); ?>
            <div class="card card-body mb-3">
                <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                    <?php echo $field->renderField(); ?>
                <?php endforeach; ?>
            </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
