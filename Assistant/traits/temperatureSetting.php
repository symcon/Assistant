<?php

declare(strict_types=1);

class DeviceTraitTemperatureSetting
{
    const propertyPrefix = 'TemperatureSetting';

    const MODE = [
        'off'         => 0,
        'heat'        => 1,
        'cool'        => 2,
        'on'          => 3,
        'heatcool'    => 4,
        'offheat'     => 5,
        'offcool'     => 6,
        'offheatcool' => 7
    ];

    use HelperSetDevice;

    public static function getColumns()
    {
        return [
            [
                'label' => 'ModeID',
                'name'  => self::propertyPrefix . 'ModeID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'SetTemperatureID',
                'name'  => self::propertyPrefix . 'SetID',
                'width' => '200px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'ObserveID',
                'name'  => self::propertyPrefix . 'ObserveID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'HumidityID',
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

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'HumidityID'])) {
            $humidityVariable = IPS_GetVariable($configuration[self::propertyPrefix . 'HumidityID']);
            if ($humidityVariable['VariableType'] != 2 /* Float */) {
                return 'Float required for humidity variable';
            }
        }

        return 'OK';
    }

    public static function doQuery($configuration)
    {
        $result = [];
        $mode = 'heatcool';
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ModeID'])) {
            $mode = array_search(GetValue($configuration[self::propertyPrefix . 'ModeID']), self::MODE);
        }
        switch ($mode) {
            case 'off':
            case 'heat':
            case 'cool':
            case 'on':
            case 'heatcool':
                break;

            case 'offheat':
            case 'offcool':
            case 'offheatcool':
                $mode = 'off';
                break;

            default:
                // TODO: Throw an error or something like that? How?
                $mode = 'heatcool';
                break;
        }
        $result['thermostatMode'] = $mode;

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'SetID'])) {
            $result['thermostatTemperatureSetpoint'] = GetValue($configuration[self::propertyPrefix . 'SetID']);
        }

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ObserveID'])) {
            $result['thermostatTemperatureAmbient'] = GetValue($configuration[self::propertyPrefix . 'ObserveID']);
        }

        if (IPS_VariableExists($configuration[self::propertyPrefix . 'HumidityID'])) {
            $result['thermostatHumidityAmbient'] = GetValue($configuration[self::propertyPrefix . 'HumidityID']);
        }

        return $result;
    }

    public static function doExecute($configuration, $command, $data)
    {
        switch ($command) {
            case 'action.devices.commands.ThermostatTemperatureSetpoint':
                if (self::setDevice($configuration[self::propertyPrefix . 'SetID'], floatval($data['thermostatTemperatureSetpoint']))) {
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => self::doQuery($configuration)
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
                {
                    $targetMode = $data['thermostatMode'];
                    if ($data['thermostatMode'] == 'on') {
                        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ModeID'])) {
                            $currentMode = array_search(GetValue($configuration[self::propertyPrefix . 'ModeID']), self::MODE);
                            switch ($currentMode) {
                                case 'offheat':
                                    $targetMode = 'heat';
                                    break;

                                case 'offcool':
                                    $targetMode = 'cool';
                                    break;

                                case 'offheatcool':
                                    $targetMode = 'heatcool';
                                    break;

                                case 'off':
                                    // Default value in case we don't know the previous mode from the variable
                                    $targetMode = 'heatcool';
                                    break;

                                default:
                                    $targetMode = $currentMode;
                                    break;
                            }
                        } else {
                            $targetMode = 'heatcool';
                        }
                    }
                    if ($data['thermostatMode'] == 'off') {
                        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ModeID'])) {
                            $currentMode = array_search(GetValue($configuration[self::propertyPrefix . 'ModeID']), self::MODE);
                            switch ($currentMode) {
                                case 'heat':
                                    $targetMode = 'offheat';
                                    break;

                                case 'cool':
                                    $targetMode = 'offcool';
                                    break;

                                case 'heatcool':
                                    $targetMode = 'offheatcool';
                                    break;

                                case 'offheat':
                                case 'offcool':
                                case 'offheatcool':
                                    $targetMode = $currentMode;
                                    break;

                                default:
                                    break;
                            }
                        }
                    }
                    if (self::setDevice($configuration[self::propertyPrefix . 'ModeID'], self::MODE[$targetMode])) {
                        return [
                            'ids'    => [$configuration['ID']],
                            'status' => 'SUCCESS',
                            'states' => self::doQuery($configuration)
                        ];
                    } else {
                        return [
                            'ids'       => [$configuration['ID']],
                            'status'    => 'ERROR',
                            'errorCode' => 'deviceTurnedOff',
                        ];
                    }
                }
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function getVariableIDs($configuration) {
        return [
            $configuration[self::propertyPrefix . 'ModeID'],
            $configuration[self::propertyPrefix . 'SetID'],
            $configuration[self::propertyPrefix . 'ObserveID'],
            $configuration[self::propertyPrefix . 'HumidityID']
        ];
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
            'action.devices.commands.ThermostatSetMode'
        ];
    }

    public static function getAttributes()
    {
        return [
            'availableThermostatModes'  => 'off,heat,cool,on,heatcool',
            'thermostatTemperatureUnit' => 'C'
        ];
    }
}
