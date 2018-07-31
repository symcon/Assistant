<?php

declare(strict_types=1);

class DeviceTypeGenericSwitch
{
    private static $implementedType = 'SWITCH';

    private static $implementedTraits = [
        'OnOff'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 20;
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
