<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/User for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ClientTest;

use Client\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase
{

    public function testParseString()
    {
        $map = array(
            "x=1&y=2" => array('x'=> 1, 'y'=> 2)
        );

        foreach ($map as $string => $array) {
            $data = array();
            Utils::parseString($string, $data);
            $this->assertEquals($data, $array);
        }
    }
}
