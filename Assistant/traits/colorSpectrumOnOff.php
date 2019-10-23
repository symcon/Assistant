<?php

declare(strict_types=1);

class DeviceTraitColorSpectrumOnOff
{
    use HelperColorDevice;
    const propertyPrefix = 'ColorSpectrumOnOff';

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
        return self::getColorCompatibility($configuration[self::propertyPrefix . 'ID']);
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
                ],
                'on' => self::getColorValue($configuration[self::propertyPrefix . 'ID']) != 0
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

            case 'action.devices.commands.OnOff':
                $newValue = $data['on'] ? 0xFFFFFF : 0;
                if (self::colorDevice($configuration[self::propertyPrefix . 'ID'], $newValue)) {
                    $on = $data['on'];
                    if (!$emulateStatus) {
                        $i = 0;
                        while (($newValue != self::getColorValue($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $on = self::getColorValue($configuration[self::propertyPrefix . 'ID']) > 0;
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
            'action.devices.traits.ColorSpectrum',
            'action.devices.traits.OnOff'
        ];
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.ColorAbsolute',
            'action.devices.commands.OnOff'
        ];
    }

    public static function getAttributes()
    {
        return [
            'colorModel' => 'rgb'
        ];
    }
}
