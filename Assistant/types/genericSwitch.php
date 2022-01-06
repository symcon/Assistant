<?php

declare(strict_types=1);

class DeviceTypeGenericSwitch extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'OnOff'
        ];
        $this->implementedType = 'SWITCH';
    }

    public function getPosition()
    {
        return 50;
    }

    public function getCaption()
    {
        return 'Generic Switch';
    }

    public function getTranslations()
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
