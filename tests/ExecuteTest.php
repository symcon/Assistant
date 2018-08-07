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

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
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

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
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
                    'ID'                             => '2',
                    'Name'                           => 'Buntes Licht',
                    'ColorSpectrumBrightnessOnOffID' => $vid
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
                "brightness": 49,
                "online": true
            }
        }]
    }
}
EOT;
        // Brightness of result is 49 due to rounding
        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testThermostatExecute()
    {
        $this->assertTrue(true);
        return; // TODO: Remove this line when the thermostat is back in
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        if (!IPS_VariableProfileExists('Temperature')) {
            IPS_CreateVariableProfile('Temperature', 2);
        }

        $modeID = IPS_CreateVariable(1 /* Integer */);
        IPS_SetVariableCustomProfile($modeID, 'ThermostatMode.GA');
        IPS_SetVariableCustomAction($modeID, $sid);

        $setID = IPS_CreateVariable(2 /* Float */);
        IPS_SetVariableCustomProfile($setID, 'Temperature');
        IPS_SetVariableCustomAction($setID, $sid);

        $observeID = IPS_CreateVariable(2 /* Float */);

        $humidityID = IPS_CreateVariable(2 /* Float */);

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceThermostat' => json_encode([
                [
                    'ID'                           => '123',
                    'Name'                         => 'Klima Flur',
                    'TemperatureSettingModeID'     => $modeID,
                    'TemperatureSettingSetID'      => $setID,
                    'TemperatureSettingObserveID'  => $observeID,
                    'TemperatureSettingHumidityID' => $humidityID,
                ]
            ])
        ]));

        IPS_ApplyChanges($iid);

        SetValue($modeID, 2);
        SetValue($setID, 38.4);
        SetValue($observeID, 42.2);
        SetValue($humidityID, 80.5);

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
                "thermostatMode": "cool",
                "thermostatTemperatureSetpoint": 22.0,
                "thermostatTemperatureAmbient": 42.2,
                "thermostatHumidityAmbient": 80.5
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
            "status": "SUCCESS",
            "states": {
                "thermostatMode": "heatcool",
                "thermostatTemperatureSetpoint": 22.0,
                "thermostatTemperatureAmbient": 42.2,
                "thermostatHumidityAmbient": 80.5
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
                        "thermostatMode": "heat"
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
                "thermostatMode": "heat",
                "thermostatTemperatureSetpoint": 22.0,
                "thermostatTemperatureAmbient": 42.2,
                "thermostatHumidityAmbient": 80.5
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
            "id": "123",
            "errorCode": "notSupported"
        }]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testThermostatExecuteOnOff()
    {
        $this->assertTrue(true);
        return; // TODO: Remove this line when the thermostat is back in
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        if (!IPS_VariableProfileExists('Temperature')) {
            IPS_CreateVariableProfile('Temperature', 2);
        }

        $modeID = IPS_CreateVariable(1 /* Integer */);
        IPS_SetVariableCustomProfile($modeID, 'ThermostatMode.GA');
        IPS_SetVariableCustomAction($modeID, $sid);

        $setID = IPS_CreateVariable(2 /* Float */);
        IPS_SetVariableCustomProfile($setID, 'Temperature');
        IPS_SetVariableCustomAction($setID, $sid);

        $observeID = IPS_CreateVariable(2 /* Float */);

        $humidityID = IPS_CreateVariable(2 /* Float */);

        $iid = IPS_CreateInstance($this->assistantModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceThermostat' => json_encode([
                [
                    'ID'                           => '123',
                    'Name'                         => 'Klima Flur',
                    'TemperatureSettingModeID'     => $modeID,
                    'TemperatureSettingSetID'      => $setID,
                    'TemperatureSettingObserveID'  => $observeID,
                    'TemperatureSettingHumidityID' => $humidityID,
                ]
            ])
        ]));

        IPS_ApplyChanges($iid);

        SetValue($modeID, 2);
        SetValue($setID, 22.0);
        SetValue($observeID, 42.2);
        SetValue($humidityID, 80.5);

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
            "status": "SUCCESS",
            "states": {
                "thermostatMode": "heatcool",
                "thermostatTemperatureSetpoint": 22.0,
                "thermostatTemperatureAmbient": 42.2,
                "thermostatHumidityAmbient": 80.5
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
                        "thermostatMode": "off"
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
                "thermostatMode": "off",
                "thermostatTemperatureSetpoint": 22.0,
                "thermostatTemperatureAmbient": 42.2,
                "thermostatHumidityAmbient": 80.5
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
                        "thermostatMode": "on"
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
                "thermostatMode": "heatcool",
                "thermostatTemperatureSetpoint": 22.0,
                "thermostatTemperatureAmbient": 42.2,
                "thermostatHumidityAmbient": 80.5
            }
        }]
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testGenericSwitchExecute()
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
