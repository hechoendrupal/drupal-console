<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Entity\DebugCommand.
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

class DebugCommand extends Command
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
            ->setName('entity:debug')
            ->setDescription($this->trans('commands.entity.debug.description'))
            ->addArgument(
                'entity-type',
                InputArgument::OPTIONAL,
                $this->trans('commands.entity.debug.arguments.entity-type')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $entityType = $input->getArgument('entity-type');

        $tableHeader = [
            $this->trans('commands.entity.debug.table-headers.entity-name'),
            $this->trans('commands.entity.debug.table-headers.entity-type')
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

        $io->table($tableHeader, array_values($tableRows));
    }
}
