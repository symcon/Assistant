<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    private $assistantModuleID = '{BB6EF5EE-1437-4C80-A16D-DA0A6C885210}';

    public function setUp()
    {
        //Reset
        IPS\Kernel::reset();

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        parent::setUp();
    }

    public function testCreate()
    {
        $iid = IPS_CreateInstance($this->assistantModuleID);
        $this->assertEquals(count(IPS_GetInstanceListByModuleID($this->assistantModuleID)), 1);
    }

    public function testEmptyQuery()
    {
        $iid = IPS_CreateInstance($this->assistantModuleID);
        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Assistant);

        $testRequest = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "inputs": [{
        "intent": "action.devices.QUERY",
        "payload": {
            "devices": []
        }
    }]
}            
EOT;

        $testResponse = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "payload": {
        "devices": []
    }
}
EOT;

        $this->assertEquals($intf->SimulateData(json_decode($testRequest, true)), json_decode($testResponse, true));
    }

    public function testInvalidQuery()
    {
        $iid = IPS_CreateInstance($this->assistantModuleID);
        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Assistant);

        $testRequest = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "inputs": [{
        "intent": "action.devices.QUERY",
        "payload": {
            "devices": [{
                "id": "12345"        
            }]
        }
    }]
}            
EOT;

        $testResponse = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "payload": {
        "devices": {
            "12345": {
                "online": false
            }
        }
    }
}
EOT;

        $this->assertEquals($intf->SimulateData(json_decode($testRequest, true)), json_decode($testResponse, true));
    }

    public function testLightQuery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);

        $iid = IPS_CreateInstance($this->assistantModuleID);
        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightSwitch' => json_encode([
                [
                    'ID'      => 0,
                    'Name'    => 'Flur Licht',
                    'OnOffID' => $vid
                ]
            ])
        ]));
        IPS_ApplyChanges($iid);

        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Assistant);

        $testRequest = <<<'EOT'
    {
        "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
        "inputs": [{
            "intent": "action.devices.QUERY",
            "payload": {
                "devices": [{
                    "id": "0"
                }]
            }
        }]
    }            
EOT;

        $testResponse = <<<'EOT'
    {
        "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
        "payload": {
            "devices": {
                "0": {
                    "online": true,
                    "on": false
                }
            }
        }
    }
EOT;

        $this->assertEquals($intf->SimulateData(json_decode($testRequest, true)), json_decode($testResponse, true));
    }
}
