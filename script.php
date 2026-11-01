<?php

/**
 * @package     Panky Lottie Files
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class (
                $container->get(AdministratorApplication::class),
                $container->get(DatabaseInterface::class)
            ) implements InstallerScriptInterface {
                private const MINIMUM_JOOMLA = '6.0.0';

                private const MINIMUM_PHP = '8.3.0';

                public function __construct(
                    private AdministratorApplication $app,
                    private DatabaseInterface $db
                ) {
                }

                public function install(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function uninstall(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    if ($type === 'uninstall') {
                        return true;
                    }

                    if (version_compare(PHP_VERSION, self::MINIMUM_PHP, '<')) {
                        $this->app->enqueueMessage(Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', self::MINIMUM_PHP), 'error');

                        return false;
                    }

                    if (version_compare(JVERSION, self::MINIMUM_JOOMLA, '<')) {
                        $this->app->enqueueMessage(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', self::MINIMUM_JOOMLA), 'error');

                        return false;
                    }

                    $this->app->getLanguage()->load('pkg_pankylottie.sys', $adapter->getParent()->getPath('source'));

                    return true;
                }

                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    if ($type === 'uninstall') {
                        return true;
                    }

                    // Plugins are installed disabled; enable them the first time only
                    if ($type === 'install') {
                        $query = $this->db->createQuery()
                            ->update($this->db->quoteName('#__extensions'))
                            ->set($this->db->quoteName('enabled') . ' = 1')
                            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
                            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('pankylottie'))
                            ->whereIn($this->db->quoteName('folder'), ['content', 'editors-xtd'], ParameterType::STRING);

                        $this->db->setQuery($query)->execute();
                    }

                    $media = ComponentHelper::getParams('com_media');

                    if (!\in_array('lottie', array_map('trim', explode(',', $media->get('restrict_uploads_extensions', ''))), true)) {
                        $this->app->enqueueMessage(Text::_('PKG_PANKYLOTTIE_MEDIA_NOTICE'), 'info');
                    }

                    return true;
                }
            }
        );
    }
};
