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
            ->setHelp($this->trans('commands.field.info.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $this->displayEntityBundleFields($io);
    }

    /**
     * Displays entities listing and fields.
     *
     * @param \Drupal\Console\Style\DrupalStyle $io
     *     An extension of SymfonyStyle for DrupalConsole.
     */
    private function displayEntityBundleFields(DrupalStyle $io)
    {

        $entityTypeManager = \Drupal::service('entity_type.manager');
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $entityList = $entityTypeManager->getDefinitions();
        $allFields = $entityFieldManager->getFieldMap();

        foreach ($entityList as $entity_type_id => $entityValue) {
            // If the Entity has bundle_entity_type set we grab it.
            $bundle_entity_type = $entityValue->get('bundle_entity_type');

            // Check to see if the entity has any bundle before continuing.
            if (!empty($bundle_entity_type)) {
                $entityTypes = $entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();

                // Override the Entity Title / Label for select entities.
                switch ($entity_type_id) {
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
                }

                // Output the Parent Entity label.
                $io->info(strtoupper($bundleParent . ':'));
                $io->newLine();

                foreach ($entityTypes as $entityType) {
                    // Load in the entityType fields.
                    $fields = $this->getBundleFields($entity_type_id, $entityType->id());

                    foreach ($fields as $field => $field_array) {
                        // Get the related / used in bundles from the field.
                        $relatedBundles = "";
                        $relatedBundlesArray = $allFields[$entity_type_id][$field]['bundles'];

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
                        $tableRows[] = [
                            $field_array->get('label'),
                            $field_array->get('field_type'),
                            // $field_array->get('description'),
                            $relatedBundles
                        ];

                        // Clear the related bundles ready for the next field.
                        unset($relatedBundles);
                    }

                    // Output the bundle label.
                    $io->info($entityType->label());

                    // Output the bundle description.
                    // $io->info(strip_tags($entityType->get('description')));

                    // If no rows exist for the fields then we display a no results message.
                    if (!empty($tableRows)) {
                        $tableHeader = [
                            $this->trans('commands.field.info.table.header-name'),
                            $this->trans('commands.field.info.table.header-type'),
                            // $this->trans('commands.field.info.table.header-desc'),
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
     * @param string $entity_type_id
     *     The entity type we want to inspect.
     * @param string bundle
     *     The bundle we want to discover the fields of.
     * @return array
     *     The fields we want to display for this content type in this entity.
     */
    private function getBundleFields($entity_type_id, $bundle)
    {
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $fields = [];

        if (!empty($entity_type_id) && !empty($bundle)) {
            $fields = array_filter(
                $entityFieldManager->getFieldDefinitions($entity_type_id, $bundle),
                function ($field_definition) {
                    return $field_definition instanceof FieldConfigInterface;
                }
            );
        }

        return $fields;
    }
}
