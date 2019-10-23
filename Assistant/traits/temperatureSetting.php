<?php

declare(strict_types=1);

class DeviceTraitTemperatureSetting
{
    use HelperFloatDevice;
    use HelperSetDevice;
    const propertyPrefix = 'TemperatureSetting';

    public static function getColumns()
    {
        return [
            [
                'label' => 'Setpoint',
                'name'  => self::propertyPrefix . 'SetPointID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'Ambient Temperature',
                'name'  => self::propertyPrefix . 'AmbientID',
                'width' => '200px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        $ambientStatus = self::getGetFloatCompatibility($configuration[self::propertyPrefix . 'AmbientID']);
        if ($ambientStatus != 'OK') {
            return 'Ambient: ' . $ambientStatus;
        }

        $setPointStatus = self::getGetFloatCompatibility($configuration[self::propertyPrefix . 'SetPointID']);
        if ($setPointStatus != 'OK') {
            return 'Setpoint: ' . $setPointStatus;
        }

        $setVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'SetPointID']);
        if ($setVariable['VariableCustomAction'] != '') {
            $setProfileAction = $setVariable['VariableCustomAction'];
        } else {
            $setProfileAction = $setVariable['VariableAction'];
        }

        if (!($setProfileAction > 10000)) {
            return 'Setpoint: Action required';
        }

        return 'OK';
    }

    public static function getStatusPrefix()
    {
        return 'Temperature: ';
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'AmbientID']) && IPS_VariableExists($configuration[self::propertyPrefix . 'SetPointID'])) {
            return [
                'thermostatMode'                => 'heat',
                'thermostatTemperatureSetpoint' => GetValue($configuration[self::propertyPrefix . 'SetPointID']),
                'thermostatTemperatureAmbient'  => GetValue($configuration[self::propertyPrefix . 'AmbientID'])
            ];
        } else {
            return [];
        }
    }

    public static function doExecute($configuration, $command, $data, $emulateStatus)
    {
        switch ($command) {
            case 'action.devices.commands.ThermostatTemperatureSetpoint':
                if (self::setDevice($configuration[self::propertyPrefix . 'SetPointID'], floatval($data['thermostatTemperatureSetpoint']))) {
                    $setPoint = floatval($data['thermostatTemperatureSetpoint']);
                    if (!$emulateStatus) {
                        $i = 0;
                        while ((floatval($data['thermostatTemperatureSetpoint']) != GetValue($configuration[self::propertyPrefix . 'SetPointID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $setPoint = GetValue($configuration[self::propertyPrefix . 'SetPointID']);
                    }

                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => [
                            'thermostatTemperatureSetpoint' => $setPoint
                        ]
                    ];
                } else {
                    return [
                        'ids'       => [$configuration['ID']],
                        'status'    => 'ERROR',
                        'errorCode' => 'deviceTurnedOff'
                    ];
                }
                break;

            case 'action.devices.commands.ThermostatTemperatureSetRange':
                return [
                    'ids'       => [$configuration['ID']],
                    'status'    => 'ERROR',
                    'errorCode' => 'notSupported'
                ];

            case 'action.devices.commands.ThermostatSetMode':
                if ($data['thermostatMode'] == 'heat') {
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => [
                            'thermostatMode' => 'heat'
                        ]
                    ];
                } else {
                    return [
                        'ids'       => [$configuration['ID']],
                        'status'    => 'ERROR',
                        'errorCode' => 'notSupported'
                    ];
                }
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function getObjectIDs($configuration)
    {
        return [
            $configuration[self::propertyPrefix . 'SetPointID'],
            $configuration[self::propertyPrefix . 'AmbientID']
        ];
    }

    public static function supportedTraits($configuration)
    {
        return [
            'action.devices.traits.TemperatureSetting'
        ];
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.ThermostatTemperatureSetpoint',
            'action.devices.commands.ThermostatSetMode',
            'action.devices.commands.ThermostatTemperatureSetRange'
        ];
    }

    public static function getAttributes()
    {
        return [
            'availableThermostatModes'  => 'heat',
            'thermostatTemperatureUnit' => 'C'
        ];
    }
}
