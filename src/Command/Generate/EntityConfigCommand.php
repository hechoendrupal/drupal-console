<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityConfigCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\EntityConfigGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Core\Utils\StringConverter;

class EntityConfigCommand extends EntityCommand
{
    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var EntityConfigGenerator
*/
    protected $generator;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * EntityConfigCommand constructor.
     *
     * @param Manager               $extensionManager
     * @param EntityConfigGenerator $generator
     * @param Validator             $validator
     * @param StringConverter       $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        EntityConfigGenerator $generator,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setEntityType('EntityConfig');
        $this->setCommandName('generate:entity:config');
        parent::configure();
        $this->addOption(
            'bundle-of',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.config.options.bundle-of')
        )
            ->setAliases(['gec']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $entity_class = $input->getOption('entity-class');
        $entity_name = $input->getOption('entity-name');
        $label = $input->getOption('label');
        $bundle_of = $input->getOption('bundle-of');
        $base_path = $input->getOption('base-path');

        $this->generator->generate([
            'module' => $module,
            'entity_name' => $entity_name,
            'entity_class' => $entity_class,
            'label' => $label,
            'base_path' => $base_path,
            'bundle_of' => $bundle_of,
        ]);
    }
}
