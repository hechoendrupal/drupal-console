<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ProjectDownloadTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Zippy\Adapter\TarGzGNUTarForWindowsAdapter;
use Drupal\Console\Zippy\FileStrategy\TarGzFileForWindowsStrategy;
use Alchemy\Zippy\Zippy;
use Alchemy\Zippy\Adapter\AdapterContainer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class ProjectDownloadTrait
 *
 * @package Drupal\Console\Command
 */
trait ProjectDownloadTrait
{
    /**
     * The fully qualified path to the Composer executable.
     *
     * @var string
     */
    private $composerExecutablePath;

    public function modulesQuestion()
    {
        $moduleList = [];

        $modules = $this->extensionManager->discoverModules()
            ->showUninstalled()
            ->showNoCore()
            ->getList(true);

        while (true) {
            $moduleName = $this->getIo()->choiceNoList(
                $this->trans('commands.module.install.questions.module'),
                $modules,
                '',
                true
            );

            if (empty($moduleName) || is_numeric($moduleName)) {
                break;
            }

            $moduleList[] = $moduleName;

            if (array_search($moduleName, $moduleList, true) >= 0) {
                unset($modules[array_search($moduleName, $modules)]);
            }
        }

        return $moduleList;
    }

    public function modulesUninstallQuestion()
    {
        $moduleList = [];

        $modules = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->showCore()
            ->getList(true);

        while (true) {
            $moduleName = $this->getIo()->choiceNoList(
                $this->trans('commands.module.uninstall.questions.module'),
                $modules,
                '',
                true
            );

            if (empty($moduleName) || is_numeric($moduleName)) {
                break;
            }

            $moduleList[] = $moduleName;
        }

        return $moduleList;
    }

    private function downloadThemes($themes, $latest, $path = null, $resultList = [])
    {
        if (!$resultList) {
            $resultList = [
              'invalid' => [],
              'uninstalled' => [],
              'dependencies' => []
            ];
        }
        drupal_static_reset('system_rebuild_module_data');

        $missingThemes = $this->validator->getMissingThemes($themes);

        $invalidModules = [];
        if ($missingThemes) {
            $this->getIo()->info(
                sprintf(
                    $this->trans('commands.theme.install.messages.theme-missing'),
                    implode(', ', $missingThemes)
                )
            );
            foreach ($missingThemes as $missingTheme) {
                $version = $this->releasesQuestion($missingTheme, $latest);
                if ($version) {
                    $this->downloadProject($missingTheme, $version, 'theme', $path);
                } else {
                    $invalidModules[] = $missingTheme;
                    unset($themes[array_search($missingTheme, $themes)]);
                }
                $this->extensionManager->discoverModules();
            }
        }
        $this->themeHandler->install($themes);

        $unInstalledThemes = $this->validator->getUninstalledThemes($themes);

        if (!$unInstalledThemes) {
            return 0;
        }else{
            return $this->setInfoMessage('commands.theme.install.messages.theme-success', $missingThemes);
        }
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
     * @param $project
     * @param $version
     * @param $type
     * @param $path
     *
     * @return string
     */
    public function downloadProject($project, $version, $type, $path = null)
    {
        $commandKey = str_replace(':', '.', $this->getName());

        $this->getIo()->comment(
            sprintf(
                $this->trans('commands.' . $commandKey . '.messages.downloading'),
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
                    $this->getIo()->error(
                        sprintf(
                            $this->trans('commands.'.$commandKey.'.messages.error-creating-folder'),
                            $projectPath
                        )
                    );
                    return null;
                }
            }

            $zippy = Zippy::load();
            if (PHP_OS === 'WIN32' || PHP_OS === 'WINNT') {
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
                $this->getIo()->success(
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
            $this->getIo()->error($e->getMessage());

            return null;
        }

        return $projectPath;
    }

    /**
     * @param string                                 $project
     * @param bool                                   $latest
     * @param bool                                   $stable
     * @return string
     */
    public function releasesQuestion($project, $latest = false, $stable = false)
    {
        $commandKey = str_replace(':', '.', $this->getName());

        $this->getIo()->comment(
            sprintf(
                $this->trans('commands.' . $commandKey . '.messages.getting-releases'),
                implode(',', [$project])
            )
        );

        $releases = $this->drupalApi->getProjectReleases($project, $latest? 1 : 15, $stable);

        if (!$releases) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.' . $commandKey . '.messages.no-releases'),
                    implode(',', [$project])
                )
            );

            return null;
        }

        if ($latest) {
            return $releases[0];
        }

        $version = $this->getIo()->choice(
            $this->trans('commands.' . $commandKey . '.messages.select-release'),
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
            return 'themes/contrib';
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

    /**
     * Finds the Composer executable full path.
     *
     * @return string
     */
    private function getComposerExecutablePath()
    {
        if (!$this->composerExecutablePath) {
            $this->composerExecutablePath = $this->shellProcess->findExecutable('composer');
        }
        return $this->composerExecutablePath;
    }

    /**
     * Requires a list of packages through Composer.
     *
     * @param string[] $packages
     *   A list of Drupal modules as Composer packages.
     *
     * @return bool
     *   TRUE if the Composer process is successful, FALSE otherwise.
     */
    private function composerRequirePackages(array $packages)
    {
        $command = [$this->getComposerExecutablePath(), 'require'];
        if (!$this->getIo()->getInput()->isInteractive()) {
            $command[] = '--no-interaction';
        }
        return $this->shellProcess->execTty(array_merge($command, $packages), true);
    }

    /**
     * Updates a list of packages through Composer.
     *
     * @param string[] $packages
     *   A list of Drupal modules as Composer packages.
     *
     * @return bool
     *   TRUE if the Composer process is successful, FALSE otherwise.
     */
    private function composerUpdatePackages(array $packages, $withDependencies = true, $dryRun = false)
    {
        $command = [$this->getComposerExecutablePath(), 'update'];
        if ($withDependencies) {
            $command[] = '--with-dependencies';
        }
        if ($dryRun) {
            $command[] = '--dry-run';
        }
        if (!$this->getIo()->getInput()->isInteractive()) {
            $command[] = '--no-interaction';
        }
        return $this->shellProcess->execTty(array_merge($command, $packages), true);
    }
}
