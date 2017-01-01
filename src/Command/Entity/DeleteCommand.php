<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Entity\DeleteCommand.
 */

namespace Drupal\Console\Command\Entity;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class DeleteCommand extends Command
{
    use CommandTrait;

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
            ->addArgument(
                'entity-definition-id',
                InputArgument::REQUIRED,
                $this->trans('commands.entity.delete.arguments.entity-definition-id')
            )
            ->addArgument(
                'entity-id',
                InputArgument::REQUIRED,
                $this->trans('commands.entity.delete.arguments.entity-id')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $entityDefinitionID = $input->getArgument('entity-definition-id');
        $entityID = $input->getArgument('entity-id');

        if (!$entityDefinitionID) {
            $entityTypes = $this->entityTypeRepository->getEntityTypeLabels(true);

            $entityType = $io->choice(
                $this->trans('commands.entity.delete.questions.entity-type'),
                array_keys($entityTypes)
            );

            $entityDefinitionID = $io->choice(
                $this->trans('commands.entity.delete.questions.entity-definition-id'),
                array_keys($entityTypes[$entityType])
            );

            $input->setArgument('entity-definition-id', $entityDefinitionID);
        }

        if (!$entityID) {
            $entityID = $io->ask(
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
        $io = new DrupalStyle($input, $output);

        $entityDefinitionID = $input->getArgument('entity-definition-id');
        $entityID = $input->getArgument('entity-id');

        try {
            $this->entityTypeManager->getStorage($entityDefinitionID)->load($entityID)->delete();
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.entity.delete.messages.deleted'),
                $entityDefinitionID,
                $entityID
            )
        );
    }
}
