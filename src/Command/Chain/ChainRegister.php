<?php

/**
 * @file
 * Contains Drupal\Console\Command\ChainRegister.
 *
 * ChainRegister is a wrapper for Chain commands.
 * It will register the classes so you don't have to specify --file when calling
 * chain commands. i.e. drupal chain --file=/some-folder/chain-magic.yml will be
 * called: drupal chain:magic.
 *
 * To register custom chains, edit the ~/.console/chain.yml and add:
 * chain:
 *   name:
 *     'site:new:example':
 *        file: '/path-to-folder/chain-site-new.yml'
 */

namespace Drupal\Console\Command\Chain;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ChainFilesTrait;
use Drupal\Console\Command\Shared\CommandTrait;

/**
 * Class ChainRegister
 *
 * @package Drupal\Console\Command\ChainRegister
 */
class ChainRegister extends Command
{
    use CommandTrait;
    use ChainFilesTrait;

    protected $name;

    protected $file;

    /**
   * ChainRegister constructor.
   *
   * @param $name
   * @param $file
   */
    public function __construct($name, $file)
    {
        $this->name = $name;
        $this->file = $file;

        parent::__construct();
    }

    /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription(sprintf('Custom chain command (%s)', $this->name))
            ->addOption(
                'placeholder',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.chain.options.placeholder')
            );
    }

    /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('chain');

        $arguments = [
            'command' => 'chain',
            '--file'  => $this->file,
            '--placeholder'  => $input->getOption('placeholder'),
            '--generate-inline'  => $input->hasOption('generate-inline'),
            '--no-interaction'  => $input->hasOption('no-interaction')
        ];

        $commandInput = new ArrayInput($arguments);

        return $command->run($commandInput, $output);
    }
}
