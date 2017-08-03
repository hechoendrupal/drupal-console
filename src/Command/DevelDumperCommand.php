<?php

namespace Drupal\Console\Command;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\devel\DevelDumperPluginManager;
use Drupal\devel\DevelDumperManager;

/**
 * Class DevelDumperCommand.
 * Command to quickly change between devel dumpers from the command line
 *
 * @package Drupal\Console\Command
 *
 * @todo Inject services using \Drupal
 * @todo Move to namespace Devel
 * @todo Load devel.module legacy file
 */
class DevelDumperCommand extends ContainerAwareCommand
{
    /**
     * @var DevelDumperPluginManager
     */
    protected $develDumperPluginManager;

    /**
     * DevelDumperCommand constructor.
     */
    public function __construct(
        DevelDumperPluginManager $develDumperPluginManager = null
    ) {
        $this->develDumperPluginManager = $develDumperPluginManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('devel:dumper')
            ->setDescription($this->trans('commands.devel.dumper.messages.change-devel-dumper-plugin'))
            ->addArgument(
                'dumper',
                InputArgument::OPTIONAL,
                $this->trans('commands.devel.dumper.messages.name-devel-dumper-plugin')
            )->setAliases(['dd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        if (!\Drupal::moduleHandler()->moduleExists('devel')) {
            $io->error($this->trans('commands.devel.dumper.messages.devel-must-be-installed'));

            return 1;
        }

        $dumper = $input->getArgument('dumper');
        if (!$dumper) {
            /* @var string[] $dumpKeys */
            $dumpKeys = $this->getDumperKeys();

            $dumper = $io->choice(
                $this->trans('commands.devel.dumper.messages.select-debug-dumper'),
                $dumpKeys,
                'kint', //Make kint the default for quick 'switchback'
                false
            );

            $input->setArgument('dumper', $dumper);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        //Check the dumper actually exists
        $dumper = $input->getArgument('dumper');
        $dumpKeys = $this->getDumperKeys();
        if (!in_array($dumper, $dumpKeys)) {
            $io->error($this->trans('commands.devel.dumper.messages.dumper-not-exist'));

            return 1;
        }
        /* @var ConfigFactory $configFactory */
        $configFactory = \Drupal::configFactory();
        /* @var Config $develSettings */
        $develSettings = $configFactory->getEditable('devel.settings');
        $develSettings->set('devel_dumper', $dumper)->save();
        $io->info(
            sprintf(
                $this->trans('commands.devel.dumper.messages.devel-dumper-set'),
                $configFactory->get('devel.settings')->get('devel_dumper')
            )
        );
    }

    protected function getDumperKeys()
    {
        /* @var DevelDumperPluginManager $manager */
        $plugins = $this->develDumperPluginManager->getDefinitions();
        return array_keys($plugins);
    }
}
