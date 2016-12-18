<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\HelpCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Generator\HelpGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\ChainQueue;

class HelpCommand extends Command
{
    use CommandTrait;
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var HelpGenerator  
*/
    protected $generator;

    /**
     * @var Site
     */
    protected $site;

    /**
 * @var Manager  
*/
    protected $extensionManager;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * HelpCommand constructor.
     * @param HelpGenerator $generator
     * @param Site          $site
     * @param Manager       $extensionManager
     * @param ChainQueue    $chainQueue
     */
    public function __construct(
        HelpGenerator $generator,
        Site $site,
        Manager $extensionManager,
        ChainQueue $chainQueue
    ) {
        $this->generator = $generator;
        $this->site = $site;
        $this->extensionManager = $extensionManager;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:help')
            ->setDescription($this->trans('commands.generate.help.description'))
            ->setHelp($this->trans('commands.generate.help.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.description')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');

        if ($this->extensionManager->validateModuleFunctionExist($module, $module . '_help')) {
            throw new \Exception(
                sprintf(
                    $this->trans('commands.generate.help.messages.help-already-implemented'),
                    $module
                )
            );
        }

        $description = $input->getOption('description');

        $this
            ->generator
            ->generate($module, $description);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->site->loadLegacyFile('/core/includes/update.inc');
        $this->site->loadLegacyFile('/core/includes/schema.inc');

        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        $description = $input->getOption('description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.module.questions.description'),
                'My Awesome Module'
            );
        }
        $input->setOption('description', $description);
    }
}
