<?php

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Generator\GeneratorInterface;

/**
 * Class PluginQueueWorkerGenerator.
 *
 * @package Drupal\Console\Generator
 */
class PluginQueueWorkerGenerator extends Generator implements GeneratorInterface {

  /**
   * Extension Manager.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * PluginQueueWorker constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   Extension manager.
   */
  public function __construct(
       Manager $extensionManager
   ) {
    $this->extensionManager = $extensionManager;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters) {
    $module = $parameters['module'];
    $queue_class = $parameters['class_name'];

    $this->renderer->addSkeletonDir(__DIR__ . '/../../console/templates');
    $this->renderFile(
      'module/src/Plugin/QueueWorker/queue_worker.php.twig',
      $this->extensionManager->getPluginPath($module, 'QueueWorker') . '/' . $queue_class . '.php',
      $parameters
    );
  }

}
