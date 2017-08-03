<?php

/**
 * @file
 * Contains \Drupal\Console\Command\RouterRebuildCommand.
 */

namespace Drupal\Console\Command\Router;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Console\Core\Style\DrupalStyle;

class RebuildCommand extends Command
{
    /**
     * @var RouteBuilderInterface
     */
    protected $routerBuilder;

    /**
     * RebuildCommand constructor.
     *
     * @param RouteBuilderInterface $routerBuilder
     */
    public function __construct(RouteBuilderInterface $routerBuilder)
    {
        $this->routerBuilder = $routerBuilder;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('router:rebuild')
            ->setDescription($this->trans('commands.router.rebuild.description'))
            ->setAliases(['rr']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $io->newLine();
        $io->comment(
            $this->trans('commands.router.rebuild.messages.rebuilding')
        );

        $this->routerBuilder->rebuild();

        $io->success(
            $this->trans('commands.router.rebuild.messages.completed')
        );
    }
}
