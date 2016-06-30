<?php
/**
 * @file
 * Contains Drupal\Console\Command\Shared\RestTrait.
 */

namespace Drupal\Console\Command\Shared;

trait RestTrait {

  public function getRestDrupalConfig()
  {
    $configFactory = $this->getDrupalService('config.factory');
    if (!$configFactory) {
      return null;
    }

    return $configFactory->get('rest.settings')->get('resources') ?: [];
  }

  /**
   * [geRest get a list of Rest Resouces].
   *
   * @param bool $rest_status return Rest Resources by status
   *
   * @return array list of rest resources
   */
  public function getRestResources($rest_status = false)
  {
    $config = $this->getRestDrupalConfig();

    $resourcePluginManager = $this->getDrupalService('plugin.manager.rest');
    /* @var Drupal\rest\Plugin\Type\ResourcePluginManager $resources */
    $resources = $resourcePluginManager->getDefinitions();

    $enabled_resources = array_combine(array_keys($config), array_keys($config));
    $available_resources = array('enabled' => array(), 'disabled' => array());

    foreach ($resources as $id => $resource) {
      $status = in_array($id, $enabled_resources) ? 'enabled' : 'disabled';
      $available_resources[$status][$id] = $resource;
    }

    // Sort the list of resources by label.
    $sort_resources = function ($resource_a, $resource_b) {
      return strcmp($resource_a['label'], $resource_b['label']);
    };
    if (!empty($available_resources['enabled'])) {
      uasort($available_resources['enabled'], $sort_resources);
    }
    if (!empty($available_resources['disabled'])) {
      uasort($available_resources['disabled'], $sort_resources);
    }

    if (isset($available_resources[$rest_status])) {
      return array($rest_status => $available_resources[$rest_status]);
    }

    return $available_resources;
  }

  /**
   * @param $rest
   * @param $rest_resources_ids
   * @param $translator
   *
   * @return mixed
   */
  public function validateRestResource($rest, $rest_resources_ids, $translator)
  {
    if (in_array($rest, $rest_resources_ids)) {
      return $rest;
    } else {
      throw new \InvalidArgumentException(
        sprintf(
          $translator->trans('commands.rest.disable.messages.invalid-rest-id'),
          $rest
        )
      );
    }
  }

}
