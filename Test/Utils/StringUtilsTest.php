<?php
namespace Drupal\AppConsole\Test\Utils;

use \Drupal\AppConsole\Utils\StringUtils;

class StringUtilsTest extends \PHPUnit_Framework_TestCase
{

  /* @var StringUtils */
  protected $stringUtil;

  protected function setUp()
  {
    $this->stringUtil = new StringUtils();
  }

  /**
   * @dataProvider getDataNames
   */
  public function testCreateMachineName($input, $machine_name)
  {
     $this->assertEquals($this->stringUtil->createMachineName($input), $machine_name);
  }

  /**
   * @dataProvider getDataCamelCaseNames
   */
  public function testCamelCaseToMachineName($camel_case, $machine_name)
  {
     $this->assertEquals($this->stringUtil->camelCaseToMachineName($camel_case), $machine_name);
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
