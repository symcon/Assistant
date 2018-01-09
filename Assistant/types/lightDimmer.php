<?php

declare(strict_types=1);

class DeviceTypeLightDimmer
{
    private static $implementedType = 'LIGHT';

    private static $implementedTraits = [
        'BrightnessOnOff'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 1;
    }

    public static function getCaption()
    {
        return 'Light (Dimmer)';
    }
}

DeviceTypeRegistry::register('LightDimmer');
