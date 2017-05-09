<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ProjectDownloadTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Zippy\Adapter\TarGzGNUTarForWindowsAdapter;
use Drupal\Console\Zippy\FileStrategy\TarGzFileForWindowsStrategy;
use Alchemy\Zippy\Zippy;
use Alchemy\Zippy\Adapter\AdapterContainer;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ProjectDownloadTrait
 *
 * @package Drupal\Console\Command
 */
trait ProjectDownloadTrait
{
    public function modulesQuestion(DrupalStyle $io)
    {
        $moduleList = [];

        $modules = $this->extensionManager->discoverModules()
            ->showUninstalled()
            ->showNoCore()
            ->getList(true);

        while (true) {
            $moduleName = $io->choiceNoList(
                $this->trans('commands.module.install.questions.module'),
                $modules,
                null,
                true
            );

            if (empty($moduleName)) {
                break;
            }

            $moduleList[] = $moduleName;

            if (array_search($moduleName, $moduleList, true) >= 0) {
                unset($modules[array_search($moduleName, $modules)]);
            }
        }

        return $moduleList;
    }

    public function modulesUninstallQuestion(DrupalStyle $io)
    {
        $moduleList = [];

        $modules = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->showCore()
            ->getList(true);

        while (true) {
            $moduleName = $io->choiceNoList(
                $this->trans('commands.module.uninstall.questions.module'),
                $modules,
                null,
                true
            );

            if (empty($moduleName)) {
                break;
            }

            $moduleList[] = $moduleName;
        }

        return $moduleList;
    }

    private function downloadModules(DrupalStyle $io, $modules, $latest, $path = null, $resultList = [])
    {
        if (!$resultList) {
            $resultList = [
              'invalid' => [],
              'uninstalled' => [],
              'dependencies' => []
            ];
        }
        drupal_static_reset('system_rebuild_module_data');

        $missingModules = $this->validator->getMissingModules($modules);

        $invalidModules = [];
        if ($missingModules) {
            $io->info(
                sprintf(
                    $this->trans('commands.module.install.messages.getting-missing-modules'),
                    implode(', ', $missingModules)
                )
            );
            foreach ($missingModules as $missingModule) {
                $version = $this->releasesQuestion($io, $missingModule, $latest);
                if ($version) {
                    $this->downloadProject($io, $missingModule, $version, 'module', $path);
                } else {
                    $invalidModules[] = $missingModule;
                    unset($modules[array_search($missingModule, $modules)]);
                }
                $this->extensionManager->discoverModules();
            }
        }

        $unInstalledModules = $this->validator->getUninstalledModules($modules);

        $dependencies = $this->calculateDependencies($unInstalledModules);

        $resultList = [
          'invalid' => array_unique(array_merge($resultList['invalid'], $invalidModules)),
          'uninstalled' => array_unique(array_merge($resultList['uninstalled'], $unInstalledModules)),
          'dependencies' => array_unique(array_merge($resultList['dependencies'], $dependencies))
        ];

        if (!$dependencies) {
            return $resultList;
        }

        return $this->downloadModules($io, $dependencies, $latest, $path, $resultList);
    }

    protected function calculateDependencies($modules)
    {
        $this->site->loadLegacyFile('/core/modules/system/system.module');
        $moduleList = system_rebuild_module_data();

        $dependencies = [];

        foreach ($modules as $moduleName) {
            $module = $moduleList[$moduleName];

            $dependencies = array_unique(
                array_merge(
                    $dependencies,
                    $this->validator->getUninstalledModules(
                        array_keys($module->requires)?:[]
                    )
                )
            );
        }

        return array_diff($dependencies, $modules);
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param $project
     * @param $version
     * @param $type
     * @param $path
     *
     * @return string
     */
    public function downloadProject(DrupalStyle $io, $project, $version, $type, $path = null)
    {
        $commandKey = str_replace(':', '.', $this->getName());

        $io->comment(
            sprintf(
                $this->trans('commands.'.$commandKey.'.messages.downloading'),
                $project,
                $version
            )
        );

        try {
            $destination = $this->drupalApi->downloadProjectRelease(
                $project,
                $version
            );

            if (!$path) {
                $path = $this->getExtractPath($type);
            }

            $projectPath = sprintf(
                '%s/%s',
                $this->appRoot,
                $path
            );

            if (!file_exists($projectPath)) {
                if (!mkdir($projectPath, 0777, true)) {
                    $io->error(
                        sprintf(
                            $this->trans('commands.'.$commandKey.'.messages.error-creating-folder'),
                            $projectPath
                        )
                    );
                    return null;
                }
            }

            $zippy = Zippy::load();
            if (PHP_OS === "WIN32" || PHP_OS === "WINNT") {
                $container = AdapterContainer::load();
                $container['Drupal\\Console\\Zippy\\Adapter\\TarGzGNUTarForWindowsAdapter'] = function ($container) {
                    return TarGzGNUTarForWindowsAdapter::newInstance(
                        $container['executable-finder'],
                        $container['resource-manager'],
                        $container['gnu-tar.inflator'],
                        $container['gnu-tar.deflator']
                    );
                };
                $zippy->addStrategy(new TarGzFileForWindowsStrategy($container));
            }
            $archive = $zippy->open($destination);
            if ($type == 'core') {
                $archive->extract(getenv('MSYSTEM') ? null : $projectPath);
            } elseif (getenv('MSYSTEM')) {
                $current_dir = getcwd();
                $temp_dir = sys_get_temp_dir();
                chdir($temp_dir);
                $archive->extract();
                $fileSystem = new Filesystem();
                $fileSystem->rename($temp_dir . '/' . $project, $projectPath . '/' . $project);
                chdir($current_dir);
            } else {
                $archive->extract($projectPath);
            }

            unlink($destination);

            if ($type != 'core') {
                $io->success(
                    sprintf(
                        $this->trans(
                            'commands.' . $commandKey . '.messages.downloaded'
                        ),
                        $project,
                        $version,
                        sprintf('%s/%s', $projectPath, $project)
                    )
                );
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return null;
        }

        return $projectPath;
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param string                                 $project
     * @param bool                                   $latest
     * @param bool                                   $stable
     * @return string
     */
    public function releasesQuestion(DrupalStyle $io, $project, $latest = false, $stable = false)
    {
        $commandKey = str_replace(':', '.', $this->getName());

        $io->comment(
            sprintf(
                $this->trans('commands.'.$commandKey.'.messages.getting-releases'),
                implode(',', [$project])
            )
        );

        $releases = $this->drupalApi->getProjectReleases($project, $latest?1:15, $stable);

        if (!$releases) {
            $io->error(
                sprintf(
                    $this->trans('commands.'.$commandKey.'.messages.no-releases'),
                    implode(',', [$project])
                )
            );

            return null;
        }

        if ($latest) {
            return $releases[0];
        }

        $version = $io->choice(
            $this->trans('commands.'.$commandKey.'.messages.select-release'),
            $releases
        );

        return $version;
    }

    /**
     * @param $type
     * @return string
     */
    private function getExtractPath($type)
    {
        switch ($type) {
        case 'module':
            return 'modules/contrib';
        case 'theme':
            return 'themes';
        case 'profile':
            return 'profiles';
        case 'core':
            return '';
        }
    }

    /**
     * check if a modules repo is in composer.json
     * check if the repo is setted and matchs the one in config.yml
     *
     * @param  object $config
     * @return boolean
     */
    private function repositoryAlreadySet($config, $repo)
    {
        if (!$config->repositories) {
            return false;
        } else {
            if (in_array($repo, $config->repositories)) {
                return true;
            } else {
                return false;
            }
        }
    }
}
