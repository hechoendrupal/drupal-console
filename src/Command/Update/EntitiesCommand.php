<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\EntitiesCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Utility\Error;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManager;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class EntitiesCommand.
 *
 * @package Drupal\Console\Command\Update
 */
class EntitiesCommand extends Command
{
    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * @var EntityDefinitionUpdateManager
     */
    protected $entityDefinitionUpdateManager;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * EntitiesCommand constructor.
     *
     * @param StateInterface                $state
     * @param EntityDefinitionUpdateManager $entityDefinitionUpdateManager
     * @param ChainQueue                    $chainQueue
     */
    public function __construct(
        StateInterface $state,
        EntityDefinitionUpdateManager $entityDefinitionUpdateManager,
        ChainQueue $chainQueue
    ) {
        $this->state = $state;
        $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('update:entities')
            ->setDescription($this->trans('commands.update.entities.description'))
            ->enableMaintenance()
            ->setAliases(['upe']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->entityDefinitionUpdateManager->applyUpdates();
            /* @var EntityStorageException $e */
        } catch (EntityStorageException $e) {
            /* @var Error $variables */
            $variables = Error::decodeException($e);
            $this->getIo()->errorLite($this->trans('commands.update.entities.messages.error'));
            $this->getIo()->error(strtr('%type: @message in %function (line %line of %file).', $variables));
        }

        $this->getIo()->info($this->trans('commands.update.entities.messages.end'));
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
