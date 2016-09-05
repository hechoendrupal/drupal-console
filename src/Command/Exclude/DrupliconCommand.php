<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Exclude\DrupliconCommand.
 */

namespace Drupal\Console\Command\Exclude;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\TwigRenderer;
use Drupal\Console\Style\DrupalStyle;

class DrupliconCommand extends Command
{
    use CommandTrait;

    protected $appRoot;
    /**
     * @var TwigRenderer
     */
    protected $renderer;

    /**
     * DrupliconCommand constructor.
     * @param string       $appRoot
     * @param TwigRenderer $renderer
     */
    public function __construct(
        $appRoot,
        TwigRenderer $renderer
    ) {
        $this->appRoot = $appRoot;
        $this->renderer = $renderer;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('druplicon')
            ->setDescription($this->trans('application.commands.druplicon.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = sprintf(
            '%s/templates/core/druplicon/',
            $this->appRoot . DRUPAL_CONSOLE
        );

        $finder = new Finder();
        $finder->files()
            ->name('*.twig')
            ->in($directory);

        $templates = [];

        foreach ($finder as $template) {
            $templates[] = $template->getRelativePathname();
        }

        $druplicon = $this->renderer->render(
            sprintf(
                'core/druplicon/%s',
                $templates[array_rand($templates)]
            )
        );

        $io->writeln($druplicon);
    }
}
