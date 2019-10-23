<?php

declare(strict_types=1);

class DeviceTypeLightColor
{
    use HelperDeviceType;
    private static $implementedType = 'LIGHT';

    private static $implementedTraits = [
        'ColorSpectrumBrightnessOnOff'
    ];

    private static $displayStatusPrefix = false;

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
