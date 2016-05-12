<?php

/**
 * @file
 * Contains \Drupal\Console\Command\HelpCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * HelpCommand displays the help for a given command.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HelpCommand extends BaseCommand
{
    use CommandTrait;

    private $command;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('help')
            ->setDefinition($this->createDefinition())
            ->setDescription($this->trans('commands.help.description'))
            ->setHelp($this->trans('commands.help.help'));
    }

    /**
     * Sets the command.
     *
     * @param $command The command to set
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        if (null === $this->command) {
            $this->command = $this->getApplication()->find($input->getArgument('command_name'));
        }

        if ($input->getOption('xml')) {
            $io->info($this->trans('commands.help.messages.deprecated'), E_USER_DEPRECATED);
            $input->setOption('format', 'xml');
        }

        $helper = new DescriptorHelper();
        $helper->describe(
            $io, $this->command, array(
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'command_name' => $input->getArgument('command_name'),
            )
        );

        $this->command = null;
    }

    /**
     * {@inheritdoc}
     */
    private function createDefinition()
    {
        return new InputDefinition(
            array(
            new InputArgument('command_name', InputArgument::OPTIONAL, $this->trans('commands.help.arguments.command_name'), 'help'),
            new InputOption('xml', null, InputOption::VALUE_NONE, $this->trans('commands.help.options.xml')),
            new InputOption('raw', null, InputOption::VALUE_NONE, $this->trans('commands.help.options.raw')),
            new InputOption('format', null, InputOption::VALUE_REQUIRED, $this->trans('commands.help.options.format'), 'txt'),
            )
        );
    }
}
