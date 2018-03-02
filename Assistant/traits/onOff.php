<?php

declare(strict_types=1);

class DeviceTraitOnOff
{
    const propertyPrefix = 'OnOff';

    use HelperSwitchDevice;

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
        return self::getSwitchCompatibility($configuration[self::propertyPrefix . 'ID']);
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            if ((time() - IPS_GetVariable($configuration[self::propertyPrefix . 'ID'])['VariableUpdated']) > 30 * 60 * 60) {
                return [
                    'ids'       => [$configuration['ID']],
                    'status'    => 'ERROR',
                    'errorCode' => 'deviceTurnedOff'
                ];
            }
            return [
                'on' => self::getSwitchValue($configuration[self::propertyPrefix . 'ID'])
            ];
        } else {
            return [];
        }
    }

    public static function doExecute($configuration, $command, $data)
    {
        switch ($command) {
            case 'action.devices.commands.OnOff':
                if (self::switchDevice($configuration[self::propertyPrefix . 'ID'], $data['on'])) {
                    $i = 0;
                    while (($data['on'] != self::getSwitchValue($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                        $i++;
                        usleep(100000);
                    }
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => [
                            'on'     => self::getSwitchValue($configuration[self::propertyPrefix . 'ID']),
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

    public static function supportedTraits()
    {
        return [
            'action.devices.traits.OnOff'
        ];
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.OnOff'
        ];
    }

    public static function getAttributes()
    {
        return [];
    }
}
