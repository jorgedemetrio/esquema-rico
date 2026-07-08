<?php
/**
 * @package     Esquema Rico
 * @subpackage  mod_esquemarico_adsense
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$app   = Factory::getApplication();
$lastQ = '';

if ($app->getInput()->getCmd('option') === 'com_esquemarico' && $app->getInput()->getCmd('view') === 'search') {
    $lastQ = $app->input->getString('q', '');
}
?>
<div class="mod-esquemarico-adsense">
    <form action="<?php echo Route::_('index.php?option=com_esquemarico&view=search'); ?>" method="get">
        <input type="hidden" name="option" value="com_esquemarico" />
        <input type="hidden" name="view" value="search" />
        <input type="hidden" name="cx" value="<?php echo htmlspecialchars($cx, ENT_QUOTES, 'UTF-8'); ?>" />
        
        <div class="input-group">
            <input type="text" name="q" class="form-control" 
                   placeholder="<?php echo Text::_('MOD_ESQUEMARICO_ADSENSE_SEARCH_PLACEHOLDER'); ?>" 
                   value="<?php echo htmlspecialchars($lastQ, ENT_QUOTES, 'UTF-8'); ?>" 
                   required aria-label="<?php echo Text::_('MOD_ESQUEMARICO_ADSENSE_SEARCH_PLACEHOLDER'); ?>" />
            <button class="btn btn-primary" type="submit">
                <?php echo Text::_('MOD_ESQUEMARICO_ADSENSE_SEARCH_BUTTON'); ?>
            </button>
        </div>
    </form>
</div>
