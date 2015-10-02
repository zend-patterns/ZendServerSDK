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
    
    public function testArrayKV()
    {
        $jsonData = '{
                     "responseData":{
                        "vhostList":[
                            {"id":"2","name":"gotcms.staging",
                            "port":"80","status":"Ok","default":"0",
                            "zendDefined":true,"zendManaged":true,"ssl":false,
                            "created":"2015-07-15T12:43:28+00:00",
                            "lastUpdated":"2015-07-23T11:53:18+00:00",
                            "createdTimestamp":"1436964208",
                            "lastUpdatedTimestamp":"1437652398",
                            "servers":[{"id":"0",
                                        "status":"Ok",
                                        "name":"web-staging",
                                        "lastMessage":""}]}], 
                       "total": 1
                   }}';
        $expectedOutput = "vhostList[0][id]=2
vhostList[0][name]=gotcms.staging
vhostList[0][port]=80
vhostList[0][status]=Ok
vhostList[0][default]=0
vhostList[0][zendDefined]=1
vhostList[0][zendManaged]=1
vhostList[0][ssl]=
vhostList[0][created]=2015-07-15T12:43:28+00:00
vhostList[0][lastUpdated]=2015-07-23T11:53:18+00:00
vhostList[0][createdTimestamp]=1436964208
vhostList[0][lastUpdatedTimestamp]=1437652398
vhostList[0][servers][0][id]=0
vhostList[0][servers][0][status]=Ok
vhostList[0][servers][0][name]=web-staging
vhostList[0][servers][0][lastMessage]=
total=1
";
        $items = json_decode($jsonData, true);
        $output = Utils::array2KV($items['responseData']);
        
        $this->assertEquals($output, $expectedOutput);
    }
}
