<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class BasicFunctionalityTest extends TestCase
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
        $previousCount = count(IPS_GetInstanceListByModuleID($this->assistantModuleID));
        IPS_CreateInstance($this->assistantModuleID);
        $this->assertEquals($previousCount + 1, count(IPS_GetInstanceListByModuleID($this->assistantModuleID)));
    }

    public function testError()
    {

        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $vid = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid, $sid);

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceGenericSwitch' => json_encode([
                [
                    'ID'      => '1',
                    'Name'    => 'Flur Gerät',
                    'OnOffID' => $vid
                ]
            ])
        ]));
        IPS_ApplyChanges($iid);

        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Assistant);

        $testRequest = <<<'EOT'
{
    "inputs": [{
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "1"
                }],
                "execution": [{
                    "command": "action.devices.commands.OnOff",
                    "params": {
                        "on": true
                    }
                }]
            }]
        }
    }]
}            
EOT;

        $testResponse = <<<'EOT'
{
    "errorCode": "protocolError",
    "debugString": "requestId is undefined"
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }
}
