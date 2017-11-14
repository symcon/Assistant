<?php

declare(strict_types=1);

class DeviceTypeRegistry
{
    const classPrefix = 'DeviceType';
    const propertyPrefix = 'Device';

    private static $supportedDeviceTypes = [];

    public static function register(string $deviceType): void
    {

        //Check if the same service was already registered
        if (in_array($deviceType, self::$supportedDeviceTypes)) {
            throw new Exception('Cannot register deviceType! ' . $deviceType . ' is already registered.');
        }
        //Add to our static array
        self::$supportedDeviceTypes[] = $deviceType;
    }

    private $registerProperty = null;
    private $instanceID = 0;

    public function __construct(int $instanceID, callable $registerProperty)
    {
        $this->registerProperty = $registerProperty;
        $this->instanceID = $instanceID;
    }

    public function registerProperties(): void
    {

        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $actionType) {
            ($this->registerProperty)(self::propertyPrefix . $actionType, '[]');
        }
    }

    public function doSyncDevices(): array
    {
        $devices = [];

        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                $devices[] = call_user_func(self::classPrefix . $deviceType . '::doSync', $configuration);
            }
        }

        return $devices;
    }

    public function doQueryDevice($deviceID): array
    {
        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                if ($configuration['ID'] == $deviceID) {
                    return call_user_func(self::classPrefix . $deviceType . '::doQuery', $configuration);
                }
            }
        }

        //Return an offline device if the id could not be found
        return [
            'online' => false
        ];
    }

    public function doExecuteDevice($deviceID, $deviceCommand, $deviceParams)
    {
        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                if ($configuration['ID'] == $deviceID) {
                    return call_user_func(self::classPrefix . $deviceType . '::doExecute', $configuration, $deviceCommand, $deviceParams);
                }
            }
        }

        //Return an device not found error
        return [
            'id'        => $deviceID,
            'status'    => 'ERROR',
            'errorCode' => 'deviceNotFound'
        ];
    }

    public function getConfigurationForm(): array
    {
        $form = [];

        $sortedDeviceTypes = self::$supportedDeviceTypes;
        uasort($sortedDeviceTypes, function ($a, $b) {
            $posA = call_user_func(self::classPrefix . $a . '::getPosition');
            $posB = call_user_func(self::classPrefix . $b . '::getPosition');

            return ($posA < $posB) ? -1 : 1;
        });

        foreach ($sortedDeviceTypes as $deviceType) {
            $columns = [
                [
                    'label' => 'ID',
                    'name'  => 'ID',
                    'width' => '35px',
                    'add'   => 0,
                    'save'  => true
                ],
                [
                    'label' => 'Name',
                    'name'  => 'Name',
                    'width' => '150px',
                    'add'   => '',
                    'edit'  => [
                        'type' => 'ValidationTextBox'
                    ]
                ], //We will insert the custom columns here
                [
                    'label' => 'Status',
                    'name'  => 'Status',
                    'width' => '50px',
                    'add'   => '-'
                ]
            ];

            array_splice($columns, 2, 0, call_user_func(self::classPrefix . $deviceType . '::getColumns'));

            $values = [];

            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                $values[] = [
                    'Status' => call_user_func(self::classPrefix . $deviceType . '::getStatus', $configuration)
                ];
            }

            $form[] = [
                'type'     => 'List',
                'name'     => self::propertyPrefix . $deviceType,
                'caption'  => call_user_func(self::classPrefix . $deviceType . '::getCaption'),
                'rowCount' => 5,
                'add'      => true,
                'delete'   => true,
                'sort'     => [
                    'column'    => 'Name',
                    'direction' => 'ascending'
                ],
                'columns' => $columns,
                'values'  => $values
            ];
        }

        return $form;
    }
}
