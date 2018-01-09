<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class ExecuteTest extends TestCase
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

    public function testEmptyExecute()
    {
        $iid = IPS_CreateInstance($this->assistantModuleID);
        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Assistant);

        $testRequest = <<<'EOT'
{
  "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
  "inputs": [{
    "intent": "action.devices.EXECUTE",
    "payload": {
      "commands": [{
        "devices": [],
        "execution": []
      }]
    }

  }]
}            
EOT;

        $testResponse = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "payload": {
        "commands": []
    }
}
EOT;

        $this->assertEquals($intf->SimulateData(json_decode($testRequest, true)), json_decode($testResponse, true));
    }

    public function testLightSwitchExecute()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $vid = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid, $sid);

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightSwitch' => json_encode([
                [
                    'ID'      => '1',
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
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "payload": {
        "commands": [
            {
                "ids": ["1"],
                "status": "SUCCESS",
                "states": {
                    "on": true,
                    "online": true
                }
            }
        ]
    }
}
EOT;

        $this->assertEquals($intf->SimulateData(json_decode($testRequest, true)), json_decode($testResponse, true));
    }

    public function testLightDimmerExecute()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $profile = 'LightDimmerQuery.Test';
        IPS_CreateVariableProfile($profile, 1 /* Integer */);
        IPS_SetVariableProfileValues($profile, 0, 256, 1);

        $vid = IPS_CreateVariable(1 /* Integer */);
        IPS_SetVariableCustomProfile($vid, $profile);
        IPS_SetVariableCustomAction($vid, $sid);

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightDimmer' => json_encode([
                [
                    'ID'                => '1',
                    'Name'              => 'Flur Licht',
                    'BrightnessOnOffID' => $vid
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
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "1"
                }],
                "execution": [{
                    "command": "action.devices.commands.BrightnessAbsolute",
                    "params": {
                        "brightness": 50
                    }
                }]
            }]
        }
    }]
}            
EOT;

        $testResponse = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "payload": {
        "commands": [
            {
                "ids": ["1"],
                "status": "SUCCESS",
                "states": {
                    "brightness": 50,
                    "online": true
                }
            }
        ]
    }
}
EOT;

        $this->assertEquals($intf->SimulateData(json_decode($testRequest, true)), json_decode($testResponse, true));
    }

    public function testLightColorExecute()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $profile = 'LightColorQuery.Test';
        IPS_CreateVariableProfile($profile, 1 /* Integer */);
        IPS_SetVariableProfileValues($profile, 0, 0xFFFFFF, 1);

        $vid = IPS_CreateVariable(1 /* Integer */);
        IPS_SetVariableCustomProfile($vid, $profile);
        IPS_SetVariableCustomAction($vid, $sid);

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightColor' => json_encode([
                [
                    'ID'                   => '2',
                    'Name'                 => 'Buntes Licht',
                    'ColorSpectrumOnOffID' => $vid
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
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "2",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command": "action.devices.commands.ColorAbsolute",
                    "params": {
                        "color": {
                            "name": "red",
                            "spectrumRGB": 16711680
                        }
                    }
                }]
            }]
        }
    }]
}
EOT;

        $testResponse = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "payload": {
        "commands": [{
            "ids": ["2"],
            "status": "SUCCESS",
            "states": {
                "color": {
                    "spectrumRGB": 16711680
                },
                "on": true
            }
        }]
    }
}
EOT;

        $this->assertEquals($intf->SimulateData(json_decode($testRequest, true)), json_decode($testResponse, true));
    }
}
