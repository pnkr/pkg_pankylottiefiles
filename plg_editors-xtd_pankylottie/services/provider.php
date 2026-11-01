<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Editors-xtd.pankylottie
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Pnkr\Plugin\EditorsXtd\PankyLottie\Extension\PankyLottie;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container) {
                $plugin = new PankyLottie((array) PluginHelper::getPlugin('editors-xtd', 'pankylottie'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
