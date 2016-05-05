<?php

/**
 * @file
 * Contains Drupal\Console\Command\ProjectDownloadTrait.
 */

namespace Drupal\Console\Command;

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
        $modules = $this->getSite()->getModules(true, false, true, true, true, true);

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

    private function downloadModules(DrupalStyle $io, $modules, $latest, $resultList = [])
    {
        if (!$resultList) {
            $resultList = [
              'invalid' => [],
              'uninstalled' => [],
              'dependencies' => []
            ];
        }
        drupal_static_reset('system_rebuild_module_data');

        $validator = $this->getValidator();
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
                    $this->downloadProject($io, $missingModule, $version, 'module');
                } else {
                    $invalidModules[] = $missingModule;
                    unset($modules[array_search($missingModule, $modules)]);
                }
                $this->getSite()->discoverModules();
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

        return $this->downloadModules($io, $dependencies, $latest, $resultList);
    }

    protected function calculateDependencies($modules)
    {
        $this->getDrupalHelper()->loadLegacyFile('/core/modules/system/system.module');
        $moduleList = system_rebuild_module_data();

        $dependencies = [];
        $validator = $this->getValidator();

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
     * @return string
     */
    public function downloadProject(DrupalStyle $io, $project, $version, $type)
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
            $destination = $this->getDrupalApi()->downloadProjectRelease(
                $project,
                $version
            );

            $drupal = $this->getDrupalHelper();
            $projectPath = sprintf(
                '%s/%s',
                $drupal->isValidInstance()?$drupal->getRoot():getcwd(),
                $this->getExtractPath($type)
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
     * @return string
     */
    public function releasesQuestion(DrupalStyle $io, $project, $latest = false)
    {
        $commandKey = str_replace(':', '.', $this->getName());

        $io->comment(
            sprintf(
                $this->trans('commands.'.$commandKey.'.messages.getting-releases'),
                implode(',', array($project))
            )
        );

        $releases = $this->getDrupalApi()->getProjectReleases($project, $latest?1:15);

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
        case 'core':
            return '';
        }
    }
}
