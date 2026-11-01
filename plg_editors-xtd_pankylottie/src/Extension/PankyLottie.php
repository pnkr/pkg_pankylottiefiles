<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Editors-xtd.pankylottie
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Pnkr\Plugin\EditorsXtd\PankyLottie\Extension;

use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * Editor button that inserts a {lottie} tag for the Panky Lottie content plugin.
 *
 * @since  2.0.0
 */
final class PankyLottie extends CMSPlugin implements SubscriberInterface
{
    private const SCRIPT_STRINGS = [
        'ALT', 'INSERT', 'LOOP', 'SRC', 'TITLE', 'TRIGGER',
        'TRIGGER_AUTOPLAY', 'TRIGGER_CLICK', 'TRIGGER_HOVER', 'TRIGGER_SCROLL', 'TRIGGER_VIEWPORT',
    ];

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];
    }

    /**
     * Registers the button.
     *
     * @param   EditorButtonsSetupEvent  $event  The event
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        if (\in_array($this->_name, $event->getDisabledButtons(), true)) {
            return;
        }

        // Defines "editor-button.pankylottie", which the button layout loads
        $this->getApplication()->getDocument()->getWebAssetManager()
            ->getRegistry()->addExtensionRegistryFile('mod_pankylottiefiles');

        $this->loadLanguage();

        foreach (self::SCRIPT_STRINGS as $key) {
            Text::script('PLG_EDITORS-XTD_PANKYLOTTIE_' . $key);
        }

        Text::script('JCANCEL');

        $event->getButtonsRegistry()->add(new Button(
            $this->_name,
            [
                'action'  => 'pankylottie-insert',
                'text'    => Text::_('PLG_EDITORS-XTD_PANKYLOTTIE_BUTTON'),
                'icon'    => 'play',
                'iconSVG' => '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M16 2a14 14 0 1 0 0 28 14 14 0 0 0 0-28zm-4 21V9l12 7z"></path></svg>',
                'name'    => $this->_type . '_' . $this->_name,
            ]
        ));
    }
}
