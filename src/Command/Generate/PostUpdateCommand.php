<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PostUpdateCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PostUpdateGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;

/**
 * Class PostUpdateCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PostUpdateCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PostUpdateGenerator
*/
    protected $generator;

    /**
     * @var Site
     */
    protected $site;

    /**
 * @var Validator
*/
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * PostUpdateCommand constructor.
     *
     * @param Manager             $extensionManager
     * @param PostUpdateGenerator $generator
     * @param Site                $site
     * @param Validator           $validator
     * @param ChainQueue          $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PostUpdateGenerator $generator,
        Site $site,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->site = $site;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:post:update')
            ->setDescription($this->trans('commands.generate.post.update.description'))
            ->setHelp($this->trans('commands.generate.post.update.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'post-update-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.post.update.options.post-update-name')
            )->setAliases(['gpu']);
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
        $postUpdateName = $input->getOption('post-update-name');

        $this->validatePostUpdateName($module, $postUpdateName);

        $this->generator->generate([
            'module' => $module,
            'post_update_name' => $postUpdateName,
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

        $postUpdateName = $input->getOption('post-update-name');
        if (!$postUpdateName) {
            $postUpdateName = $this->getIo()->ask(
                $this->trans('commands.generate.post.update.questions.post-update-name'),
                '',
                function ($postUpdateName) {
                    return $this->validator->validateSpaces($postUpdateName);
                }
            );

            $input->setOption('post-update-name', $postUpdateName);
        }
    }

    protected function getLastUpdate($module)
    {
        $this->site->loadLegacyFile('/core/includes/update.inc');
        $this->site->loadLegacyFile('/core/includes/schema.inc');

        $updates = update_get_update_list();

        if (empty($updates[$module]['pending'])) {
            $lastUpdateSchema = drupal_get_schema_versions($module);
        } else {
            $lastUpdateSchema = reset(array_keys($updates[$module]['pending'], max($updates[$module]['pending'])));
        }

        return $lastUpdateSchema;
    }

    protected function validatePostUpdateName($module, $postUpdateName)
    {
        if (!$this->validator->validateSpaces($postUpdateName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    $this->trans('commands.generate.post.update.messages.wrong-post-update-name'),
                    $postUpdateName
                )
            );
        }

        if ($this->extensionManager->validateModuleFunctionExist($module, $module . '_post_update_' . $postUpdateName, $module . '.post_update.php')) {
            throw new \InvalidArgumentException(
                sprintf(
                    $this->trans('commands.generate.post.update.messages.post-update-name-already-implemented'),
                    $postUpdateName
                )
            );
        }
    }
}
