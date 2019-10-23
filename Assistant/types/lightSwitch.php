<?php

declare(strict_types=1);

class DeviceTypeLightSwitch
{
    use HelperDeviceType;
    private static $implementedType = 'LIGHT';

    private static $implementedTraits = [
        'OnOff'
    ];

    private static $displayStatusPrefix = false;

    public static function getPosition()
    {
        return 0;
    }

    public static function getCaption()
    {
        return 'Light (Switch)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Light (Switch)' => 'Licht (Schalter)',
                'Variable'       => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightSwitch');
