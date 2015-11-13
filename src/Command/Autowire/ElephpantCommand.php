<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Autowire\ElephpantCommand.
 */

namespace Drupal\Console\Command\Autowire;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
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
        $renderer = $this->getRenderHelper();

        $directory = sprintf(
            '%stemplates/core/elephpants/',
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
                'core/elephpants/%s',
                $templates[array_rand($templates)]
            )
        );

        $output->writeln($elephpant);
    }
}
