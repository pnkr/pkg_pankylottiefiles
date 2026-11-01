<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Override in templates/<template>/html/layouts/pankylottie/player.php
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * @var array $displayData
 * @var array $animation  See PlayerHelper::getAnimation()
 * @var array $options    See PlayerHelper::getPlayerOptions()
 */
['animation' => $animation, 'options' => $options] = $displayData;

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$trigger = $options['trigger'];

// A link takes over clicks, so a click trigger falls back to autoplay
if ($animation['link'] !== '' && $trigger === 'click') {
    $trigger = 'autoplay';
}

$label    = $animation['decorative'] ? '' : $animation['alt'];
$controls = $options['controls'] && !\in_array($trigger, ['click', 'scroll'], true);

$config = [
    'trigger'       => $trigger,
    'hoverOut'      => $options['hoverOut'],
    'reducedMotion' => $options['reducedMotion'],
    'fit'           => $options['fit'],
    'segment'       => $animation['segment'],
];

$style = '--pankylottie-width: ' . $options['width'] . '; --pankylottie-height: ' . $options['height'] . ';';

if ($options['ratio'] !== '') {
    $style .= ' --pankylottie-ratio: ' . $options['ratio'] . ';';
}

$classes = 'pankylottie pankylottie--' . $trigger . ($options['ratio'] !== '' ? ' pankylottie--ratio' : '');

$playLabel  = $label !== '' ? Text::sprintf('MOD_PANKYLOTTIEFILES_PLAY_NAMED', $label) : Text::_('MOD_PANKYLOTTIEFILES_PLAY');
$pauseLabel = $label !== '' ? Text::sprintf('MOD_PANKYLOTTIEFILES_PAUSE_NAMED', $label) : Text::_('MOD_PANKYLOTTIEFILES_PAUSE');

?>
<div class="<?php echo $classes; ?>" style="<?php echo $e($style); ?>" data-pankylottie="<?php echo $e(json_encode($config)); ?>">
    <?php if ($animation['link'] !== '') : ?>
        <a class="pankylottie__stage" href="<?php echo $e($animation['link']); ?>"
            <?php echo $animation['target'] === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>
            <?php echo $label !== '' ? 'aria-label="' . $e($label) . '"' : ''; ?>>
    <?php elseif ($trigger === 'click') : ?>
        <button type="button" class="pankylottie__stage" aria-label="<?php echo $e($playLabel); ?>"
            data-label-play="<?php echo $e($playLabel); ?>" data-label-pause="<?php echo $e($pauseLabel); ?>">
    <?php else : ?>
        <div class="pankylottie__stage" <?php echo $label !== '' ? 'role="img" aria-label="' . $e($label) . '"' : 'aria-hidden="true"'; ?>>
    <?php endif; ?>
        <template class="pankylottie__template">
            <dotlottie-wc class="pankylottie__player"
                src="<?php echo $e($animation['src']); ?>"
                speed="<?php echo $options['speed']; ?>"
                mode="<?php echo $options['mode']; ?>"
                renderconfig="<?php echo $e(json_encode(['autoResize' => true])); ?>"
                <?php echo $options['loop'] ? 'loop' : ''; ?>
                <?php echo $animation['segment'] ? 'segment="' . $e(json_encode($animation['segment'])) . '"' : ''; ?>
                <?php echo $animation['marker'] !== '' ? 'marker="' . $e($animation['marker']) . '"' : ''; ?>
                <?php echo $animation['theme'] !== '' ? 'themeid="' . $e($animation['theme']) . '"' : ''; ?>
                <?php echo $options['background'] !== '' ? 'backgroundcolor="' . $options['background'] . '"' : ''; ?>
            ></dotlottie-wc>
        </template>
    <?php if ($animation['link'] !== '') : ?>
        </a>
    <?php elseif ($trigger === 'click') : ?>
        </button>
    <?php else : ?>
        </div>
    <?php endif; ?>
    <?php if ($controls) : ?>
        <button type="button" class="pankylottie__toggle" hidden aria-label="<?php echo $e($pauseLabel); ?>"
            data-label-play="<?php echo $e($playLabel); ?>" data-label-pause="<?php echo $e($pauseLabel); ?>">
            <svg class="pankylottie__icon-pause" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M4 2h3v12H4zM9 2h3v12H9z"/></svg>
            <svg class="pankylottie__icon-play" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M4 2l10 6-10 6z"/></svg>
        </button>
    <?php endif; ?>
</div>
