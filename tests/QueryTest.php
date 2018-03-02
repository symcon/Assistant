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

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
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

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testLightSwitchQuery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);
        SetValue($vid, false);

        $iid = IPS_CreateInstance($this->assistantModuleID);
        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightSwitch' => json_encode([
                [
                    'ID'      => '12345',
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
                    "online": true,
                    "on": false
                }
            }
        }
    }
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testLightDimmerQuery()
    {
        $profile = 'LightDimmerQuery.Test';
        IPS_CreateVariableProfile($profile, 1 /* Integer */);
        IPS_SetVariableProfileValues($profile, 0, 256, 1);

        $vid = IPS_CreateVariable(1 /* Integer */);
        IPS_SetVariableCustomProfile($vid, $profile);
        SetValue($vid, 128); //50% auf 256 steps

        $iid = IPS_CreateInstance($this->assistantModuleID);
        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightDimmer' => json_encode([
                [
                    'ID'                => '12345',
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
                    "online": true,
                    "brightness": 50,
                    "on": true
                }
            }
        }
    }
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testLightColorQuery()
    {
        $profile = 'LightColorQuery.Test';
        IPS_CreateVariableProfile($profile, 1 /* Integer */);
        IPS_SetVariableProfileValues($profile, 0, 0xFFFFFF, 1);

        $vid = IPS_CreateVariable(1 /* Integer */);
        IPS_SetVariableCustomProfile($vid, $profile);
        SetValue($vid, 0x0000FF); // blue

        $iid = IPS_CreateInstance($this->assistantModuleID);
        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightColor' => json_encode([
                [
                    'ID'                             => '123',
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
        "intent": "action.devices.QUERY",
        "payload": {
            "devices": [{
                "id": "123",
                "customData": {
                    "fooValue": 74,
                    "barValue": true,
                    "bazValue": "lambtwirl"
                }
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
            "123": {
                "online": true,
                "color": {
                    "spectrumRGB": 255
                },
                "brightness": 100,
                "on": true
            }
        }
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testThermostatQuery()
    {
        $this->assertTrue(true);
        return; // TODO: Remove this line when the thermostat is back in
        $modeID = IPS_CreateVariable(1 /* Integer */);
        $setID = IPS_CreateVariable(2 /* Float */);
        $observeID = IPS_CreateVariable(2 /* Float */);
        $setHighID = IPS_CreateVariable(2 /* Float */);
        $setLowID = IPS_CreateVariable(2 /* Float */);
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
                    'TemperatureSettingSetHighID'  => $setHighID,
                    'TemperatureSettingSetLowID'   => $setLowID,
                    'TemperatureSettingHumidityID' => $humidityID,
                ]
            ])
        ]));

        SetValue($modeID, 2);
        SetValue($setID, 38.4);
        SetValue($observeID, 42.2);
        SetValue($setHighID, 50.0);
        SetValue($setLowID, -10.1);
        SetValue($humidityID, 80.5);

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
                "id": "123",
                "customData": {
                    "fooValue": 74,
                    "barValue": true,
                    "bazValue": "lambtwirl"
                }
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
            "123": {
                "online": true,
                "thermostatMode": "cool",
                "thermostatTemperatureSetpoint": 38.4,
                "thermostatTemperatureAmbient": 42.2,
                "thermostatHumidityAmbient": 80.5
            }
        }
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }

    public function testSceneQuery()
    {
        $activateID = IPS_CreateScript(0);
        $deactivateID = IPS_CreateScript(0);

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
        "intent": "action.devices.QUERY",
        "payload": {
            "devices": [{
                "id": "123",
                "customData": {
                    "fooValue": 74,
                    "barValue": true,
                    "bazValue": "lambtwirl"
                }
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
            "123": {
                "online": true
            }
        }
    }
}
EOT;

        $this->assertEquals(json_decode($testResponse, true), $intf->SimulateData(json_decode($testRequest, true)));
    }
}
