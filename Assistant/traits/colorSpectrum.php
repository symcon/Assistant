<?php

declare(strict_types=1);

class DeviceTraitColorSpectrum
{
    use HelperColorDevice;
    const propertyPrefix = 'ColorSpectrum';

    public static function getColumns()
    {
        return [
            [
                'label' => 'Color Variable',
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
            return self::getColorCompatibility($configuration[self::propertyPrefix . 'ID']);
        }
    }

    public static function getStatusPrefix()
    {
        return 'Color: ';
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            return [
                'color' => [
                    'spectrumRGB' => self::getColorValue($configuration[self::propertyPrefix . 'ID'])
                ]
            ];
        } else {
            return [];
        }
    }

    public static function doExecute($configuration, $command, $data, $emulateStatus)
    {
        switch ($command) {
            case 'action.devices.commands.ColorAbsolute':
                if (self::colorDevice($configuration[self::propertyPrefix . 'ID'], $data['color']['spectrumRGB'])) {
                    $color = $data['color']['spectrumRGB'];
                    if (!$emulateStatus) {
                        $i = 0;
                        while (($data['color']['spectrumRGB'] != self::getColorValue($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $color = self::getColorValue($configuration[self::propertyPrefix . 'ID']);
                    }
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => [
                            'color'  => [
                                'spectrumRGB' => $color
                            ],
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
        if ($configuration[self::propertyPrefix . 'ID'] != 0) {
            return [
                'action.devices.traits.ColorSpectrum'
            ];
        } else {
            return [];
        }
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.ColorAbsolute'
        ];
    }

    public static function getAttributes()
    {
        return [
            'colorModel' => 'rgb'
        ];
    }
}
