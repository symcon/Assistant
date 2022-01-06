<?php

declare(strict_types=1);

class DeviceTypeSceneDeactivatable extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'SceneDeactivatable'
        ];
        $this->implementedType = 'SCENE';
    }

    public function getPosition()
    {
        return 101;
    }

    public function getCaption()
    {
        return 'Scenes (Deactivatable)';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Scenes (Deactivatable)' => 'Szenen (deaktivierbar)',
                'Activate Action'        => 'Aktion beim Aktivieren',
                'Deactivate Action'      => 'Aktion beim Deaktivieren'
            ]
        ];
    }
}

DeviceTypeRegistry::register('SceneDeactivatable');
