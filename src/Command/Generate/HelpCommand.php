<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\HelpCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Generator\HelpGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Validator;

class HelpCommand extends Command
{
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
     * @var Validator
     */
    protected $validator;


    /**
     * HelpCommand constructor.
     *
     * @param HelpGenerator $generator
     * @param Site          $site
     * @param Manager       $extensionManager
     * @param ChainQueue    $chainQueue
     */
    public function __construct(
        HelpGenerator $generator,
        Site $site,
        Manager $extensionManager,
        ChainQueue $chainQueue,
        Validator $validator
    ) {
        $this->generator = $generator;
        $this->site = $site;
        $this->extensionManager = $extensionManager;
        $this->chainQueue = $chainQueue;
        $this->validator = $validator;
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
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.help.options.description')
            )->setAliases(['gh']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $module = $this->validateModule($input->getOption('module'));

        if ($this->extensionManager->validateModuleFunctionExist($module, $module . '_help')) {
            throw new \Exception(
                sprintf(
                    $this->trans('commands.generate.help.messages.help-already-implemented'),
                    $module
                )
            );
        }

        $description = $input->getOption('description');

        $this->generator->generate([
          'machine_name' => $module,
          'description' => $description,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->site->loadLegacyFile('/core/includes/update.inc');
        $this->site->loadLegacyFile('/core/includes/schema.inc');

        // --module option
        $this->getModuleOption();

        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.help.questions.description'),
                $this->trans('commands.generate.module.suggestions.my-awesome-module')
            );
        }
        $input->setOption('description', $description);
    }
}
