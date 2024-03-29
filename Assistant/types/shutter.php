<?php

declare(strict_types=1);

class DeviceTypeShutter extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'OpenCloseShutter'
        ];
        $this->implementedType = 'BLINDS';
    }

    public function getPosition()
    {
        return 20;
    }

    public function getCaption()
    {
        return 'Shutter';
    }

    public function getTranslations()
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
