<?php
/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$app = Factory::getApplication();
$q   = $app->input->getString('q', '');
$cx  = $app->input->getString('cx', '');
?>
<div class="com-esquemarico-search container my-5">
    <h1 class="mb-4"><?php echo Text::_('COM_ESQUEMARICO_SEARCH_TITLE'); ?></h1>
    
    <form action="<?php echo Route::_('index.php?option=com_esquemarico&view=search'); ?>" method="get" class="mb-5">
        <input type="hidden" name="option" value="com_esquemarico" />
        <input type="hidden" name="view" value="search" />
        <input type="hidden" name="cx" value="<?php echo htmlspecialchars($cx, ENT_QUOTES, 'UTF-8'); ?>" />
        
        <div class="input-group input-group-lg">
            <input type="text" name="q" class="form-control" 
                   placeholder="<?php echo Text::_('COM_ESQUEMARICO_SEARCH_PLACEHOLDER'); ?>" 
                   value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" 
                   required aria-label="<?php echo Text::_('COM_ESQUEMARICO_SEARCH_PLACEHOLDER'); ?>" />
            <button class="btn btn-primary" type="submit">
                <?php echo Text::_('COM_ESQUEMARICO_SEARCH_BUTTON'); ?>
            </button>
        </div>
    </form>

    <?php if (!empty($cx) && !empty($q)) : ?>
        <div class="adsense-search-results card card-body shadow-sm">
            <script async src="https://cse.google.com/cse.js?cx=<?php echo htmlspecialchars($cx, ENT_QUOTES, 'UTF-8'); ?>"></script>
            <div class="gcse-searchresults-only" data-queryParameterName="q"></div>
        </div>
    <?php elseif (empty($cx)) : ?>
        <div class="alert alert-warning">
            <?php echo Text::_('COM_ESQUEMARICO_SEARCH_MISSING_CX'); ?>
        </div>
    <?php endif; ?>
</div>
