<?php

declare(strict_types=1);

class DeviceTypeThermostat
{
    private static $implementedType = 'THERMOSTAT';

    private static $implementedTraits = [
        'TemperatureSetting'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 3;
    }

    public static function getCaption()
    {
        return 'Thermostat';
    }
}

DeviceTypeRegistry::register('Thermostat');
