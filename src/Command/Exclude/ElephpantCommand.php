<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Exclude\ElephpantCommand.
 */

namespace Drupal\Console\Command\Exclude;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Finder\Finder;

class ElephpantCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('elephpant')
            ->setDescription($this->trans('application.commands.elephpant.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $renderer = $this->getRenderHelper();

        $directory = sprintf(
            '%stemplates/core/elephpant/',
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

        $elephpant = $renderer->render(
            sprintf(
                'core/elephpant/%s',
                $templates[array_rand($templates)]
            )
        );

        $io->writeln($elephpant);
    }
}
