<?php

declare(strict_types=1);

class DeviceTypeSceneDeactivatable
{
    private static $implementedType = 'SCENE';

    private static $implementedTraits = [
        'SceneDeactivatable'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 4;
    }

    public static function getCaption()
    {
        return 'Scenes (Deactivatable)';
    }
}

DeviceTypeRegistry::register('SceneDeactivatable');
