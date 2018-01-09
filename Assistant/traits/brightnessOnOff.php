<?php

declare(strict_types=1);

class DeviceTraitBrightnessOnOff
{
    const propertyPrefix = 'BrightnessOnOff';

    use HelperDimDevice;

    public static function getColumns()
    {
        return [
            [
                'label' => 'VariableID',
                'name'  => self::propertyPrefix . 'ID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        return self::getDimCompatibility($configuration[self::propertyPrefix . 'ID']);
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            return [
                'brightness' => self::getDimValue($configuration[self::propertyPrefix . 'ID']),
                'on' => self::getDimValue($configuration[self::propertyPrefix . 'ID']) > 0
            ];
        } else {
            return [];
        }
    }

    public static function doExecute($configuration, $command, $data)
    {
        switch ($command) {
            case 'action.devices.commands.BrightnessAbsolute':
                if (self::dimDevice($configuration[self::propertyPrefix . 'ID'], $data['brightness'])) {
                    return [
                        'id'     => $configuration['ID'],
                        'status' => 'SUCCESS',
                        'states' => [
                            'brightness' => self::getDimValue($configuration[self::propertyPrefix . 'ID']),
                            'online'     => true
                        ]
                    ];
                } else {
                    return [
                        'id'        => $configuration['ID'],
                        'status'    => 'ERROR',
                        'errorCode' => 'deviceTurnedOff'
                    ];
                }
                break;

            case 'action.devices.commands.OnOff':
                if (self::dimDevice($configuration[self::propertyPrefix . 'ID'], $data['on'] ? 100 : 0)) {
                    return [
                        'id'     => $configuration['ID'],
                        'status' => 'SUCCESS',
                        'states' => [
                            'on'     => self::getDimValue($configuration[self::propertyPrefix . 'ID']) > 0,
                            'online' => true
                        ]
                    ];
                } else {
                    return [
                        'id'        => $configuration['ID'],
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
            'action.devices.traits.Brightness',
            'action.devices.traits.OnOff'
        ];
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.BrightnessAbsolute',
            'action.devices.commands.OnOff'
        ];
    }

    public static function getAttributes()
    {
        return [];
    }
}
