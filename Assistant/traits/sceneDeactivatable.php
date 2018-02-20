<?php

declare(strict_types=1);

class DeviceTraitSceneDeactivatable
{
    const propertyPrefix = 'SceneDeactivatable';

    use HelperStartScript;

    public static function getColumns()
    {
        return [
            [
                'label' => 'Activate Script',
                'name'  => self::propertyPrefix . 'ActivateID',
                'width' => '200px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectScript'
                ]
            ],
            [
                'label' => 'Deactivate Script',
                'name'  => self::propertyPrefix . 'DeactivateID',
                'width' => '200px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectScript'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        $activateStatus = self::getScriptCompatibility($configuration[self::propertyPrefix . 'ActivateID']);
        return ($activateStatus != 'OK') ? $activateStatus : self::getScriptCompatibility($configuration[self::propertyPrefix . 'DeactivateID']);
    }

    public static function doQuery($configuration)
    {
        return [
            'online' => (self::getStatus($configuration) == 'OK')
        ];
    }

    public static function doExecute($configuration, $command, $data)
    {
        switch ($command) {
            case 'action.devices.commands.ActivateScene':
                $scriptID = $data['deactivate'] ? $configuration[self::propertyPrefix . 'DeactivateID'] : $configuration[self::propertyPrefix . 'ActivateID'];
                if (self::startScript($scriptID, !$data['deactivate'])) {
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => [
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
            'action.devices.traits.Scene'
        ];
    }

    public static function supportedCommands()
    {
        return [
            'action.devices.commands.ActivateScene'
        ];
    }

    public static function getAttributes()
    {
        return [
            'sceneReversible' => true
        ];
    }
}
