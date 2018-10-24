<?php

declare(strict_types=1);

class DeviceTypeLightExpert
{
    private static $implementedType = 'LIGHT';

    private static $implementedTraits = [
        'OnOff', 'Brightness', 'ColorSpectrum'
    ];

    private static $displayStatusPrefix = true;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 3;
    }

    public static function getCaption()
    {
        return 'Light (Expert)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Light (Expert)'      => 'Licht (Experte)',
                'Switch Variable'     => 'Schaltervariable',
                'Brightness Variable' => 'Helligkeitsvariable',
                'Color Variable'      => 'Farbvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightExpert');
