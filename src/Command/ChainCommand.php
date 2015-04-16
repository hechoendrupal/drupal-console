<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ChainCommand.
 */
namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Config;
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
          ->addOption(
            'file',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.chain.options.file')
          )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getHelperSet()->get('message');

        $interactive = false;

        $learning = false;
        if ($input->hasOption('learning')) {
            $learning = $input->getOption('learning');
        }

        $file = null;
        if ($input->hasOption('file')) {
            $file = $input->getOption('file');
        }

        if (strpos($file, '~') == 0) {
            $home = rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/');
            $file = realpath(preg_replace('/~/', $home, $file, 1));
        }

        if (!$file) {
            $message->addErrorMessage(
              $this->trans('commands.chain.messages.missing_file')
            );
            return 1;
        }

        if (!file_exists($file)) {
            $message->addErrorMessage(
              sprintf(
                $this->trans('commands.chain.messages.invalid_file'),
                $file
              )
            );
            return 1;
        }

        $chainData = new Config($file);
        $commands = $chainData->get('commands');

        foreach ($commands as $command) {
            $moduleInputs = [];
            $arguments = !empty($command['arguments']) ? $command['arguments'] : [];
            $options = !empty($command['options']) ? $command['options'] : [];

            foreach ($arguments as $key => $value) {
                $moduleInputs[$key] = is_null($value) ? '' : $value;
            }

            foreach ($options as $key => $value) {
                $moduleInputs['--' . $key] = is_null($value) ? '' : $value;
            }

            $this->getHelper('chain')
                ->addCommand($command['command'], $moduleInputs, $interactive, $learning);
        }
    }
}
