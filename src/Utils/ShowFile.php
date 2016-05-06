<?php

/**
 * @file
 * Contains Drupal\Console\Command\ShowFileHelper.
 */

namespace Drupal\Console\Utils;

use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Translator;

/**
 * Class ShowFileHelper
 * @package Drupal\Console\Helper
 */
class ShowFile
{
    /**
 * @var Site 
*/
    protected $site;

    /**
 * @var Translator 
*/
    protected $translator;

    /**
     * ShowFile constructor.
     * @param Site       $site
     * @param Translator $translator
     */
    public function __construct(
        Site $site,
        Translator $translator
    ) {
        $this->site = $site;
        $this->translator = $translator;
    }

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
            $this->site->getRoot()
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

        $io->writeln($this->translator->trans($headerKey));

        $io->info(
            sprintf('%s:', $this->translator->trans($pathKey)),
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
}
