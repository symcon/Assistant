<?php

declare(strict_types=1);

class DeviceTypeLightColor
{
    private static $implementedType = 'LIGHT';

    private static $implementedTraits = [
        'ColorSpectrumBrightnessOnOff'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 2;
    }

    public static function getCaption()
    {
        return 'Light (Color)';
    }
}

DeviceTypeRegistry::register('LightColor');
