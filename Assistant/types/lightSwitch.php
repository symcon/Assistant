<?php

declare(strict_types=1);
class DeviceTypeLightSwitch
{
    private static $implementedType = 'LIGHT';

    private static $implementedTraits = [
        'OnOff'
    ];

    use HelperDeviceTypeColumns;
    use HelperDeviceTypeStatus;
    use HelperDeviceTypeSync;
    use HelperDeviceTypeQuery;
    use HelperDeviceTypeExecute;

    public static function getPosition()
    {
        return 0;
    }

    public static function getCaption()
    {
        return 'Light (Switch)';
    }
}

DeviceTypeRegistry::register('LightSwitch');
