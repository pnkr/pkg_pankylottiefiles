<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.pankylottie
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Pnkr\Plugin\Content\PankyLottie\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Pnkr\Module\PankyLottieFiles\Site\Helper\PlayerHelper;

\defined('_JEXEC') or die;

/**
 * Replaces {lottie src="..."} tags in content with a Lottie animation.
 *
 * @since  2.0.0
 */
final class PankyLottie extends CMSPlugin implements SubscriberInterface
{
    /**
     * Tag attribute => player param.
     */
    private const PLAYER_ATTRIBUTES = [
        'trigger'        => 'trigger',
        'hover-out'      => 'hover_out',
        'loop'           => 'loop',
        'speed'          => 'speed',
        'mode'           => 'mode',
        'controls'       => 'controls',
        'reduced-motion' => 'reduced_motion',
        'width'          => 'width',
        'height'         => 'height',
        'ratio'          => 'aspect_ratio',
        'fit'            => 'fit',
        'background'     => 'backgroundcolor',
    ];

    /**
     * Tag attribute => animation setting.
     */
    private const ANIMATION_ATTRIBUTES = [
        'src'        => 'url',
        'alt'        => 'alt',
        'decorative' => 'decorative',
        'link'       => 'link',
        'target'     => 'link_target',
        'marker'     => 'marker',
        'theme'      => 'theme',
    ];

    private const BOOLEAN_ATTRIBUTES = ['loop', 'controls', 'reduced-motion', 'decorative'];

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'onContentPrepare'];
    }

    /**
     * Replaces the tags in the item text.
     *
     * @param   ContentPrepareEvent  $event  The event
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        $item = $event->getItem();

        if (!\is_object($item) || !property_exists($item, 'text') || !\is_string($item->text) || stripos($item->text, '{lottie') === false) {
            return;
        }

        $app      = $this->getApplication();
        $document = $app->getDocument();

        // Only HTML pages on the site get players; elsewhere (search index, feeds) the tags are removed
        $render = $app->isClient('site')
            && $document !== null
            && $document->getType() === 'html'
            && $event->getContext() !== 'com_finder.indexer'
            && class_exists(PlayerHelper::class);

        $helper   = $render ? new PlayerHelper() : null;
        $callback = fn (array $match): string => $helper ? $this->render($helper, $match[1]) : '';

        // A tag alone in a paragraph replaces the whole paragraph, keeping the HTML valid
        $item->text = preg_replace_callback('#<p\b[^>]*>\s*\{lottie\s+([^}]*)\}\s*</p>#i', $callback, $item->text);
        $item->text = preg_replace_callback('#\{lottie\s+([^}]*)\}#i', $callback, $item->text);
    }

    /**
     * Renders one tag.
     *
     * @param   PlayerHelper  $helper      The shared player helper
     * @param   string        $attributes  Raw tag attributes
     *
     * @return  string
     *
     * @since   2.0.0
     */
    private function render(PlayerHelper $helper, string $attributes): string
    {
        $params = clone $this->params;
        $row    = ['source' => 'url'];

        foreach ($this->parseAttributes($attributes) as $name => $value) {
            if (\in_array($name, self::BOOLEAN_ATTRIBUTES, true)) {
                $value = (int) \in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
            }

            if (isset(self::PLAYER_ATTRIBUTES[$name])) {
                $params->set(self::PLAYER_ATTRIBUTES[$name], $value);
            } elseif (isset(self::ANIMATION_ATTRIBUTES[$name])) {
                $row[self::ANIMATION_ATTRIBUTES[$name]] = $value;
            } elseif ($name === 'segment' && preg_match('#^\s*(\d+)\s*[-,:]\s*(\d+)\s*$#', $value, $frames)) {
                $row['segment_start'] = $frames[1];
                $row['segment_end']   = $frames[2];
            }
        }

        $animation = $helper->getAnimation($row);

        return $animation ? $helper->render($animation, $helper->getPlayerOptions($params)) : '';
    }

    /**
     * Parses name="value" pairs. Values are entity-decoded after parsing, so a
     * quote can be written as &quot;.
     *
     * @param   string  $attributes  Raw tag attributes
     *
     * @return  array
     *
     * @since   2.0.0
     */
    private function parseAttributes(string $attributes): array
    {
        preg_match_all('#([a-z][a-z-]*)\s*=\s*(?|"([^"]*)"|\'([^\']*)\'|([^\s"\']+))#i', strip_tags($attributes), $matches, PREG_SET_ORDER);

        $parsed = [];

        foreach ($matches as [, $name, $value]) {
            $parsed[strtolower($name)] = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return $parsed;
    }
}
