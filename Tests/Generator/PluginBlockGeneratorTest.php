<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginBlockGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginBlockGenerator;

class PluginBlockGeneratorTest extends GeneratorTest
{
  /**
   * @dataProvider commandData
   */
  public function testGeneratePluginBlock($parameters)
  {
    $this->setUpTemporalDirectory();

    list($module, $class_name, $plugin_label, $plugin_id, $services, $inputs) = $parameters;
    $this->getGenerator()->generate($module, $class_name, $plugin_label, $plugin_id, $services, $inputs);

    $this->assertTrue(
      file_exists($this->dir . '/src/Plugin/Block/' . $class_name .'.php'),
      sprintf('%s has been generated', $this->dir . '/src/Plugin/Block/'.$class_name.'.php')
    );

    $contains = [
      'build',
      '@Block',
      'id',
      'admin_label',
      $class_name . ' extends BlockBase',
    ];

    if ($inputs) {
      $contains += [
        'blockForm()',
        'blockForm($form, &$form_state)',
        'blockSubmit()',
        'blockSubmit($form, &$form_state)',
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

    $content = file_get_contents($this->dir . '/src/Plugin/Block/' . $class_name .'.php');
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
      ]
    ];

    return [
      [
        ['foo', 'FooBlock', 'Foo Block', 'foo_block', null, []]
      ],
      [
        ['foo', 'FooBlock', 'Foo Block', 'foo_block', $services, []]
      ],
      [
        ['foo', 'FooBlock', 'Foo Block', 'foo_block', null, $inputs]
      ],
      [
        ['foo', 'FooBlock', 'Foo Block', 'foo_block', $services, $inputs]
      ],
    ];
  }

  protected function getGenerator()
  {
    $generator = $this->getMockBuilder('\Drupal\AppConsole\Generator\PluginBlockGenerator')
      ->setMethods(['getPluginPath'])
      ->getMock();

    $generator->setSkeletonDirs($this->getSkeletonDirs());

    $generator->expects($this->once())
      ->method('getPluginPath')
      ->will($this->returnValue($this->dir . '/src/Plugin/Block'));

    return $generator;
  }
} 