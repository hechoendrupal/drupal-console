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
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;


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
            )
            ->addOption(
                'title_words_min',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.content.arguments.title-words-min')
            )
            ->addOption(
                'initial_creation_date',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.content.arguments.start-creation-date')
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

        $titleWordsMin = $input->getOption('title_words_min');
        if (!$titleWordsMin) {
            $titleWordsMin = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.content.questions.title-words-min'), ''),
                ''
            );
        }
        $input->setOption('title_words_min', $titleWordsMin);

        $initialCreationDate = $input->getOption('initial_creation_date');
        if (!$initialCreationDate) {
            $initialCreationDate = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.content.questions.initial-creation-date'), ''),
                ''
            );
        }
        $input->setOption('initial_creation_date', $initialCreationDate);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        $contentTypes = $input->getArgument('content_types');
        $limit = $input->getOption('limit');
        $titleWordsMin = $input->getOption('title_words_min');
        $initialCreationDate = $input->getOption('initial_creation_date');

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
                $this->createNode($contenTypes[array_rand($contenTypes)], $titleWordsMin, $initialCreationDate, $output, $dialog);
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

    protected function createNode($contentType, $titleWordsMin, $initialCreationDate,  $output, $dialog)
    {
        $random = new Random();

        $nodeStorage = $this->getEntityManager()->getStorage('node');
        $this->uids = $this->getUsers();

        $fields = $this->getFields($contentType);

        $title = $titleWordsMin?$random->sentences(mt_rand(1, $titleWordsMin), TRUE): $random->sentences(1, TRUE);

        $nodeInfo = [
            'type' => $contentType,
            'uid' => $this->uids[array_rand($this->uids)],
            'title' => $title,
            'revision' => mt_rand(0, 1),
            'status' => TRUE,
            'promote' => mt_rand(0, 1),
            'langcode' => ''
        ];

        $entity = Node::create($nodeInfo);

        $initialTimeStamp = strtotime($initialCreationDate);
        $created = $initialTimeStamp?REQUEST_TIME - mt_rand(0, (REQUEST_TIME - $initialTimeStamp)):REQUEST_TIME;

        $entity->setCreatedTime($created);

        foreach ($fields as $field) {
            $fieldName = $field->getFieldStorageDefinition()->getName();
            print $fieldName . "\n";
            $required = $field->isRequired();
            $cardinality = $field->getFieldStorageDefinition()->getCardinality();
            print $cardinality . "\n";

            if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
                // Always set at least one value, due generate content objective is testing files
                $cardinality = rand(1, 5);
            }

            $entity->$fieldName->generateSampleItems($cardinality);

            //print_r($entity);
        }

        //$entity->comment->generateSampleItems();

        print_r($entity->field_image->getValue());
        print_r($entity->body->getValue());

        try {
            print_r(get_class($entity));
            print_r($entity->getEntityTypeId());

            $return = $entity->save();

            print 'Save return:' . $return;

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
