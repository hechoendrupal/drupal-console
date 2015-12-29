<?php

/**
 * @file
 * Contains Drupal\Console\Command\ProjectDownloadTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;
use Alchemy\Zippy\Zippy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Class ProjectDownloadTrait
 * @package Drupal\Console\Command
 */
trait ProjectDownloadTrait
{
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

        $siteName = null;
        if ($type == 'core') {
            $siteName = $project;
            $project = 'drupal';
        }

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

            $io->commentBlock($destination);

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
            } else {
                $downloadPath = sprintf('%sdrupal-%s', $projectPath, $version);
                $copyPath = sprintf('%s%s', $projectPath, $siteName);

                $io->commentBlock($projectPath);
                $io->commentBlock($downloadPath);
                $io->commentBlock($copyPath);

                try {
                    $fileSystem = new Filesystem();
                    $fileSystem->rename($downloadPath, $copyPath);
                } catch (IOExceptionInterface $e) {
                    $io->commentBlock(
                        sprintf(
                            $this->trans('commands.site.new.messages.downloaded'),
                            $version,
                            $downloadPath
                        )
                    );

                    $io->error(
                        sprintf(
                            $this->trans('commands.site.new.messages.error-copying'),
                            $e->getPath()
                        )
                    );

                    return;
                }

                $io->success(
                    sprintf(
                        $this->trans('commands.site.new.messages.downloaded'),
                        $version,
                        $copyPath
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
     * @param $project
     * @return string
     */
    public function releasesQuestion(DrupalStyle $io, $project)
    {
        $commandKey = str_replace(':', '.', $this->getName());

        $io->comment(
            sprintf(
                $this->trans('commands.'.$commandKey.'.messages.getting-releases'),
                implode(',', array($project))
            )
        );

        $releases = $this->getDrupalApi()->getProjectReleases($project);

        if (!$releases) {
            $io->error(
                sprintf(
                    $this->trans('commands.'.$commandKey.'.messages.no-releases'),
                    implode(',', array($project))
                )
            );

            return null;
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
