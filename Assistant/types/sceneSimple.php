<?php

declare(strict_types=1);

class DeviceTypeSceneSimple extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'SceneSimple'
        ];
        $this->implementedType = 'SCENE';
    }

    public function getPosition()
    {
        return 100;
    }

    public function getCaption()
    {
        return 'Scenes';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Scenes' => 'Szenen',
                'Action' => 'Aktion'
            ]
        ];
    }
}

DeviceTypeRegistry::register('SceneSimple');
