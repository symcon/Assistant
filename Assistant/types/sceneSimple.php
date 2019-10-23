<?php

declare(strict_types=1);

class DeviceTypeSceneSimple
{
    use HelperDeviceType;
    private static $implementedType = 'SCENE';

    private static $implementedTraits = [
        'SceneSimple'
    ];

    private static $displayStatusPrefix = false;

    public static function getPosition()
    {
        return 100;
    }

    public static function getCaption()
    {
        return 'Scenes';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Scenes' => 'Szenen',
                'Script' => 'Skript'
            ]
        ];
    }
}

DeviceTypeRegistry::register('SceneSimple');
