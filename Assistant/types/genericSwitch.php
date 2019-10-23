<?php

declare(strict_types=1);

class DeviceTypeGenericSwitch
{
    use HelperDeviceType;
    private static $implementedType = 'SWITCH';

    private static $implementedTraits = [
        'OnOff'
    ];

    private static $displayStatusPrefix = false;

    public static function getPosition()
    {
        return 50;
    }

    public static function getCaption()
    {
        return 'Generic Switch';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Generic Switch' => 'Generischer Schalter',
                'Variable'       => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('GenericSwitch');
