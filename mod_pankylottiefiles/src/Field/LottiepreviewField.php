<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Pnkr\Module\PankyLottieFiles\Site\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * Live preview of the module's animations in the module editor.
 *
 * @since  2.0.0
 */
class LottiepreviewField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  2.0.0
     */
    protected $type = 'Lottiepreview';

    /**
     * Method to get the field input markup.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    protected function getInput()
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('mod_pankylottiefiles');
        $wa->useStyle('mod_pankylottiefiles.preview')->useScript('mod_pankylottiefiles.preview');

        return '<div class="pankylottie-preview" id="' . $this->id . '" aria-live="polite"'
            . ' data-control="' . htmlspecialchars($this->formControl . '[' . $this->group . ']', ENT_QUOTES, 'UTF-8') . '"'
            . ' data-root="' . htmlspecialchars(Uri::root(), ENT_QUOTES, 'UTF-8') . '"'
            . ' data-empty="' . htmlspecialchars(Text::_('MOD_PANKYLOTTIEFILES_PREVIEW_EMPTY'), ENT_QUOTES, 'UTF-8') . '"'
            . '></div>';
    }
}
