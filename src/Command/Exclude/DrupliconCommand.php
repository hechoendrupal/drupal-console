<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Exclude\DrupliconCommand.
 */

namespace Drupal\Console\Command\Exclude;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Finder\Finder;

class DrupliconCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('druplicon')
            ->setDescription($this->trans('application.commands.druplicon.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $renderer = $this->getRenderHelper();

        $directory = sprintf(
            '%stemplates/core/druplicon/',
            $this->getApplication()->getDirectoryRoot()
        );

        $finder = new Finder();
        $finder->files()
            ->name('*.twig')
            ->in($directory);

        $templates = [];

        foreach ($finder as $template) {
            $templates[] = $template->getRelativePathname();
        }

        $druplicon = $renderer->render(
            sprintf(
                'core/druplicon/%s',
                $templates[array_rand($templates)]
            )
        );

        $io->writeln($druplicon);
    }
}
