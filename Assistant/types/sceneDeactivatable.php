<?php

declare(strict_types=1);

class DeviceTypeSceneDeactivatable
{
    private static $implementedType = 'SCENE';

    private static $implementedTraits = [
        'SceneDeactivatable'
    ];

    private static $displayStatusPrefix = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 101;
    }

    public static function getCaption()
    {
        return 'Scenes (Deactivatable)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Scenes (Deactivatable)' => 'Szenen (deaktivierbar)',
                'ActivateScript'         => 'AktivierenSkript',
                'DeactivateScript'       => 'DeaktivierenSkript'
            ]
        ];
    }
}

DeviceTypeRegistry::register('SceneDeactivatable');
