<?php

declare(strict_types=1);

class DeviceTypeThermostat extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'TemperatureSetting'
        ];
        $this->implementedType = 'THERMOSTAT';
    }

    public function getPosition()
    {
        return 10;
    }

    public function getCaption()
    {
        return 'Thermostat';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Thermostat'          => 'Thermostat',
                'Setpoint'            => 'Sollwert',
                'Ambient Temperature' => 'Umgebungstemperatur'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Thermostat');
