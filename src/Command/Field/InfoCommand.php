<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Field\InfoCommand.
*/

namespace Drupal\Console\Command\Field;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class InfoCommand.
 */
class InfoCommand extends Command
{
    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    /**
     * InfoCommand constructor.
     *
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param EntityFieldManagerInterface $entityFieldManager
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        parent::__construct();
    }

    /**
     * @{@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('field:info')
            ->setDescription($this->trans('commands.field.info.description'))
            ->setHelp($this->trans('commands.field.info.help'))
            ->addOption(
                'detailed',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.field.info.options.detailed')
            )
            ->addOption(
                'entity',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.field.info.options.entity')
            )
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.field.info.options.bundle')
            )->setAliases(['fii']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // Retrieve whether detailed option has been selected.
        $detailedOutput = $input->getOption('detailed');

        // Retrieve whether an entity type has been specified.
        $entityTypeOption = $input->getOption('entity');

        // Retrieve whether a specific bundle type has been specified.
        $bundleTypeOption = $input->getOption('bundle');

        $entityList = $this->entityTypeManager->getDefinitions();
        $allFields = $this->entityFieldManager->getFieldMap();

        // Set a flag so we can error if a specific entity type selected but not found.
        $entityTypeOptionFound = false;

        // Set a flag so we can error if a specific bundle type selected but not found.
        $bundleTypeOptionFound = false;

        // Let's count the fields found so we can display a message if none found.
        $fieldCounter = 0;

        foreach ($entityList as $entityTypeId => $entityValue) {
            // If the Entity has bundleEntityType set we grab it.
            $bundleEntityType = $entityValue->get('bundle_entity_type');

            // Check to see if the entity has any bundle before continuing.
            if (!empty($bundleEntityType)) {
                $bundleTypes = $this->entityTypeManager
                    ->getStorage($bundleEntityType)->loadMultiple();

                // If a specific entity type has been selected and this is it then we continue else we skip.
                if ((!empty($entityTypeOption) && ($entityTypeOption == $entityTypeId))| empty($entityTypeOption)
                ) {
                    // Store the fact that we found the entity type specified so we can error if not found.
                    $entityTypeOptionFound = true;

                    // Get the entity type label.
                    $bundleParent = $entityValue->get('label');

                    // Using counter to know whether to output header.
                    $bundleTypeCounter = 0;
                    foreach ($bundleTypes as $bundleType) {
                        // If a specific bundle type has been selected and this is it then we continue else we skip.
                        if ((!empty($bundleTypeOption) && ($bundleTypeOption == $bundleType->id()))| empty($bundleTypeOption)
                        ) {
                            // Store the fact that we found the bundle type specified so we can error if not found.
                            $bundleTypeOptionFound = true;

                            // Increase the bundle type counter so we know whether to output header.
                            $bundleTypeCounter++;

                            if ($bundleTypeCounter == 1) {
                                // Output the Parent Entity label if we haven't already.
                                if ($detailedOutput) {
                                    // If detailed output then display the id as well.
                                    $io->info(strtoupper($bundleParent) . ' (' . $entityTypeId . '):');
                                } else {
                                    // otherwise just display the label for normal output.
                                    $io->info(strtoupper($bundleParent . ':'));
                                }
                                $io->newLine();
                            }

                            // Load in the entityType fields.
                            $fields = $this->getBundleFields(
                                $entityTypeId,
                                $bundleType->id()
                            );

                            foreach ($fields as $field => $fieldArray) {
                                // We found a field so increase the field counter.
                                $fieldCounter++;

                                // Get the related / used in bundles from the field.
                                $relatedBundles = "";
                                $relatedBundlesArray = $allFields[$entityTypeId][$field]['bundles'];

                                // Turn those related / used in bundles array into a string.
                                foreach ($relatedBundlesArray as $relatedBundlesValue) {
                                    if ($bundleTypes[$relatedBundlesValue]->id() != $bundleType->id()) {
                                        if (!empty($relatedBundles)) {
                                            $relatedBundles .= ', ' . $bundleTypes[$relatedBundlesValue]->label();
                                        } else {
                                            $relatedBundles = $bundleTypes[$relatedBundlesValue]->label();
                                        }
                                    }
                                }

                                // Build out our table for the fields.
                                $tableRows[] = $detailedOutput ? [
                                    $fieldArray->get('label'),
                                    $fieldArray->get('field_type'),
                                    $fieldArray->get('description'),
                                    $relatedBundles
                                ] : [
                                    $fieldArray->get('label'),
                                    $fieldArray->get('field_type'),
                                    $relatedBundles
                                ];

                                // Clear the related bundles ready for the next field.
                                unset($relatedBundles);
                            }

                            // If detailed output then display bundle id and description.
                            if ($detailedOutput) {
                                // Output the bundle label and id.
                                $io->info($bundleType->label() . ' (' . $bundleType->id() . ')');
                                $io->info(strip_tags($bundleType->get('description')));
                            } else {
                                // Else just output the bundle label.
                                $io->info($bundleType->label());
                            }

                            // Fill out our table header.
                            // If no rows exist for the fields then we display a no results message.
                            if (!empty($tableRows)) {
                                $tableHeader = $detailedOutput ? [
                                    $this->trans('commands.field.info.table.header-name'),
                                    $this->trans('commands.field.info.table.header-type'),
                                    $this->trans('commands.field.info.table.header-desc'),
                                    $this->trans('commands.field.info.table.header-usage')
                                ] : [
                                    $this->trans('commands.field.info.table.header-name'),
                                    $this->trans('commands.field.info.table.header-type'),
                                    $this->trans('commands.field.info.table.header-usage')
                                ];
                                $io->table($tableHeader, $tableRows);
                            } else {
                                $io->comment(
                                    $this->trans('commands.field.info.messages.fields-none')
                                    . ' ' . $this->trans('commands.field.info.messages.in-bundle-type')
                                    . " '" . $bundleType->label() . "'"
                                );
                            }

                            // Clear out the rows & headers arrays to start fresh.
                            unset($tableHeader, $tableRows);

                            // Create some space so the output looks nice.
                            $io->newLine();
                        }
                    }
                }
            }
        }

        // If entity type was specified but not found then display error message.
        if (!empty($entityTypeOption)) {
            if (!$entityTypeOptionFound) {
                $io->comment(
                    $this->trans('commands.field.info.messages.entity-type') .
                    ' ' . $entityTypeOption . ' ' .
                    $this->trans('commands.field.info.messages.not-found')
                );
            } elseif (!empty($bundleTypeOption) && !$bundleTypeOptionFound) {
                // If specified entity type found and bundle type specified but not found then display error message.
                $io->comment(
                    $this->trans('commands.field.info.messages.bundle-type') .
                    ' ' . $bundleTypeOption . ' ' .
                    $this->trans('commands.field.info.messages.not-found') .
                    ' ' . $this->trans('commands.field.info.messages.in-entity-type') .
                    ' ' . $entityTypeOption
                );
            }
        } elseif (!empty($bundleTypeOption) && !$bundleTypeOptionFound) {
            // If specified bundle type not found then display error message.
            $io->comment(
                $this->trans('commands.field.info.messages.bundle-type') .
                ' ' . $bundleTypeOption . ' ' .
                $this->trans('commands.field.info.messages.not-found')
            );
        } elseif ($fieldCounter == 0) {
            // If no fields found then display appropriate message.
            $io->comment($this->trans('commands.field.info.messages.fields-none'));
        }

        return 0;
    }

    /**
     * Helper function to get the field definitions.
     *
     * @param  string $entityTypeId
     *     The entity type we want to inspect.
     * @param  string $bundle
     *     The bundle we want to discover the fields of.
     * @return array
     *     An array of field storage definitions for the entity type,
     *     keyed by field name.
     */
    private function getBundleFields($entityTypeId, $bundle)
    {
        $fields = [];
        if (!empty($entityTypeId) && !empty($bundle)) {
            $fields = array_filter(
                $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle),
                function ($fieldDefinition) {
                    return $fieldDefinition instanceof FieldConfigInterface;
                }
            );
        }

        return $fields;
    }
}
