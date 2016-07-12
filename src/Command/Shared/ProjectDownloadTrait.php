<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ProjectDownloadTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Style\DrupalStyle;
use Alchemy\Zippy\Zippy;

/**
 * Class ProjectDownloadTrait
 * @package Drupal\Console\Command
 */
trait ProjectDownloadTrait
{
    public function modulesQuestion(DrupalStyle $io)
    {
        $moduleList = [];
        $modules = $this->getApplication()->getSite()->getModules(true, false, true, true, true, true);

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
        $modules = $this->getApplication()->getSite()->getModules(true, true, false, true, true, true);

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

        $validator = $this->getApplication()->getValidator();
        $missingModules = $validator->getMissingModules($modules);

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
                $this->getApplication()->getSite()->discoverModules();
            }
        }

        $unInstalledModules = $validator->getUninstalledModules($modules);

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
        $this->getApplication()->getDrupalHelper()->loadLegacyFile('/core/modules/system/system.module');
        $moduleList = system_rebuild_module_data();

        $dependencies = [];
        $validator = $this->getApplication()->getValidator();

        foreach ($modules as $moduleName) {
            $module = $moduleList[$moduleName];

            $dependencies = array_unique(
                array_merge(
                    $dependencies,
                    $validator->getUninstalledModules(
                        array_keys($module->requires)?:[]
                    )
                )
            );
        }

        return array_diff($dependencies, $modules);
    }

    /**
     * @param \Drupal\Console\Style\DrupalStyle $io
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
            $destination = $this->getApplication()->getDrupalApi()->downloadProjectRelease(
                $project,
                $version
            );

            if (!$path) {
                $path = $this->getExtractPath($type);
            }

            $drupal = $this->get('site');
            $projectPath = sprintf(
                '%s/%s',
                $drupal->isValidInstance()?$drupal->getRoot():getcwd(),
                $path
            );

            if (!file_exists($projectPath)) {
                if (!mkdir($projectPath, 0777, true)) {
                    $io->error($this->trans('commands.'.$commandKey.'.messages.error-creating-folder') . ': ' . $projectPath);
                    return null;
                }
            }

            $zippy = Zippy::load();
            $archive = $zippy->open($destination);
            $archive->extract($projectPath);

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
     * @param \Drupal\Console\Style\DrupalStyle $io
     * @param string                            $project
     * @param bool                              $latest
     * @param bool                              $stable
     * @return string
     */
    public function releasesQuestion(DrupalStyle $io, $project, $latest = false, $stable = false)
    {
        $commandKey = str_replace(':', '.', $this->getName());

        $io->comment(
            sprintf(
                $this->trans('commands.'.$commandKey.'.messages.getting-releases'),
                implode(',', array($project))
            )
        );

        $releases = $this->getApplication()->getDrupalApi()->getProjectReleases($project, $latest?1:15, $stable);

        if (!$releases) {
            $io->error(
                sprintf(
                    $this->trans('commands.'.$commandKey.'.messages.no-releases'),
                    implode(',', array($project))
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
     * Includes drupal packagist repository at composer.json file.
     *
     * @param \Drupal\Console\Style\DrupalStyle $io
     */
    public function setComposerRepositories($repo)
    {
        $file = $this->getApplication()->getSite()->getSiteRoot() . "/composer.json";
        $composerFile = json_decode(file_get_contents($file));

        $application = $this->getApplication();
        $config = $application->getConfig();

        $repository = $config->get('application.composer.repositories.' . $repo);

        if (!$repository) {
            throw new \Exception(
                $this->trans('commands.module.download.messages.no-composer-repo')
            );
            return 1;
        }

        if (!$this->repositoryAlreadySet($composerFile, $repository)) {
            $repositories = (object) [[
                'type' => "composer",
                'url' => $repository
            ]];

            //@TODO: check it doesn't exist already
            $composerFile->repositories = $repositories;

            unlink($file);
            file_put_contents(
                $file,
                json_encode(
                    $composerFile,
                    JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
                )
            );
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
