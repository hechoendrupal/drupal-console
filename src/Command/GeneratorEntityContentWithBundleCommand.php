<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorEntityContentCommand.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Generator\EntityConfigGenerator;
use Drupal\Console\Generator\EntityContentWithBundleGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorEntityContentWithBundleCommand extends GeneratorEntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content:bundled');
        parent::configure();
    }

    public function getGenerator($generator = null)
    {
        if (!$this->generator) {
            $this->generator = $generator;
            $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
            $this->getRenderHelper()->setTranslator($this->getTranslator());
            $this->generator->setHelperSet($this->getHelperSet());
        }
        return $this->generator;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $content_entity_class = $input->getOption('entity-class');
        $content_entity_name = $input->getOption('entity-name');
        $content_entity_label = $input->getOption('label');

        $this
            ->getGenerator(new EntityContentWithBundleGenerator())
            ->generate($module, $content_entity_name, $content_entity_class, $content_entity_label, $content_entity_name . '_type');

        // @todo: we need to properly chain, this is very hacky.
        unset($this->generator);

        $config_entity_class = $content_entity_class . 'Type';
        $config_entity_name = $content_entity_name . '_type';
        $config_entity_label = $content_entity_label . ' type';
        $this
            ->getGenerator(new EntityConfigGenerator())
            ->generate($module, $config_entity_name, $config_entity_class, $config_entity_label, $content_entity_name);
    }
}
