<?php

declare(strict_types=1);

class DeviceTraitBrightnessOnOff
{
    use HelperDimDevice;
    const propertyPrefix = 'BrightnessOnOff';

    public static function getColumns()
    {
        return [
            [
                'label' => 'Variable',
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
        return self::getDimCompatibility($configuration[self::propertyPrefix . 'ID']);
    }

    public static function getStatusPrefix()
    {
        return 'Brightness: ';
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            if ((time() - IPS_GetVariable($configuration[self::propertyPrefix . 'ID'])['VariableUpdated']) > 30 * 60) {
                return [
                    'ids'       => [$configuration['ID']],
                    'status'    => 'ERROR',
                    'errorCode' => 'deviceOffline'
                ];
            }
            return [
                'brightness' => intval(self::getDimValue($configuration[self::propertyPrefix . 'ID'])),
                'on'         => self::getDimValue($configuration[self::propertyPrefix . 'ID']) > 0
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

            case 'action.devices.commands.OnOff':
                $newValue = $data['on'] ? 100 : 0;
                if (self::dimDevice($configuration[self::propertyPrefix . 'ID'], $newValue)) {
                    $on = $newValue;
                    if (!$emulateStatus) {
                        $i = 0;
                        while (($newValue != self::getDimValue($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $on = self::getDimValue($configuration[self::propertyPrefix . 'ID']) > 0;
                    }
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => [
                            'on'     => $on,
                            'online' => true
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
