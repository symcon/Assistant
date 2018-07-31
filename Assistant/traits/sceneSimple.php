<?php

declare(strict_types=1);

class DeviceTraitSceneSimple
{
    const propertyPrefix = 'SceneSimple';

    use HelperStartScript;

    public static function getColumns()
    {
        return [
            [
                'label' => 'Script',
                'name'  => self::propertyPrefix . 'ScriptID',
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
        return self::getScriptCompatibility($configuration[self::propertyPrefix . 'ScriptID']);
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
                if (self::startScript($configuration[self::propertyPrefix . 'ScriptID'], true)) {
                    return [
                        'ids'    => [$configuration['ID']],
                        'status' => 'SUCCESS',
                        'states' => new stdClass()
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

    public static function getVariableIDs($configuration) {
        return [];
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
            'sceneReversible' => false
        ];
    }
}
