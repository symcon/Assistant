<?php

declare(strict_types=1);

class DeviceTraitBrightness
{
    const propertyPrefix = 'Brightness';

    use HelperDimDevice;

    public static function getColumns()
    {
        return [
            [
                'label' => 'VariableID',
                'name'  => 'BrightnessID',
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
        return self::getDimCompatibility($configuration['BrightnessID']);
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration['BrightnessID'])) {
            return [
                'brightness' => self::getDimValue($configuration['BrightnessID'])
            ];
        } else {
            return [];
        }
    }

    public static function doExecute($configuration, $command, $data)
    {
        switch ($command) {
            case 'action.devices.commands.BrightnessAbsolute':
                if (self::dimDevice($configuration['BrightnessID'], $data['brightness'])) {
                    return [
                        'id'     => $configuration['ID'],
                        'status' => 'SUCCESS',
                        'states' => [
                            'brightness' => self::getDimValue($configuration['BrightnessID']),
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
            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedTrait()
    {
        return 'action.devices.traits.Brightness';
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.BrightnessAbsolute'
        ];
    }
}
