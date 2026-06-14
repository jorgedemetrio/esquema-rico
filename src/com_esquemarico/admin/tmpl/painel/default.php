<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Esquemarico\Administrator\View\Painel\HtmlView $this */
?>
<div class="esr-painel">
    <p class="lead"><?php echo Text::_('COM_ESQUEMARICO_PAINEL_INTRO'); ?></p>

    <?php if (!$this->pluginAtivo) : ?>
        <div class="alert alert-warning">
            <?php echo Text::_('COM_ESQUEMARICO_PAINEL_STATUS_PLUGIN_OFF'); ?>
        </div>
    <?php else : ?>
        <div class="alert alert-success">
            <?php echo Text::_('COM_ESQUEMARICO_PAINEL_STATUS_PLUGIN_OK'); ?>
        </div>
    <?php endif; ?>

    <div class="card-columns" style="display:flex; gap:1rem; flex-wrap:wrap;">
        <a class="btn btn-primary btn-lg" href="<?php echo Route::_('index.php?option=com_esquemarico&view=items'); ?>">
            <span class="icon-list" aria-hidden="true"></span>
            <?php echo Text::_('COM_ESQUEMARICO_PAINEL_ITEMS'); ?>
        </a>
        <a class="btn btn-secondary btn-lg" href="<?php echo Route::_('index.php?option=com_esquemarico&view=config&layout=edit'); ?>">
            <span class="icon-options" aria-hidden="true"></span>
            <?php echo Text::_('COM_ESQUEMARICO_PAINEL_CONFIG'); ?>
        </a>
    </div>
</div>
