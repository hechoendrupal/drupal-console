<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginBlockGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

class PluginBlockGeneratorTest extends GeneratorTest
{
  /**
   * @dataProvider commandData
   */
  public function testGeneratePluginBlock($parameters)
  {
    $this->setUpTemporalDirectory();

    // Get parameters
    list($module, $class_name, $label, $plugin_id, $services, $inputs) = $parameters;

    // Get generator
    $generator = $this->getGenerator();

    $dir_module = $this->dir . '/' . $module;
    // Set plugin path.
    $generator->expects($this->once())
      ->method('getPluginPath')
      ->will($this->returnValue(
          $dir_module . '/src/Plugin/Block')
      );

    // Generate plugin block
    $generator->generate($module, $class_name, $label, $plugin_id, $services, $inputs);

    $this->assertTrue(
      file_exists($dir_module . '/src/Plugin/Block/' . $class_name .'.php'),
      sprintf('%s has been generated', $dir_module . '/src/Plugin/Block/'.$class_name.'.php')
    );

    $contains = [
      'build',
      '@Block',
      'id',
      'label',
      $class_name . ' extends BlockBase',
    ];

    if ($inputs) {
      $contains += [
        'blockForm()',
        'blockForm($form, FormStateInterface $form_state)',
        'blockSubmit()',
        'blockSubmit($form, FormStateInterface $form_state)',
      ];
    }

    if ($services) {
      $contains += [
        '__construct()',
        'create()',
        'create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)',
        'implements ContainerFactoryPluginInterface',
      ];
    }

    $content = file_get_contents($dir_module . '/src/Plugin/Block/' . $class_name .'.php');
    foreach ($contains as $contain) {
      $this->assertContains($contain, $content);
    }
  }

  public function commandData()
  {
    $services = [
      'twig' => [
        'name' => 'twig',
        'machine_name' => 'twig',
        'class' => 'Twig_Environment',
        'short'=>'Twig_Environment',
      ]
    ];

    $inputs = [
      [
        'name' => 'foo',
        'type' => 'textfield',
        'label' => 'Foo',
        'description' => 'Foo',
      ]
    ];

    return [
      [
        ['plugin_block' . rand(), 'FooBlock', 'Foo Block', 'foo_block', null, []]
      ],
      [
        ['plugin_block' . rand(), 'FooBlock', 'Foo Block', 'foo_block', $services, []]
      ],
      [
        ['plugin_block' . rand(), 'FooBlock', 'Foo Block', 'foo_block', null, $inputs]
      ],
      [
        ['plugin_block' . rand(), 'FooBlock', 'Foo Block', 'foo_block', $services, $inputs]
      ],
    ];
  }

  protected function getGenerator()
  {
    $generator = $this->getMockBuilder('\Drupal\AppConsole\Generator\PluginBlockGenerator')
      ->setMethods(['getPluginPath'])
      ->getMock();

    $generator->setSkeletonDirs($this->getSkeletonDirs());

    return $generator;
  }
} 
