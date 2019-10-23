<?php

declare(strict_types=1);

class DeviceTypeShutter
{
    use HelperDeviceType;
    private static $implementedType = 'BLINDS';

    private static $implementedTraits = [
        'OpenCloseShutter'
    ];

    private static $displayStatusPrefix = false;

    public static function getPosition()
    {
        return 20;
    }

    public static function getCaption()
    {
        return 'Shutter';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Shutter'          => 'Rollladen',
                'Shutter Variable' => 'Rollladenvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Shutter');
