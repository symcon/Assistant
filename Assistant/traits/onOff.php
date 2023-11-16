<?php

declare(strict_types=1);

class DeviceTraitOnOff extends DeviceTrait
{
    use HelperSwitchDevice;
    public const propertyPrefix = 'OnOff';

    public function getColumns()
    {
        return [
            [
                'caption' => 'Switch Variable',
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
        return self::getSwitchCompatibility($configuration[self::propertyPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Switch: ';
    }

    public function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            return [
                'on' => self::getSwitchValue($configuration[self::propertyPrefix . 'ID'])
            ];
        } else {
            return [];
        }
    }

    public function doExecute($configuration, $command, $data, $emulateStatus)
    {
        switch ($command) {
            case 'action.devices.commands.OnOff':
                if (self::switchDevice($configuration[self::propertyPrefix . 'ID'], $data['on'])) {
                    $on = $data['on'];
                    if (!$emulateStatus) {
                        $i = 0;
                        while (($data['on'] != self::getSwitchValue($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $on = self::getSwitchValue($configuration[self::propertyPrefix . 'ID']);
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

    public function getObjectIDs($configuration)
    {
        return [
            $configuration[self::propertyPrefix . 'ID']
        ];
    }

    public function supportedTraits($configuration)
    {
        return [
            'action.devices.traits.OnOff'
        ];
    }

    public function supportedCommands()
    {
        return [
            'action.devices.commands.OnOff'
        ];
    }

    protected function getSupportedProfiles()
    {
        return [
            self::propertyPrefix . 'ID' => ['~Switch']
        ];
    }
}
