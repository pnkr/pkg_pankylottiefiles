<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Pnkr\Module\PankyLottieFiles\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\HTML\HTMLHelper;

\defined('_JEXEC') or die;

/**
 * Dispatcher class for mod_pankylottiefiles
 *
 * @since  2.0.0
 */
final class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];
        $helper = $this->getHelperFactory()->getHelper('PlayerHelper');

        if ($params->get('prepare_content', 0)) {
            $data['module']->content = HTMLHelper::_('content.prepare', $data['module']->content, '', 'mod_pankylottiefiles.content');
        }

        $display = $params->get('display', 'stack');

        $data['playerHelper'] = $helper;
        $data['options']      = $helper->getPlayerOptions($params);
        $data['animations']   = $helper->getAnimations($params->get('animations', []));
        $data['display']      = \in_array($display, ['stack', 'grid', 'slider'], true) ? $display : 'stack';
        $data['columns']      = min(12, max(1, (int) $params->get('columns', 3)));

        return $data;
    }
}
