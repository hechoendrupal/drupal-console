<?php

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;

/**
 * Class DockerizeGenerator
 *
 * @package Drupal\Console\Generator
 */
class DockerInitGenerator extends Generator
{

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $parameters['volume_configuration'] = $this->getVolumeConfiguration();

        $dockerComposeFile = $parameters['docker_compose_file'];
        unset($parameters['docker_compose_file']);

        $this->renderFile(
            'files/docker-compose.yml.twig',
            $dockerComposeFile,
            $parameters
        );
    }

    protected function getVolumeConfiguration() {
        $volumeConfiguration = [
            'darwin' => ':cached'
        ];

        $osType = strtolower(PHP_OS);

        return array_key_exists($osType, $volumeConfiguration)?$volumeConfiguration[$osType]:'';
    }
}
