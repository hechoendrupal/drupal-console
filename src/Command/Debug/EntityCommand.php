<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Debug\EntityCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class EntityCommand extends Command
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
     * EntityCommand constructor.
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
            ->setName('debug:entity')
            ->setDescription($this->trans('commands.debug.entity.description'))
            ->addArgument(
                'entity-type',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.entity.arguments.entity-type')
            )->setAliases(['de']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityType = $input->getArgument('entity-type');

        $tableHeader = [
            $this->trans('commands.debug.entity.table-headers.entity-name'),
            $this->trans('commands.debug.entity.table-headers.entity-type')
        ];
        $tableRows = [];

        $entityTypesLabels = $this->entityTypeRepository->getEntityTypeLabels(true);

        if ($entityType) {
            $entityTypes = [$entityType => $entityType];
        } else {
            $entityTypes = array_keys($entityTypesLabels);
        }

        foreach ($entityTypes as $entityTypeId) {
            $entities = array_keys($entityTypesLabels[$entityTypeId]);
            foreach ($entities as $entity) {
                $tableRows[$entity] = [
                    $entity,
                    $entityTypeId
                ];
            }
        }

        $this->getIo()->table($tableHeader, array_values($tableRows));
    }
}
