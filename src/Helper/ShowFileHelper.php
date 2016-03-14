<?php

/**
 * @file
 * Contains Drupal\Console\Command\ShowFileHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShowFileHelper
 * @package Drupal\Console\Helper
 */
class ShowFileHelper extends Helper
{
    /**
     * @param DrupalStyle $io
     * @param string      $files
     */
    public function generatedFiles($io, $files)
    {
        $this->showFiles(
            $io,
            $files,
            'application.messages.files.generated',
            'application.site.messages.path',
            $this->getDrupalHelper()->getRoot()
        );
    }

    /**
     * @param DrupalStyle $io
     * @param string      $files
     */
    public function copiedFiles($io, $files)
    {
        $this->showFiles(
            $io,
            $files,
            'application.messages.files.copied',
            'application.user.messages.path',
            rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\').'/.console/'
        );
    }

    /**
     * @param DrupalStyle $io
     * @param array       $files
     * @param string      $headerKey
     * @param string      $pathKey
     * @param string      $path
     */
    private function showFiles($io, $files, $headerKey, $pathKey, $path)
    {
        if (!$files) {
            return;
        }

        $io->writeln($this->getTranslator()->trans($headerKey));

        $io->info(
            sprintf('%s:', $this->getTranslator()->trans($pathKey)),
            false
        );
        $io->comment($path, false);
        $io->newLine();

        $index = 1;
        foreach ($files as $file) {
            $this->showFile($io, $file, $index);
            ++$index;
        }
    }

    /**
     * @param DrupalStyle $io
     * @param string      $file
     * @param int         $index
     */
    private function showFile(DrupalStyle $io, $file, $index)
    {
        $io->info(
            sprintf('%s -', $index),
            false
        );
        $io->comment($file, false);
        $io->newLine();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'showFile';
    }
}
