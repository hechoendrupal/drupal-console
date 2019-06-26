<?php

namespace Drupal\Console;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Console\Annotations\DrupalCommandAnnotationReader;
use Drupal\Console\Utils\AnnotationValidator;
use Drupal\Console\Core\Application as BaseApplication;

/**
 * Class Application
 *
 * @package Drupal\Console
 */
class Application extends BaseApplication
{
    /**
     * @var string
     */
    const NAME = 'Drupal Console';

    /**
     * @var string
     */
    const VERSION = '1.9.1';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, $this::NAME, $this::VERSION);
    }

    /**
     * Returns the long version of the application.
     *
     * @return string The long application version
     */
    public function getLongVersion()
    {
        $output = '';

        if ('UNKNOWN' !== $this->getName()) {
            if ('UNKNOWN' !== $this->getVersion()) {
                $output .= sprintf('<info>%s</info> version <comment>%s</comment>', $this->getName(), $this->getVersion());
            } else {
                $output .= sprintf('<info>%s</info>', $this->getName());
            }
        } else {
            $output .= '<info>Drupal Console</info>';
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->validateCommands();

        return parent::doRun($input, $output);
    }

    public function validateCommands()
    {
        $consoleCommands = $this->container
            ->findTaggedServiceIds('drupal.command');

        if (!$consoleCommands) {
            return;
        }

        $serviceDefinitions = $this->container->getDefinitions();

        if (!$serviceDefinitions) {
            return;
        }

        if (!$this->container->has('console.annotation_command_reader')) {
            return;
        }

        /**
         * @var DrupalCommandAnnotationReader $annotationCommandReader
         */
        $annotationCommandReader = $this->container
            ->get('console.annotation_command_reader');

        if (!$this->container->has('console.annotation_validator')) {
            return;
        }

        /**
         * @var AnnotationValidator $annotationValidator
         */
        $annotationValidator = $this->container
            ->get('console.annotation_validator');

        $invalidCommands = [];

        foreach ($consoleCommands as $name => $tags) {
            AnnotationRegistry::reset();
            AnnotationRegistry::registerLoader(
                [
                    $this->container->get('class_loader'),
                    "loadClass"
                ]
            );

            if (!$this->container->has($name)) {
                $invalidCommands[] = $name;
                continue;
            }

            if (!$serviceDefinition = $serviceDefinitions[$name]) {
                $invalidCommands[] = $name;
                continue;
            }

            if (!$annotationValidator->isValidCommand(
                $serviceDefinition->getClass()
            )
            ) {
                $invalidCommands[] = $name;
                continue;
            }

            $annotation = $annotationCommandReader
                ->readAnnotation($serviceDefinition->getClass());
            if ($annotation) {
                $this->container->get('console.translator_manager')
                    ->addResourceTranslationsByExtension(
                        $annotation['extension'],
                        $annotation['extensionType']
                    );
            }
        }

        $this->container
            ->get('console.key_value_storage')
            ->set('invalid_commands', $invalidCommands);

        return;
    }
}
