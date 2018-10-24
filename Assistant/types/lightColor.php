<?php

declare(strict_types=1);

class DeviceTypeLightColor
{
    private static $implementedType = 'LIGHT';

    private static $implementedTraits = [
        'ColorSpectrumBrightnessOnOff'
    ];

    private static $displayStatusPrefix = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 2;
    }

    public static function getCaption()
    {
        return 'Light (Color)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Light (Color)' => 'Licht (Farbe)',
                'Variable'      => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightColor');
