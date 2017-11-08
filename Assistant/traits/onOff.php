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
                'name'  => 'OnOffID',
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
        $targetVariable = IPS_GetVariable($configuration['OnOffID']);

        if ($targetVariable['VariableType'] != 0 /* Boolean */) {
            return 'Bool required';
        }

        if ($targetVariable['VariableCustomAction'] != '') {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if (!($profileAction > 10000)) {
            return 'Action required';
        }

        return 'OK';
    }

    public static function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration['OnOffID'])) {
            return [
                'on' => GetValue($configuration['OnOffID'])
            ];
        } else {
            return [];
        }
    }

    public static function doExecute($configuration, $command, $data)
    {
        switch ($command) {
            case 'action.devices.commands.OnOff':
                if (self::switchDevice($configuration['OnOffID'], $data['on'])) {
                    return [
                        'id'     => $configuration['ID'],
                        'status' => 'SUCCESS',
                        'states' => [
                            'on'     => GetValue($configuration['OnOffID']),
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

    public static function supportedTrait()
    {
        return 'action.devices.traits.OnOff';
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.OnOff'
        ];
    }
}
