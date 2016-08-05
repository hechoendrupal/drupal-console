<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Field\InfoCommand.
*/

namespace Drupal\Console\Command\Field;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\field\FieldConfigInterface;

/**
 * Class InfoCommand.
 */
class InfoCommand extends Command
{
    use ContainerAwareCommandTrait;

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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // Retrieve whether detailed option has been selected.
        $detailedOutput = $input->getOption('detailed');

        $entityTypeManager = \Drupal::service('entity_type.manager');
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $entityList = $entityTypeManager->getDefinitions();
        $allFields = $entityFieldManager->getFieldMap();

        foreach ($entityList as $entityTypeId => $entityValue) {
            // If the Entity has bundleEntityType set we grab it.
            $bundleEntityType = $entityValue->get('bundle_entity_type');

            // Check to see if the entity has any bundle before continuing.
            if (!empty($bundleEntityType)) {
                $entityTypes = $entityTypeManager->getStorage($bundleEntityType)->loadMultiple();

                // Override the Entity Title / Label for select entities.
                switch ($entityTypeId) {
                    case 'block_content':
                        $bundleParent = $this->trans('commands.field.info.other.section-heading-block-content');
                        break;
                    case 'comment':
                        $bundleParent = $this->trans('commands.field.info.other.section-heading-comment');
                        break;
                    case 'contact_message':
                        $bundleParent = $this->trans('commands.field.info.other.section-heading-contact-message');
                        break;
                    case 'node':
                        $bundleParent = $this->trans('commands.field.info.other.section-heading-node');
                        break;
                    case 'shortcut':
                        $bundleParent = $this->trans('commands.field.info.other.section-heading-shortcut');
                        break;
                    case 'taxonomy_term':
                        $bundleParent = $this->trans('commands.field.info.other.section-heading-taxonomy-term');
                        break;
                    default:
                        $bundleParent = $entityValue->get('label');
                        break;
                }

                // Output the Parent Entity label.
                $io->info(strtoupper($bundleParent . ':'));
                $io->newLine();

                foreach ($entityTypes as $entityType) {
                    // Load in the entityType fields.
                    $fields = $this->getBundleFields($entityTypeId, $entityType->id());

                    foreach ($fields as $field => $fieldArray) {
                        // Get the related / used in bundles from the field.
                        $relatedBundles = "";
                        $relatedBundlesArray = $allFields[$entityTypeId][$field]['bundles'];

                        // Turn those related / used in bundles array into a string.
                        foreach ($relatedBundlesArray as $relatedBundlesValue) {
                            if ($entityTypes[$relatedBundlesValue]->id() != $entityType->id()) {
                                if (!empty($relatedBundles)) {
                                    $relatedBundles .= ', ' . $entityTypes[$relatedBundlesValue]->label();
                                } else {
                                    $relatedBundles = $entityTypes[$relatedBundlesValue]->label();
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

                    // Output the bundle label.
                    $io->info($entityType->label());

                    // If detailed output then display bundle description.
                    if ($detailedOutput) {
                        $io->info(strip_tags($entityType->get('description')));
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
                        $io->commentBlock($this->trans('commands.field.info.messages.fields-none'));
                    }

                    // Clear out the rows & headers arrays to start fresh.
                    unset($tableHeader, $tableRows);

                    // Create some space so the output looks nice.
                    $io->newLine();
                }
            }
        }
    }

    /**
     * Helper function to get the field definitions.
     *
     * @param string $entityTypeId
     *     The entity type we want to inspect.
     * @param string $bundle
     *     The bundle we want to discover the fields of.
     * @return array
     *     An array of field storage definitions for the entity type,
     *     keyed by field name.
     */
    private function getBundleFields($entityTypeId, $bundle)
    {
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $fields = [];

        if (!empty($entityTypeId) && !empty($bundle)) {
            $fields = array_filter(
                $entityFieldManager->getFieldDefinitions($entityTypeId, $bundle),
                function ($fieldDefinition) {
                    return $fieldDefinition instanceof FieldConfigInterface;
                }
            );
        }

        return $fields;
    }
}
