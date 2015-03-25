<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ChainCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ChainCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('chain')
          ->setDescription($this->trans('commands.chain.description'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interactive = false;

        $learning = false;
        if ($input->hasOption('learning')) {
            $learning = $input->getOption('learning');
        }

        $generateModuleInputs = [
          '--module' => 'Example module',
          '--machine-name' => 'example',
          '--module-path' => DRUPAL_ROOT . '/modules/custom/',
          '--description' => 'My example module',
          '--core' => '8.x',
          '--package' => 'Test',
          '--test' => false,
          '--controller' => false,
        ];

        $this->getHelper('chain')->addCommand('generate:module', $generateModuleInputs, $interactive, $learning);

        $generateControllerInputs = [
          '--module' => 'example',
          '--class-name' => 'ExampleController',
          '--method-name' => 'index',
          '--route'=>'/index',
          '--services' => 'twig',
        ];

        $this->getHelper('chain')->addCommand('generate:controller', $generateControllerInputs, $interactive, $learning);
    }
}
