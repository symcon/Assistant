<?php

declare(strict_types=1);

class DeviceTypeThermostat
{
    private static $implementedType = 'THERMOSTAT';

    private static $implementedTraits = [
        'TemperatureSetting'
    ];

    private static $displayStatusPrefix = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 10;
    }

    public static function getCaption()
    {
        return 'Thermostat';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Thermostat' => 'Thermostat',
                'Setpoint'   => 'Sollwert',
                'Ambient Temperature' => 'Umgebungstemperatur'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Thermostat');
