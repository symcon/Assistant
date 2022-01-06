<?php

declare(strict_types=1);

class DeviceTraitBrightness extends DeviceTrait
{
    use HelperDimDevice;
    const propertyPrefix = 'Brightness';

    public function getColumns()
    {
        return [
            [
                'caption' => 'Brightness Variable',
                'name'    => self::propertyPrefix . 'ID',
                'width'   => '200px',
                'add'     => 0,
                'edit'    => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        if ($configuration[self::propertyPrefix . 'ID'] == 0) {
            return 'OK';
        } else {
            return self::getDimCompatibility($configuration[self::propertyPrefix . 'ID']);
        }
    }

    public function getStatusPrefix()
    {
        return 'Brightness: ';
    }

    public function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            return [
                'brightness' => intval(self::getDimValue($configuration[self::propertyPrefix . 'ID']))
            ];
        } else {
            return [];
        }
    }

    public function doExecute($configuration, $command, $data, $emulateStatus)
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

    public function getObjectIDs($configuration)
    {
        return [
            $configuration[self::propertyPrefix . 'ID']
        ];
    }

    public function supportedTraits($configuration)
    {
        if ($configuration[self::propertyPrefix . 'ID'] != 0) {
            return [
                'action.devices.traits.Brightness'
            ];
        } else {
            return [];
        }
    }

    public function supportedCommands()
    {
        return [
            'action.devices.commands.BrightnessAbsolute'
        ];
    }

    protected function getSupportedProfiles()
    {
        return [
            self::propertyPrefix . 'ID' => ['~Intensity.100', '~Intensity.255', '~Intensity.1']
        ];
    }
}
