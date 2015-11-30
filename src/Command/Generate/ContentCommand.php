<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ContentCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\ModuleTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Yaml\Dumper;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\field\FieldConfigInterface;
use Drupal\node\Entity\Node;
use Faker\Factory;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class ContentCommand extends ContainerAwareCommand
{
    protected $entityManager;
    protected $configStorage;
    protected $contentTypes = [];
    protected $contentTypesObject = [];
    protected $uids;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:content')
            ->setDescription($this->trans('commands.generate.content.description'))
            ->addArgument(
                'content_types',
                InputArgument::IS_ARRAY,
                $this->trans('commands.generate.content.arguments.content_types')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.content.arguments.limit')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        $this->getContentTypes();

        // --content type argument
        $contenTypes = $input->getArgument('content_types');
        if (!$contenTypes) {
            $bundles = $this->contentTypes;
            $contentTypes = [];
            while (true) {
                $contentType = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion($this->trans('commands.generate.content.questions.content-type'), ''),
                    function ($bundle) use ($bundles) {
                        if (!empty($bundle) && !in_array($bundle, array_values($bundles))) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    'Content type "%s" is invalid.',
                                    $bundle
                                )
                            );
                        }

                        return array_search($bundle, $bundles);
                    },
                    false,
                    '',
                    $bundles
                );

                if (empty($contentType) and count($contentTypes) > 0) {
                    break;
                } elseif (!empty($contentType)) {
                    $contentTypes[] = $contentType;
                }
            }
        }

        $input->setArgument('content_types', $contentTypes);

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.content.questions.limit'), '10'),
                '10'
            );
        }
        $input->setOption('limit', $limit);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        $contentTypes = $input->getArgument('content_types');
        $limit = $input->getOption('limit');

        if (!$limit) {
            $limit = 10;
        }

        /*print 'Content types:';
        print_r($contentTypes);
        print '\n';*/


        $this->getContentTypes();

        //print_r($this->contentTypes);

        if ($contenTypes = array_intersect(array_keys($this->contentTypes), $contentTypes)) {
            for ($i=0; $i<$limit; $i++) {
                $this->createNode($contenTypes[array_rand($contenTypes)], $output, $dialog);
            }
        } else {
            $output->writeln(
                $dialog->getFormatterHelper()->formatBlock(
                    sprintf(
                        $this->trans('commands.generate.content.messages.invalid-content-type'),
                        implode(",", $contenTypes)
                    ), 'error'
                )
            );
        }
        return;
    }

    protected function getContentTypes()
    {
        if (empty($this->contentTypes)) {
            $this->entityManager = $this->getEntityManager();

            $bundles_entities = $this->entityManager->getStorage('node_type')->loadMultiple();

            foreach ($bundles_entities as $entity) {
                $this->contentTypes[$entity->id()] = $entity->label();
                $this->contentTypesObject[$entity->id()] = $entity;
            }
        }

        return $this->contentTypes;
    }
    protected function getFields($contentType)
    {
        $fields = array_filter(
            $this->entityManager->getFieldDefinitions('node', $contentType), function ($field_definition) {
                return $field_definition instanceof FieldConfigInterface;
            }
        );

        return $fields;
    }

    protected function createNode($contentType, $output, $dialog)
    {
        $this->uids = $this->getUsers();

        $faker = Factory::create();

        $fields = $this->getFields($contentType);

        $node = [
            'type' => $contentType,
            'uid' => $this->uids[array_rand($this->uids)],
            'title' => $faker->sentence,
        ];

        $entity = entity_create('node', $node);

        foreach ($fields as $field) {
            $fieldName = $field->getName();
            $required = $field->isRequired();
            $cardinality = $cardinality = $field->getFieldStorageDefinition();

            if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
                if ($required) {
                    // Always set at least one value, due generate content objective is testing files
                    $cardinality = rand(1, 5);
                }
            }

            $entity->$fieldName->generateSampleItems($cardinality);
        }

        try {
            $entity->save();

            $output->writeln(
                $dialog->getFormatterHelper()->formatBlock(
                    sprintf(
                        $this->trans('commands.generate.content.messages.generated-content-type'),
                        ucfirst($contentType),
                        $entity->getTitle()
                    ), 'info'
                )
            );
        } catch (\Exception $error) {
            $output->writeln(
                $dialog->getFormatterHelper()->formatBlock(
                    $error->getMessage(), 'error'
                )
            );
        }
    }

    /**
     * Retrive 50 uids of enabled users from the database using Entity Query
     */
    protected function getUsers()
    {
        $query = $this->getEntityQuery()->get('user');
        $query->pager(50);
        $query->condition('status', true);

        $users = $query->execute();

        return $users;
    }
}
