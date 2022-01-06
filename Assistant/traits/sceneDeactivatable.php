<?php

declare(strict_types=1);

class DeviceTraitSceneDeactivatable extends DeviceTrait
{
    use HelperStartAction;
    const propertyPrefix = 'SceneDeactivatable';

    public function getColumns()
    {
        return [
            [
                'caption' => 'Activate Action',
                'name'    => self::propertyPrefix . 'ActivateAction',
                'width'   => '400px',
                'add'     => '{}',
                'edit'    => [
                    'type'            => 'SelectAction',
                    'saveEnvironment' => false,
                    'saveParent'      => false,
                    'environment'     => 'VoiceControl'
                ]
            ],
            [
                'caption' => 'Deactivate Action',
                'name'    => self::propertyPrefix . 'DeactivateAction',
                'width'   => '400px',
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
        $activateStatus = $this->getActionCompatibility($configuration[self::propertyPrefix . 'ActivateAction']);
        if ($activateStatus != 'OK') {
            return $activateStatus;
        } else {
            return $this->getActionCompatibility($configuration[self::propertyPrefix . 'DeactivateAction']);
        }
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
                $action = $configuration[self::propertyPrefix . (($data['deactivate']) ? 'DeactivateAction' : 'ActivateAction')];
                if ($this->startAction($action, $this->instanceID)) {
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
        $result = [];
        foreach (['ActivateAction', 'DeactivateAction'] as $field) {
            if ($this->getActionCompatibility($configuration[self::propertyPrefix . $field]) === 'OK') {
                $result[] = json_decode($configuration[self::propertyPrefix . $field], true)['parameters']['TARGET'];
            }
        }
        return $result;
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
            'sceneReversible' => true
        ];
    }
}
