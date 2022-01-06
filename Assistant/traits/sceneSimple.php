<?php

declare(strict_types=1);

class DeviceTraitSceneSimple extends DeviceTrait
{
    use HelperStartAction;
    const propertyPrefix = 'SceneSimple';

    public function getColumns()
    {
        return [
            [
                'caption' => 'Action',
                'name'    => self::propertyPrefix . 'Action',
                'width'   => '500px',
                'add'     => '{}',
                'edit'    => [
                    'type'            => 'SelectAction',
                    'saveEnvironment' => false,
                    'saveParent'      => false,
                    'environment'     => 'VoiceControl'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        return self::getActionCompatibility($configuration[self::propertyPrefix . 'Action']);
    }

    public function getStatusPrefix()
    {
        return 'Scene: ';
    }

    public function doQuery($configuration)
    {
        return [
            'online' => (self::getStatus($configuration) == 'OK')
        ];
    }

    public function doExecute($configuration, $command, $data, $emulateStatus)
    {
        switch ($command) {
            case 'action.devices.commands.ActivateScene':
                if (self::startAction($configuration[self::propertyPrefix . 'Action'], $this->instanceID)) {
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

    public function getObjectIDs($configuration)
    {
        if ($this->getStatus($configuration) === 'OK') {
            return [json_decode($configuration[self::propertyPrefix . 'Action'], true)['parameters']['TARGET']];
        } else {
            return [];
        }
    }

    public function supportedTraits($configuration)
    {
        return [
            'action.devices.traits.Scene'
        ];
    }

    public function supportedCommands()
    {
        return [
            'action.devices.commands.ActivateScene'
        ];
    }

    public function getAttributes()
    {
        return [
            'sceneReversible' => false
        ];
    }
}
