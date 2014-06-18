<?php
namespace Drupal\AppConsole\Test\Utils;

use \Drupal\AppConsole\Utils\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase
{

  /**
   * @dataProvider getDataNames
   */
  public function testCreateMachineName($input, $machine_name)
  {
     $this->assertEquals(Utils::createMachineName($input), $machine_name);
  }

  /**
   * @dataProvider getDataCamelCaseNames
   */
  public function testCamelCaseToMachineName($camel_case, $machine_name)
  {
     $this->assertEquals(Utils::camelCaseToMachineName($camel_case), $machine_name);
  }

  /**
   * Random strings and their equivalent machine-name
   */
  public function getDataNames()
  {
    return [
      ['Test Space between words', 'test_space_between_words'],
      ['test$special*characters!', 'test_special_characters'],
      ['URL', 'url'],
    ];
  }

  /**
   * Camel-case strings and their equivalent machine-name
   */
  public function getDataCamelCaseNames()
  {
    return [
      ['camelCase', 'camel_case'],
      ['greatestFunctionEverWritten', 'greatest_function_ever_written'],
      ['WakeUp', 'wake_up'],
    ];
  }

}
