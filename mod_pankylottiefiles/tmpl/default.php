<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/**
 * @var \Joomla\Registry\Registry                                       $params
 * @var \stdClass                                                       $module
 * @var \Pnkr\Module\PankyLottieFiles\Site\Helper\PlayerHelper          $playerHelper
 * @var array                                                           $options
 * @var array                                                           $animations
 * @var string                                                          $display  stack, grid or slider
 * @var int                                                             $columns
 */

if (!$animations) {
    return;
}

$classes = 'mod-pankylottiefiles mod-pankylottiefiles--' . $display;

if ($columns > 2) {
    $classes .= ' mod-pankylottiefiles--wide';
}

if ($params->get('moduleclass_sfx')) {
    $classes .= ' ' . $params->get('moduleclass_sfx');
}

?>
<div id="mod-pankylottiefiles-<?php echo (int) $module->id; ?>" class="<?php echo htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'); ?>" style="--pankylottie-columns-setting: <?php echo $columns; ?>;">
    <div class="mod-pankylottiefiles__items"
        <?php if ($display === 'slider') : ?>
            role="region" tabindex="0" aria-label="<?php echo htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8'); ?>"
        <?php endif; ?>>
        <?php foreach ($animations as $animation) : ?>
            <?php echo $playerHelper->render($animation, $options); ?>
        <?php endforeach; ?>
    </div>
    <?php echo $module->content; ?>
</div>
