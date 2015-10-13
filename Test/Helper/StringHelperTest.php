<?php
namespace Drupal\Console\Test\Helper;

use Drupal\Console\Helper\StringHelper;

class StringHelperTest extends \PHPUnit_Framework_TestCase
{
    /* @var $stringHelper */
    protected $stringHelper;

    protected function setUp()
    {
        $this->stringHelper = new StringHelper();
    }

    /**
     * @dataProvider getDataNames
     */
    public function testCreateMachineName($input, $machine_name)
    {
        $this->assertEquals($this->stringHelper->createMachineName($input), $machine_name);
    }

    /**
     * @dataProvider getDataCamelCaseNames
     */
    public function testCamelCaseToMachineName($camel_case, $machine_name)
    {
        $this->assertEquals($this->stringHelper->camelCaseToMachineName($camel_case), $machine_name);
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
