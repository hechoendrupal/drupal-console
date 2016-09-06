<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Exclude\ElephpantCommand.
 */

namespace Drupal\Console\Command\Exclude;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\TwigRenderer;
use Drupal\Console\Style\DrupalStyle;

class ElephpantCommand extends Command
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
            ->setName('elephpant')
            ->setDescription($this->trans('application.commands.elephpant.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = sprintf(
            '%stemplates/core/elephpant/',
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

        $elephpant = $this->renderer->render(
            sprintf(
                'core/elephpant/%s',
                $templates[array_rand($templates)]
            )
        );

        $io->writeln($elephpant);
    }
}
