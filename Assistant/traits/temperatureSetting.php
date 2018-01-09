<?php

declare(strict_types=1);

class DeviceTraitTemperatureSetting
{
    const propertyPrefix = 'TemperatureSetting';

    const MODE = [
        'off' => 0,
        'heat' => 1,
        'cool' => 2,
        'on' => 3,
        'heatcool' => 4
    ];

    use HelperSetDevice;

    public static function getColumns()
    {
        return [
            [
                'label' => 'ThermostatModeVariableID',
                'name'  => self::propertyPrefix . 'ModeID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'SetTemperatureVariableID',
                'name'  => self::propertyPrefix . 'SetID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'ObserveTemperatureVariableID',
                'name'  => self::propertyPrefix . 'ObserveID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'SetTemperatureHighVariableID',
                'name'  => self::propertyPrefix . 'SetHighID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'SetTemperatureLowVariableID',
                'name'  => self::propertyPrefix . 'SetLowID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'HumidityVariableID',
                'name'  => self::propertyPrefix . 'HumidityID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
        ];
    }

    public static function getStatus($configuration)
    {
        if (!IPS_VariableExists($configuration[self::propertyPrefix . 'ModeID'])) {
            return 'No mode variable';
        }

        $modeVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'ModeID']);
        if ($modeVariable['VariableType'] != 1 /* Integer */) {
            return 'Integer required for mode';
        }

        if ($modeVariable['VariableCustomProfile'] != '') {
            $modeProfileName = $modeVariable['VariableCustomProfile'];
        } else {
            $modeProfileName = $modeVariable['VariableProfile'];
        }

        // TODO: Check if profile name is correct
        if ($modeProfileName != 'ThermostatMode.GA') {
            return 'Mode profile incorrect';
        }

        if ($modeVariable['VariableCustomAction'] != '') {
            $modeProfileAction = $modeVariable['VariableCustomAction'];
        } else {
            $modeProfileAction = $modeVariable['VariableAction'];
        }

        if (!($modeProfileAction > 10000)) {
            return 'Action for mode required';
        }

        if (!IPS_VariableExists($configuration[self::propertyPrefix . 'SetID'])) {
            return 'No set temperature';
        }

        $setVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'SetID']);
        if ($setVariable['VariableType'] != 2 /* Float */) {
            return 'Float required for set variable';
        }

        if ($setVariable['VariableCustomProfile'] != '') {
            $setProfileName = $setVariable['VariableCustomProfile'];
        } else {
            $setProfileName = $setVariable['VariableProfile'];
        }

        if (!IPS_VariableProfileExists($setProfileName)) {
            return 'Profile for set variable required';
        }

        if ($setVariable['VariableCustomAction'] != '') {
            $setProfileAction = $setVariable['VariableCustomAction'];
        } else {
            $setProfileAction = $setVariable['VariableAction'];
        }

        if (!($setProfileAction > 10000)) {
            return 'Action for set required';
        }

        if (!IPS_VariableExists($configuration[self::propertyPrefix . 'ObserveID'])) {
            return 'No observe temperature';
        }

        $observeVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'ObserveID']);
        if ($observeVariable['VariableType'] != 2 /* Float */) {
            return 'Float required for observe variable';
        }

        if ((GetValue($configuration[self::propertyPrefix . 'ModeID']) == 'heatcool') || IPS_VariableExists($configuration[self::propertyPrefix . 'SetHighID'])) {
            if (!IPS_VariableExists($configuration[self::propertyPrefix . 'SetHighID'])) {
                return 'No setHigh temperature despite mode heatcool';
            }

            $setHighVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'SetHighID']);
            if ($setHighVariable['VariableType'] != 2 /* Float */) {
                return 'Float required for setHigh variable';
            }

            if ($setHighVariable['VariableCustomProfile'] != '') {
                $setHighProfileName = $setHighVariable['VariableCustomProfile'];
            } else {
                $setHighProfileName = $setHighVariable['VariableProfile'];
            }

            if (!IPS_VariableProfileExists($setHighProfileName)) {
                return 'Profile for setHigh variable required';
            }

            if ($setHighVariable['VariableCustomAction'] != '') {
                $setHighProfileAction = $setHighVariable['VariableCustomAction'];
            } else {
                $setHighProfileAction = $setHighVariable['VariableAction'];
            }

            if (!($setHighProfileAction > 10000)) {
                return 'Action for setHigh required';
            }
        }

        if ((GetValue($configuration[self::propertyPrefix . 'ModeID']) == 'heatcool') || IPS_VariableExists($configuration[self::propertyPrefix . 'SetLowID'])) {
            if (!IPS_VariableExists($configuration[self::propertyPrefix . 'SetLowID'])) {
                return 'No setLow temperature despite mode heatcool';
            }

            $setLowVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'SetLowID']);
            if ($setLowVariable['VariableType'] != 2 /* Float */) {
                return 'Float required for setLow variable';
            }

            if ($setLowVariable['VariableCustomProfile'] != '') {
                $setLowProfileName = $setLowVariable['VariableCustomProfile'];
            } else {
                $setLowProfileName = $setLowVariable['VariableProfile'];
            }

            if (!IPS_VariableProfileExists($setLowProfileName)) {
                return 'Profile for setLow variable required';
            }

            if ($setLowVariable['VariableCustomAction'] != '') {
                $setLowProfileAction = $setLowVariable['VariableCustomAction'];
            } else {
                $setLowProfileAction = $setLowVariable['VariableAction'];
            }

            if (!($setLowProfileAction > 10000)) {
                return 'Action for setLow required';
            }
        }

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'HumidityID'])) {
            $humidityVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'HumidityID']);
            if ($humidityVariable['VariableType'] != 2 /* Float */) {
                return 'Float required for humidity variable';
            }
        }
    }

    public static function doQuery($configuration)
    {
        $result = [];
        $mode = 'heatcool';
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ModeID'])) {
            $key = array_search(GetValue($configuration[self::propertyPrefix . 'ModeID']), self::MODE);
            $mode = $key;
        }
        switch($mode) {
            case 'off':
            case 'heat':
            case 'cool':
            case 'on':
            case 'heatcool':
                break;

            default:
                // TODO: Throw an error or something like that? How?
                //$mode = 'heatcool';
                break;
        }
        $result['thermostatMode'] = $mode;

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'SetID'])) {
            $result['thermostatTemperatureSetpoint'] = GetValue($configuration[self::propertyPrefix . 'SetID']);
        }

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ObserveID'])) {
            $result['thermostatTemperatureAmbient'] = GetValue($configuration[self::propertyPrefix . 'ObserveID']);
        }

        if (($mode == 'heatcool') && IPS_VariableExists($configuration[self::propertyPrefix . 'SetHighID'])) {
            $result['thermostatTemperatureSetpointHigh'] = GetValue($configuration[self::propertyPrefix . 'SetHighID']);
        }

        if (($mode == 'heatcool') && IPS_VariableExists($configuration[self::propertyPrefix . 'SetLowID'])) {
            $result['thermostatTemperatureSetpointLow'] = GetValue($configuration[self::propertyPrefix . 'SetLowID']);
        }

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'HumidityID'])) {
            $result['thermostatHumidityAmbient'] = GetValue($configuration[self::propertyPrefix . 'HumidityID']);
        }

        return $result;
    }

    public static function doExecute($configuration, $command, $data)
    {
        $states = self::doQuery($configuration);

        switch ($command) {
            case 'action.devices.commands.ThermostatTemperatureSetpoint':
                if (self::setDevice($configuration[self::propertyPrefix . 'SetID'], floatval($data['thermostatTemperatureSetpoint']))) {
                    return [
                        'ids'    => [ $configuration['ID'] ],
                        'status' => 'SUCCESS',
                        'states' => $states
                    ];
                } else {
                    return [
                        'ids'       => [ $configuration['ID'] ],
                        'status'    => 'ERROR',
                        'errorCode' => 'deviceTurnedOff'
                    ];
                }
                break;

            case 'action.devices.commands.ThermostatTemperatureSetRange':
                $success = self::setDevice($configuration[self::propertyPrefix . 'SetHighID'], floatval($data['thermostatTemperatureSetpointHigh']));
                $success = $success && self::setDevice($configuration[self::propertyPrefix . 'SetLowID'], floatval($data['thermostatTemperatureSetpointLow']));
                $success = $success && self::setDevice($configuration[self::propertyPrefix . 'ModeID'], 'heatcool');
                if ($success) {
                    return [
                        'ids'    => [ $configuration['ID'] ],
                        'status' => 'SUCCESS',
                        'states' => $states
                    ];
                } else {
                    return [
                        'ids'       => [ $configuration['ID'] ],
                        'status'    => 'ERROR',
                        'errorCode' => 'deviceTurnedOff'
                    ];
                }
                break;

            case 'action.devices.commands.ThermostatSetMode':
                if (self::setDevice($configuration[self::propertyPrefix . 'ModeID'], self::MODE[$data['thermostatMode']])) {
                    return [
                        'ids'    => [ $configuration['ID'] ],
                        'status' => 'SUCCESS',
                        'states' => $states
                    ];
                } else {
                    return [
                        'ids'       => [ $configuration['ID'] ],
                        'status'    => 'ERROR',
                        'errorCode' => 'deviceTurnedOff'
                    ];
                }
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedTraits()
    {
        return [
            'action.devices.traits.TemperatureSetting'
        ];
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.ThermostatTemperatureSetpoint',
            'action.devices.commands.ThermostatTemperatureSetRange',
            'action.devices.commands.ThermostatSetMode'
        ];
    }

    public static function getAttributes()
    {
        return [
            'availableThermostatModes' => 'off,heat,cool,on,heatcool',
            'thermostatTemperatureUnit' => 'C'
        ];
    }
}
