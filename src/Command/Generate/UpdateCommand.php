<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\UpdateCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\UpdateGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;

/**
 * Class UpdateCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class UpdateCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var UpdateGenerator
     */
    protected $generator;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * UpdateCommand constructor.
     *
     * @param Manager         $extensionManager
     * @param UpdateGenerator $generator
     * @param Site            $site
     * @param ChainQueue      $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        UpdateGenerator $generator,
        Site $site,
        ChainQueue $chainQueue,
        Validator $validator
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->site = $site;
        $this->chainQueue = $chainQueue;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:update')
            ->setDescription($this->trans('commands.generate.update.description'))
            ->setHelp($this->trans('commands.generate.update.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'update-n',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.update.options.update-n')
            )->setAliases(['gu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $module = $this->validateModule($input->getOption('module'));
        $updateNumber = $input->getOption('update-n');

        $lastUpdateSchema = $this->getLastUpdate($module);

        if ($updateNumber <= $lastUpdateSchema) {
            throw new \InvalidArgumentException(
                sprintf(
                    $this->trans('commands.generate.update.messages.wrong-update-n'),
                    $updateNumber
                )
            );
        }

        $this->generator->generate([
          'module' => $module,
          'update_number' => $updateNumber,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->site->loadLegacyFile('/core/includes/update.inc');
        $this->site->loadLegacyFile('/core/includes/schema.inc');

        // --module option
        $module = $this->getModuleOption();

        $lastUpdateSchema = $this->getLastUpdate($module);
        $nextUpdateSchema = $lastUpdateSchema ? ($lastUpdateSchema + 1): 8001;

        $updateNumber = $input->getOption('update-n');
        if (!$updateNumber) {
            $updateNumber = $this->getIo()->ask(
                $this->trans('commands.generate.update.questions.update-n'),
                $nextUpdateSchema,
                function ($updateNumber) use ($lastUpdateSchema) {
                    if (!is_numeric($updateNumber)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.update.messages.wrong-update-n'),
                                $updateNumber
                            )
                        );
                    } else {
                        if ($updateNumber <= $lastUpdateSchema) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    $this->trans('commands.generate.update.messages.wrong-update-n'),
                                    $updateNumber
                                )
                            );
                        }
                        return $updateNumber;
                    }
                }
            );

            $input->setOption('update-n', $updateNumber);
        }
    }

    protected function getLastUpdate($module)
    {
        $this->site->loadLegacyFile('/core/includes/update.inc');
        $this->site->loadLegacyFile('/core/includes/schema.inc');

        $updates = update_get_update_list();

        if (empty($updates[$module]['pending'])) {
            $lastUpdateSchema = drupal_get_schema_versions($module);
            $lastUpdateSchema = $lastUpdateSchema[0];
        } else {
            $lastUpdateSchema = reset(array_keys($updates[$module]['pending'], max($updates[$module]['pending'])));
        }

        return $lastUpdateSchema;
    }
}
