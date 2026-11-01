<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class ($container->get(DatabaseInterface::class)) implements InstallerScriptInterface {
                public function __construct(private DatabaseInterface $db)
                {
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
                    return true;
                }

                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    if ($type === 'update') {
                        $this->migrateLegacyParams();
                        $this->removeLegacyFiles();
                    }

                    return true;
                }

                /**
                 * Converts module params saved by 1.x into the 2.x structure.
                 */
                private function migrateLegacyParams(): void
                {
                    $module = 'mod_pankylottiefiles';
                    $query  = $this->db->createQuery()
                        ->select($this->db->quoteName(['id', 'params']))
                        ->from($this->db->quoteName('#__modules'))
                        ->where($this->db->quoteName('module') . ' = :module')
                        ->bind(':module', $module);

                    foreach ($this->db->setQuery($query)->loadObjectList() as $row) {
                        $params = new Registry($row->params);

                        // 1.x stored a single URL; 2.x params always have "animations"
                        if (!$params->exists('jsonlottie') || $params->exists('animations')) {
                            continue;
                        }

                        $params->set('animations', [
                            'animations0' => [
                                'source'     => 'url',
                                'url'        => $params->get('jsonlottie'),
                                'decorative' => 1,
                            ],
                        ]);

                        if ($params->get('hover') === 'hover') {
                            $params->set('trigger', 'hover');
                        } elseif ($params->get('autoplay', 'autoplay') === 'autoplay') {
                            $params->set('trigger', 'autoplay');
                        } else {
                            $params->set('trigger', 'click');
                        }

                        $mode = $params->get('playmode') === 'bounce' ? 'bounce' : 'forward';

                        if ($params->get('direction') === 'backwards') {
                            $mode = $mode === 'bounce' ? 'reverse-bounce' : 'reverse';
                        }

                        $params->set('mode', $mode);

                        $params->set('loop', (int) ($params->get('loop', 'loop') === 'loop'));
                        $params->set('controls', (int) ($params->get('controls', 'controls') === 'controls'));
                        $params->set('aspect_ratio', '');
                        $params->set('display', 'stack');

                        foreach (['jsonlottie', 'hover', 'autoplay', 'playmode', 'direction'] as $legacy) {
                            $params->remove($legacy);
                        }

                        $json   = $params->toString();
                        $update = $this->db->createQuery()
                            ->update($this->db->quoteName('#__modules'))
                            ->set($this->db->quoteName('params') . ' = :params')
                            ->where($this->db->quoteName('id') . ' = :id')
                            ->bind(':params', $json)
                            ->bind(':id', $row->id, ParameterType::INTEGER);

                        $this->db->setQuery($update)->execute();
                    }
                }

                /**
                 * Removes files left behind by 1.x. Its language files lived in the
                 * site language folder, which takes precedence over the module's own.
                 */
                private function removeLegacyFiles(): void
                {
                    $files = [
                        JPATH_SITE . '/modules/mod_pankylottiefiles/mod_pankylottiefiles.php',
                        JPATH_SITE . '/modules/mod_pankylottiefiles/index.html',
                        JPATH_SITE . '/modules/mod_pankylottiefiles/tmpl/index.html',
                        JPATH_ROOT . '/media/mod_pankylottiefiles/js/lottie-player.js',
                    ];

                    foreach (['en-GB', 'el-GR'] as $tag) {
                        $files[] = JPATH_SITE . '/language/' . $tag . '/mod_pankylottiefiles.ini';
                        $files[] = JPATH_SITE . '/language/' . $tag . '/mod_pankylottiefiles.sys.ini';
                    }

                    foreach ($files as $file) {
                        if (is_file($file)) {
                            File::delete($file);
                        }
                    }
                }
            }
        );
    }
};
