<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Esquemarico\Administrator\View\Items\HtmlView $this */

$app       = Factory::getApplication();
$user      = $app->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_esquemarico&view=items'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo \Joomla\CMS\Layout\LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="itemList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_ESQUEMARICO_SUBMENU_ITEMS'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th class="w-1 text-center"><?php echo Text::_('JSTATUS'); ?></th>
                                <th><?php echo Text::_('ESR_ITEM_TITLE'); ?></th>
                                <th><?php echo Text::_('ESR_ITEM_INTEGRATION'); ?></th>
                                <th><?php echo Text::_('ESR_ITEM_CONTENT_TYPE'); ?></th>
                                <th class="w-5 text-center"><?php echo Text::_('JFIELD_LANGUAGE_LABEL'); ?></th>
                                <th class="w-1 text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'items.', $user->authorise('core.edit.state', 'com_esquemarico')); ?>
                                    </td>
                                    <td>
                                        <?php if ($user->authorise('core.edit', 'com_esquemarico')) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_esquemarico&task=item.edit&id=' . (int) $item->id); ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->title); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item->note)) : ?>
                                            <div class="small text-muted"><?php echo $this->escape($item->note); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $this->escape($item->plugin); ?></td>
                                    <td><?php echo $this->escape($item->contenttype); ?></td>
                                    <td class="text-center"><?php echo $this->escape($item->language); ?></td>
                                    <td class="text-center"><?php echo (int) $item->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <input type="hidden" name="list[fullordering]" value="<?php echo $this->escape($listOrder . ' ' . $listDirn); ?>">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
