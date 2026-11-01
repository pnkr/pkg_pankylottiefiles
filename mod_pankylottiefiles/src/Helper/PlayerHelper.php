<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Pnkr\Module\PankyLottieFiles\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Normalises player settings and renders animations. Shared by the module
 * and the Panky Lottie content plugin.
 *
 * @since  2.0.0
 */
final class PlayerHelper
{
    public const TRIGGERS = ['autoplay', 'viewport', 'hover', 'click', 'scroll'];

    public const MODES = ['forward', 'reverse', 'bounce', 'reverse-bounce'];

    public const FITS = ['contain', 'cover', 'fill', 'fit-width', 'fit-height', 'none'];

    public const HOVER_OUT = ['stop', 'pause', 'reverse'];

    private static bool $languageLoaded = false;

    /**
     * Returns the validated player options shared by every animation of one instance.
     *
     * @param   Registry  $params  Module or plugin params
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function getPlayerOptions(Registry $params): array
    {
        return [
            'trigger'       => $this->pick($params->get('trigger'), self::TRIGGERS),
            'hoverOut'      => $this->pick($params->get('hover_out'), self::HOVER_OUT),
            'loop'          => (bool) $params->get('loop', 1),
            'speed'         => min(10.0, max(0.1, (float) $params->get('speed', 1))),
            'mode'          => $this->pick($params->get('mode'), self::MODES),
            'controls'      => (bool) $params->get('controls', 1),
            'reducedMotion' => (bool) $params->get('reduced_motion', 1),
            'width'         => $this->cssLength($params->get('width'), '300px'),
            'height'        => $this->cssLength($params->get('height'), '300px'),
            'ratio'         => $this->ratio($params->get('aspect_ratio')),
            'fit'           => $this->pick($params->get('fit'), self::FITS),
            'background'    => $this->hexColor($params->get('backgroundcolor')),
        ];
    }

    /**
     * Returns the validated animations, skipping rows without a source.
     *
     * @param   iterable|object  $rows  Subform rows
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function getAnimations(iterable|object $rows): array
    {
        $animations = [];

        foreach ((array) $rows as $row) {
            $animation = $this->getAnimation((array) $row);

            if ($animation !== null) {
                $animations[] = $animation;
            }
        }

        return $animations;
    }

    /**
     * Returns one validated animation, or null when it has no usable source.
     *
     * @param   array  $row  Animation settings
     *
     * @return  array|null
     *
     * @since   2.0.0
     */
    public function getAnimation(array $row): ?array
    {
        if (($row['source'] ?? 'file') === 'url') {
            $src = (string) ($row['url'] ?? '');
        } else {
            // Media field values may carry a "#joomlaImage://..." suffix
            $src = explode('#', (string) ($row['file'] ?? ''), 2)[0];
        }

        $src = $this->resolveUrl($src);

        if ($src === '') {
            return null;
        }

        $start = $row['segment_start'] ?? '';
        $end   = $row['segment_end'] ?? '';

        return [
            'src'        => $src,
            'alt'        => trim((string) ($row['alt'] ?? '')),
            'decorative' => (bool) ($row['decorative'] ?? false),
            'link'       => $this->resolveUrl((string) ($row['link'] ?? '')),
            'target'     => ($row['link_target'] ?? '') === '_blank' ? '_blank' : '',
            'segment'    => is_numeric($start) && is_numeric($end) && (int) $end > (int) $start
                ? [max(0, (int) $start), (int) $end]
                : null,
            'marker'     => trim((string) ($row['marker'] ?? '')),
            'theme'      => trim((string) ($row['theme'] ?? '')),
        ];
    }

    /**
     * Renders one animation through the overridable "pankylottie.player" layout.
     *
     * @param   array  $animation  Result of getAnimation()
     * @param   array  $options    Result of getPlayerOptions()
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function render(array $animation, array $options): string
    {
        $app = Factory::getApplication();

        if (!self::$languageLoaded) {
            $app->getLanguage()->load('mod_pankylottiefiles', JPATH_SITE)
                || $app->getLanguage()->load('mod_pankylottiefiles', JPATH_SITE . '/modules/mod_pankylottiefiles');

            self::$languageLoaded = true;
        }

        $wa = $app->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('mod_pankylottiefiles');
        $wa->useStyle('mod_pankylottiefiles.player')->useScript('mod_pankylottiefiles.player');

        // Appended last, so template overrides in html/layouts/pankylottie/ take precedence
        $layout = new FileLayout('pankylottie.player');
        $layout->setIncludePaths([
            ...$layout->getDefaultIncludePaths(),
            JPATH_SITE . '/modules/mod_pankylottiefiles/layouts',
        ]);

        return $layout->render(['animation' => $animation, 'options' => $options]);
    }

    /**
     * Makes site-relative paths absolute and rejects anything but http(s).
     *
     * @param   string  $url  Absolute URL or path relative to the site root
     *
     * @return  string  Empty string when the URL is not allowed
     *
     * @since   2.0.0
     */
    public function resolveUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (preg_match('#^([a-z][a-z0-9+.-]*):#i', $url, $match)) {
            return \in_array(strtolower($match[1]), ['http', 'https'], true) ? $url : '';
        }

        if (str_starts_with($url, '//') || str_starts_with($url, '#')) {
            return $url;
        }

        return Uri::root(true) . '/' . ltrim($url, '/');
    }

    private function pick(mixed $value, array $allowed): string
    {
        return \in_array($value, $allowed, true) ? $value : $allowed[0];
    }

    private function cssLength(mixed $value, string $default): string
    {
        $value = strtolower(trim((string) $value));

        if (is_numeric($value)) {
            return $value . 'px';
        }

        return preg_match('#^(auto|\d+(\.\d+)?(px|%|em|rem|vw|vh|svh|dvh|ch))$#', $value) ? $value : $default;
    }

    private function ratio(mixed $value): string
    {
        if (!preg_match('#^\s*(\d+(?:\.\d+)?)\s*(?:[/:]\s*(\d+(?:\.\d+)?))?\s*$#', (string) $value, $match) || (float) $match[1] <= 0) {
            return '';
        }

        $height = $match[2] ?? '1';

        return (float) $height > 0 ? $match[1] . ' / ' . $height : '';
    }

    private function hexColor(mixed $value): string
    {
        $value = strtoupper(ltrim(trim((string) $value), '#'));

        if (!preg_match('#^([0-9A-F]{3,4}|[0-9A-F]{6}|[0-9A-F]{8})$#', $value)) {
            return '';
        }

        // dotLottie only accepts 6 or 8 digit colours
        if (\strlen($value) <= 4) {
            $value = implode('', array_map(static fn (string $c): string => $c . $c, str_split($value)));
        }

        return '#' . $value;
    }
}
