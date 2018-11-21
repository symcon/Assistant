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

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testEmulateStatus()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, '');

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
            ]),
            'EmulateStatus' => false
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
                    "on": false,
                    "online": true
                }
            }
        ]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));

        IPS_SetProperty($iid, 'EmulateStatus', true);
        IPS_ApplyChanges($iid);

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

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testLightSwitchExecute()
    {
        $testFunction = function ($emulateStatus) {
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
                ]),
                'EmulateStatus' => $emulateStatus
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

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightDimmerExecute()
    {
        $testFunction = function ($emulateStatus) {
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
                ]),
                'EmulateStatus' => $emulateStatus
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

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightColorExecute()
    {
        $testFunction = function ($emulateStatus) {
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
                        'ID'                             => '2',
                        'Name'                           => 'Buntes Licht',
                        'ColorSpectrumBrightnessOnOffID' => $vid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
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
                "online": true
            }
        }]
    }
}
EOT;

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));

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

            $brightnessResponse = $emulateStatus ? 50 : 49; // Some rounding error without emulate status, which is fine
            $testResponse = <<<EOT
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "payload": {
        "commands": [{
            "ids": ["2"],
            "status": "SUCCESS",
            "states": {
                "brightness": $brightnessResponse,
                "online": true
            }
        }]
    }
}
EOT;
            // Brightness of result is 49 due to rounding
            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertOnOffExecute()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
            IPS_SetVariableCustomAction($vid, $sid);

            $iid = IPS_CreateInstance($this->assistantModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceLightExpert' => json_encode([
                    [
                        'ID'              => '1',
                        'Name'            => 'Flur Licht',
                        'OnOffID'         => $vid,
                        'BrightnessID'    => 0,
                        'ColorSpectrumID' => 0
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
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

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertOnOffBrightnessExecute()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $profile = 'LightDimmerQuery.Test';
            IPS_CreateVariableProfile($profile, 1 /* Integer */);
            IPS_SetVariableProfileValues($profile, 0, 256, 1);

            $vid = IPS_CreateVariable(0 /* Boolean */);
            $bvid = IPS_CreateVariable(1 /* Integer */);
            IPS_SetVariableCustomProfile($bvid, $profile);
            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($bvid, $sid);

            $iid = IPS_CreateInstance($this->assistantModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceLightExpert' => json_encode([
                    [
                        'ID'              => '1',
                        'Name'            => 'Flur Licht',
                        'OnOffID'         => $vid,
                        'BrightnessID'    => $bvid,
                        'ColorSpectrumID' => 0
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
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

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertOnOffColorExecute()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $colorProfile = 'LightColorQuery.Test';
            IPS_CreateVariableProfile($colorProfile, 1 /* Integer */);
            IPS_SetVariableProfileValues($colorProfile, 0, 0xFFFFFF, 1);

            $vid = IPS_CreateVariable(0 /* Boolean */);
            $cvid = IPS_CreateVariable(1 /* Integer */);

            IPS_SetVariableCustomProfile($cvid, $colorProfile);

            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($cvid, $sid);

            $iid = IPS_CreateInstance($this->assistantModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceLightExpert' => json_encode([
                    [
                        'ID'              => '2',
                        'Name'            => 'Buntes Licht',
                        'OnOffID'         => $vid,
                        'BrightnessID'    => 0,
                        'ColorSpectrumID' => $cvid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
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
                "online": true
            }
        }]
    }
}
EOT;

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertOnOffBrightnessColorExecute()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $dimmerProfile = 'LightDimmerQuery.Test';
            IPS_CreateVariableProfile($dimmerProfile, 1 /* Integer */);
            IPS_SetVariableProfileValues($dimmerProfile, 0, 256, 1);

            $colorProfile = 'LightColorQuery.Test';
            IPS_CreateVariableProfile($colorProfile, 1 /* Integer */);
            IPS_SetVariableProfileValues($colorProfile, 0, 0xFFFFFF, 1);

            $vid = IPS_CreateVariable(0 /* Boolean */);
            $bvid = IPS_CreateVariable(1 /* Integer */);
            $cvid = IPS_CreateVariable(1 /* Integer */);

            IPS_SetVariableCustomProfile($bvid, $dimmerProfile);
            IPS_SetVariableCustomProfile($cvid, $colorProfile);

            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($bvid, $sid);
            IPS_SetVariableCustomAction($cvid, $sid);

            $iid = IPS_CreateInstance($this->assistantModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceLightExpert' => json_encode([
                    [
                        'ID'              => '2',
                        'Name'            => 'Buntes Licht',
                        'OnOffID'         => $vid,
                        'BrightnessID'    => $bvid,
                        'ColorSpectrumID' => $cvid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
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
                "online": true
            }
        }]
    }
}
EOT;

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));

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
        "commands": [{
            "ids": ["2"],
            "status": "SUCCESS",
            "states": {
                "brightness": 50,
                "online": true
            }
        }]
    }
}
EOT;
            // Brightness of result is 49 due to rounding
            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testThermostatExecute()
    {
        $testFunction = function ($emulateStatus) {
            $setID = IPS_CreateVariable(2 /* Float */);
            $observeID = IPS_CreateVariable(2 /* Float */);

            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');
            IPS_SetVariableCustomAction($setID, $sid);

            $iid = IPS_CreateInstance($this->assistantModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceThermostat' => json_encode([
                    [
                        'ID'                           => '123',
                        'Name'                         => 'Klima Flur',
                        'TemperatureSettingSetPointID' => $setID,
                        'TemperatureSettingAmbientID'  => $observeID
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);
            SetValue($setID, 38.4);
            SetValue($observeID, 42.2);

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
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ThermostatTemperatureSetpoint",
                    "params": {
                        "thermostatTemperatureSetpoint": 22.0
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
            "ids": ["123"],
            "status": "SUCCESS",
            "states": {
                "thermostatTemperatureSetpoint": 22.0
            }
        }]
    }
}
EOT;

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));

            $testRequest = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "inputs": [{
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ThermostatSetMode",
                    "params": {
                        "thermostatMode": "heatcool"
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
            "ids": ["123"],
            "status": "ERROR",
            "errorCode": "notSupported"
        }]
    }
}
EOT;

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));

            $testRequest = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "inputs": [{
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ThermostatTemperatureSetRange",
                    "params": {
                        "thermostatTemperatureSetpointHigh": 25.0,
                        "thermostatTemperatureSetpointLow": 20.0
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
            "status": "ERROR",
            "ids": ["123"],
            "errorCode": "notSupported"
        }]
    }
}
EOT;

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testGenericSwitchExecute()
    {
        $testFunction = function ($emulateStatus) {
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
                ]),
                'EmulateStatus' => $emulateStatus
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

            $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testSimpleSceneExecute()
    {
        $activateID = IPS_CreateScript(0);
        $colorVariableID = IPS_CreateVariable(1);

        IPS_SetScriptContent($activateID, '<?
            SetValue(' . $colorVariableID . ', 0xff0000);
        ?>');

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceSceneSimple' => json_encode([
                [
                    'ID'                  => '123',
                    'Name'                => 'Blau',
                    'SceneSimpleScriptID' => $activateID
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
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ActivateScene",
                    "params": {
                        "deactivate": false
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
            "ids": ["123"],
            "status": "SUCCESS",
            "states": {}
        }]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($intf->SimulateData(json_decode($testRequest, true))), true));

        $this->assertEquals(0xff0000, GetValue($colorVariableID));
    }

    public function testDeactivatableSceneExecute()
    {
        $activateID = IPS_CreateScript(0);
        $deactivateID = IPS_CreateScript(0);
        $colorVariableID = IPS_CreateVariable(1);

        IPS_SetScriptContent($activateID, '<?
            if ($_IPS[\'VALUE\']) {
                SetValue(' . $colorVariableID . ', 0xff0000);
            }
            else {
                SetValue(' . $colorVariableID . ', 0x00ff00);
            }
        ?>');

        IPS_SetScriptContent($deactivateID, '<?
            SetValue(' . $colorVariableID . ', 0x000000);
        ?>');

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceSceneDeactivatable' => json_encode([
                [
                    'ID'                             => '123',
                    'Name'                           => 'Blau',
                    'SceneDeactivatableActivateID'   => $activateID,
                    'SceneDeactivatableDeactivateID' => $deactivateID
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
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ActivateScene",
                    "params": {
                        "deactivate": false
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
            "ids": ["123"],
            "status": "SUCCESS",
            "states": {}
        }]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($intf->SimulateData(json_decode($testRequest, true))), true));

        $this->assertEquals(0xff0000, GetValue($colorVariableID));

        $testRequest = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "inputs": [{
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ActivateScene",
                    "params": {
                        "deactivate": true
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
            "ids": ["123"],
            "status": "SUCCESS",
            "states": {}
        }]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($intf->SimulateData(json_decode($testRequest, true))), true));

        $this->assertEquals(0x000000, GetValue($colorVariableID));

        // Use one script for activation and deactivation
        IPS_SetConfiguration($iid, json_encode([
            'DeviceSceneDeactivatable' => json_encode([
                [
                    'ID'                             => '123',
                    'Name'                           => 'Blau',
                    'SceneDeactivatableActivateID'   => $activateID,
                    'SceneDeactivatableDeactivateID' => $activateID
                ]
            ])
        ]));

        IPS_ApplyChanges($iid);

        $testRequest = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "inputs": [{
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ActivateScene",
                    "params": {
                        "deactivate": false
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
            "ids": ["123"],
            "status": "SUCCESS",
            "states": {}
        }]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($intf->SimulateData(json_decode($testRequest, true))), true));

        $this->assertEquals(0xff0000, GetValue($colorVariableID));

        $testRequest = <<<'EOT'
{
    "requestId": "ff36a3cc-ec34-11e6-b1a0-64510650abcf",
    "inputs": [{
        "intent": "action.devices.EXECUTE",
        "payload": {
            "commands": [{
                "devices": [{
                    "id": "123",
                    "customData": {
                        "fooValue": 74,
                        "barValue": true,
                        "bazValue": "sheepdip"
                    }
                }],
                "execution": [{
                    "command":   "action.devices.commands.ActivateScene",
                    "params": {
                        "deactivate": true
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
            "ids": ["123"],
            "status": "SUCCESS",
            "states": {}
        }]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($intf->SimulateData(json_decode($testRequest, true))), true));

        $this->assertEquals(0x00ff00, GetValue($colorVariableID));
    }
}
