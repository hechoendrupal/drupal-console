<?php

namespace Drupal\Console\Command;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\devel\DevelDumperPluginManager;
use Drupal\devel\DevelDumperManager;

/**
 * Class DumperCommand.
 * Command to quickly change between devel dumpers from the command line
 * @package Drupal\Console\Command
 */
class DumperCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        //Dumper is optional, if no input present a list
        $this
            ->setName('devel:dumper')
            ->setDescription($this->trans('Change the devel dumper plugin'))
            ->addArgument('dumper', InputArgument::OPTIONAL, $this->trans('Name of the devel dumper plugin'));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        //Without devel we will not get very far
        $d = $this->getModuleHandler()->moduleExists('devel');
        if (!$d) {
            $io->error($this->trans('Devel must be installed'));
        }

        $name = $input->getArgument('dumper');
        if (!$name) {
            /* @var string[] $dumpKeys */
            $dumpKeys = $this->getDumperKeys();

            $name = $io->choice(
                $this->trans('Select a Debug Dumper'),
                $dumpKeys,
                'kint', //Make kint the default for quick 'switchback'
                false
            );

            $input->setArgument('dumper', $name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        //Check the dumper actually exists
        $name = $input->getArgument('dumper');
        $dumpKeys = $this->getDumperKeys();
        if (!in_array($name, $dumpKeys)) {
            $io->error($this->trans('Dumper does not exist'));
            return;
        }
        //Set the dumper in config
        /* @var ConfigFactory $cf */
        $cf = $this->getContainer()->get('config.factory');
        /* @var Config $ds */
        $ds = $cf->getEditable('devel.settings');
        $ds->set('devel_dumper', $name)->save();
        //By actually retrieving the value from config again here and printing it we confirm it was set properly
        $set = $cf->get('devel.settings')->get('devel_dumper');
        $io->info($this->trans("Devel Dumper set to $set"));
    }

    protected function getDumperKeys()
    {
        /* @var DevelDumperPluginManager $manager */
        $manager = $this->getContainer()->get('plugin.manager.devel_dumper');
        $plugins = $manager->getDefinitions();
        return array_keys($plugins);
    }
}
