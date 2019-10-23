<?php

declare(strict_types=1);

class DeviceTraitBrightness
{
    use HelperDimDevice;
    const propertyPrefix = 'Brightness';

    public static function getColumns()
    {
        return [
            [
                'label' => 'Brightness Variable',
                'name'  => self::propertyPrefix . 'ID',
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
        if ($configuration[self::propertyPrefix . 'ID'] == 0) {
            return 'OK';
        } else {
            return self::getDimCompatibility($configuration[self::propertyPrefix . 'ID']);
        }
    }

    public static function getStatusPrefix()
    {
        return 'Brightness: ';
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            return [
                'brightness' => intval(self::getDimValue($configuration[self::propertyPrefix . 'ID']))
            ];
        } else {
            return [];
        }
    }

    public static function doExecute($configuration, $command, $data, $emulateStatus)
    {
        switch ($command) {
            case 'action.devices.commands.BrightnessAbsolute':
                if (self::dimDevice($configuration[self::propertyPrefix . 'ID'], $data['brightness'])) {
                    $brightness = $data['brightness'];
                    if (!$emulateStatus) {
                        $i = 0;
                        while (($data['brightness'] != self::getDimValue($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $brightness = intval(self::getDimValue($configuration[self::propertyPrefix . 'ID']));
                    }
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => [
                            'brightness' => $brightness,
                            'online'     => true
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
            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function getObjectIDs($configuration)
    {
        return [
            $configuration[self::propertyPrefix . 'ID']
        ];
    }

    public static function supportedTraits($configuration)
    {
        if ($configuration[self::propertyPrefix . 'ID'] != 0) {
            return [
                'action.devices.traits.Brightness'
            ];
        } else {
            return [];
        }
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.BrightnessAbsolute'
        ];
    }

    public static function getAttributes()
    {
        return [];
    }
}
