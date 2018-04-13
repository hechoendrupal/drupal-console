<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Entity\DeleteCommand.
 */

namespace Drupal\Console\Command\Entity;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class DeleteCommand extends Command
{
    /**
     * @var EntityTypeRepository
     */
    protected $entityTypeRepository;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * DeleteCommand constructor.
     *
     * @param EntityTypeRepository       $entityTypeRepository
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(
        EntityTypeRepository $entityTypeRepository,
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeRepository = $entityTypeRepository;
        $this->entityTypeManager = $entityTypeManager;
        parent::__construct();
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('entity:delete')
            ->setDescription($this->trans('commands.entity.delete.description'))
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.entity.delete.options.all')
            )
            ->addArgument(
                'entity-definition-id',
                InputArgument::REQUIRED,
                $this->trans('commands.entity.delete.arguments.entity-definition-id')
            )
            ->addArgument(
                'entity-id',
                InputArgument::REQUIRED,
                $this->trans('commands.entity.delete.arguments.entity-id')
            )->setAliases(['ed']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $entityDefinitionID = $input->getArgument('entity-definition-id');
        $entityID = $input->getArgument('entity-id');
        $all = $input->getOption('all');

        if (!$entityDefinitionID) {
            $entityTypes = $this->entityTypeRepository->getEntityTypeLabels(true);

            $entityType = $this->getIo()->choice(
                $this->trans('commands.entity.delete.questions.entity-type'),
                array_keys($entityTypes)
            );

            $entityDefinitionID = $this->getIo()->choice(
                $this->trans('commands.entity.delete.questions.entity-definition-id'),
                array_keys($entityTypes[$entityType])
            );

            $input->setArgument('entity-definition-id', $entityDefinitionID);
        }

        if ($all) {
            $input->setArgument('entity-id', '-');
        } elseif (!$entityID) {
            $entityID = $this->getIo()->ask(
                $this->trans('commands.entity.delete.questions.entity-id')
            );
            $input->setArgument('entity-id', $entityID);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityDefinitionID = $input->getArgument('entity-definition-id');

        try {
            $storage = $this->entityTypeManager->getStorage($entityDefinitionID);

            if ($input->getOption('all')) {
                $entities = $storage->loadMultiple();
                if ($this->getIo()->confirm(
                    sprintf(
                        $this->trans('commands.entity.delete.messages.confirm-delete-all'),
                        $entityDefinitionID,
                        count($entities)
                    )
                )
                ) {
                    $storage->delete($entities);
                    $this->getIo()->success(
                        sprintf(
                            $this->trans('commands.entity.delete.messages.deleted-all'),
                            $entityDefinitionID,
                            count($entities)
                        )
                    );
                }
            } else {
                $entityID = $input->getArgument('entity-id');
                $storage->load($entityID)->delete();
                $this->getIo()->success(
                    sprintf(
                        $this->trans('commands.entity.delete.messages.deleted'),
                        $entityDefinitionID,
                        $entityID
                    )
                );
            }
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }
    }
}
