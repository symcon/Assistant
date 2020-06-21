<?php

declare(strict_types=1);

class DeviceTypeRegistry
{
    const classPrefix = 'DeviceType';
    const propertyPrefix = 'Device';

    private static $supportedDeviceTypes = [];

    private $registerProperty = null;
    private $sendDebug = null;
    private $instanceID = 0;

    public function __construct(int $instanceID, callable $registerProperty, callable $sendDebug)
    {
        $this->sendDebug = $sendDebug;
        $this->registerProperty = $registerProperty;
        $this->instanceID = $instanceID;
    }

    public static function register(string $deviceType): void
    {

        //Check if the same service was already registered
        if (in_array($deviceType, self::$supportedDeviceTypes)) {
            throw new Exception('Cannot register deviceType! ' . $deviceType . ' is already registered.');
        }
        //Add to our static array
        self::$supportedDeviceTypes[] = $deviceType;
    }

    public function registerProperties(): void
    {

        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $actionType) {
            ($this->registerProperty)(self::propertyPrefix . $actionType, '[]');
        }
    }

    public function updateProperties(): void
    {
        $ids = [];

        //Check that all IDs have distinct values and build an id array
        foreach (self::$supportedDeviceTypes as $actionType) {
            $datas = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $actionType), true);
            foreach ($datas as $data) {
                //Skip over uninitialized zero values
                if ($data['ID'] != '') {
                    if (in_array($data['ID'], $ids)) {
                        throw new Exception('ID has to be unique for all devices');
                    }
                    $ids[] = $data['ID'];
                }
            }
        }

        //Sort array and determine highest value
        rsort($ids);

        //Start with zero
        $highestID = 0;

        //Highest value is first
        if ((count($ids) > 0) && ($ids[0] > 0)) {
            $highestID = $ids[0];
        }

        //Update all properties and ids which are currently empty
        $wasChanged = false;
        foreach (self::$supportedDeviceTypes as $actionType) {
            $wasUpdated = false;
            $datas = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $actionType), true);
            foreach ($datas as &$data) {
                if ($data['ID'] == '') {
                    $data['ID'] = (string) (++$highestID);
                    $wasChanged = true;
                    $wasUpdated = true;
                }
            }
            if ($wasUpdated) {
                IPS_SetProperty($this->instanceID, self::propertyPrefix . $actionType, json_encode($datas));
            }
        }

        //This is dangerous. We need to be sure that we do not end in an endless loop!
        if ($wasChanged) {
            //Save. This will start a recursion. We need to be careful, that the recursion stops after this.
            IPS_ApplyChanges($this->instanceID);
        }
    }

    public function doSyncDevices(): array
    {
        $devices = [];

        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                if ($this->isOK($deviceType, $configuration)) {
                    $devices[] = call_user_func(self::classPrefix . $deviceType . '::doSync', $configuration);
                }
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
        $emulateStatus = IPS_GetProperty($this->instanceID, 'EmulateStatus');
        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                if ($configuration['ID'] == $deviceID) {
                    return call_user_func(self::classPrefix . $deviceType . '::doExecute', $configuration, $deviceCommand, $deviceParams, $emulateStatus);
                }
            }
        }

        //Return an device not found error
        return [
            'ids'       => [$deviceID],
            'status'    => 'ERROR',
            'errorCode' => 'deviceNotFound'
        ];
    }

    public function getObjectIDs()
    {
        $result = [];
        // Add all variable IDs of all devices
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                $result = array_unique(array_merge($result, call_user_func(self::classPrefix . $deviceType . '::getObjectIDs', $configuration)));
            }
        }

        return $result;
    }

    public function ReportState($variableUpdates)
    {
        $states = [];
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                $variableIDs = call_user_func(self::classPrefix . $deviceType . '::getObjectIDs', $configuration);
                if ((count(array_intersect($variableUpdates, $variableIDs)) > 0) && ($this->isOK($deviceType, $configuration))) {
                    $queryResult = call_user_func(self::classPrefix . $deviceType . '::doQuery', $configuration);
                    if (!isset($queryResult['status']) || ($queryResult['status'] != 'ERROR')) {
                        $states[$configuration['ID']] = call_user_func(self::classPrefix . $deviceType . '::doQuery', $configuration);
                    }
                }
            }
        }

        if (count($states) == 0) {
            return true;
        }

        $connectControlIDs = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');

        if (count($connectControlIDs) == 0) {
            echo 'No Connect Control found';
            return false;
        }

        if (IPS_GetInstance($connectControlIDs[0])['InstanceStatus'] !== IS_ACTIVE) {
            return false;
        }

        //This is available with IP-Symcon 5.4 (July 2020)
        if (function_exists('CC_SendGoogleAssistantStateReport')) {
            $states = json_encode($states);

            ($this->sendDebug)('States', $states, 0);

            CC_SendGoogleAssistantStateReport($connectControlIDs[0], $states);
        } else {
            $guid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
            $jsonRequest = json_encode([
                'requestId'   => $guid,
                'agentUserId' => md5(IPS_GetLicensee()),
                'payload'     => [
                    'devices' => [
                        'states' => $states
                    ]
                ]
            ]);

            ($this->sendDebug)('JSON Request', $jsonRequest, 0);

            $response = CC_MakeRequest($connectControlIDs[0], '/google/reportstate', $jsonRequest);
        }

        return true;
    }

    public function getConfigurationForm(): array
    {
        $form = [];

        $sortedDeviceTypes = self::$supportedDeviceTypes;
        uasort($sortedDeviceTypes, function ($a, $b)
        {
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
                    'add'   => '',
                    'save'  => true
                ],
                [
                    'label' => 'Name',
                    'name'  => 'Name',
                    'width' => 'auto',
                    'add'   => '',
                    'edit'  => [
                        'type' => 'ValidationTextBox'
                    ]
                ], //We will insert the custom columns here
                [
                    'label' => 'Status',
                    'name'  => 'Status',
                    'width' => '200px',
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
                'type'    => 'ExpansionPanel',
                'caption' => call_user_func(self::classPrefix . $deviceType . '::getCaption'),
                'items'   => [[
                    'type'     => 'List',
                    'name'     => self::propertyPrefix . $deviceType,
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => $columns,
                    'values'  => $values
                ]]
            ];
        }

        return $form;
    }

    public function getTranslations(): array
    {
        $translations = [
            'de' => [
                'Name'                                                                                                                                                                      => 'Name',
                'ID'                                                                                                                                                                        => 'ID',
                'Status'                                                                                                                                                                    => 'Status',
                'Symcon Connect is not active!'                                                                                                                                             => 'Symcon Connect ist nicht aktiv!',
                'Symcon Connect is OK!'                                                                                                                                                     => 'Symcon Connect ist OK!',
                'License is not yet linked with Google Assistant!'                                                                                                                          => 'Lizenz wurde noch nicht mit Google Assistant verknüpft!',
                'If you added/updated/removed devices press this button to notify Google'                                                                                                   => 'Wenn Sie Geräte hinzugefügt, aktualisiert oder entfernt haben, betätigen Sie diesen Button um Google zu informieren',
                'Request device update'                                                                                                                                                     => 'Geräteupdate anfragen',
                'Expert Options'                                                                                                                                                            => 'Expertenoptionen',
                'Please check the documentation before handling these settings. These settings do not need to be changed under regular circumstances.'                                      => 'Bitte prüfen Sie die Dokumentation bevor Sie diese Einstellungen anpassen. Diese Einstellungen müssen unter normalen Umständen nicht verändert werden.',
                'Emulate Status'                                                                                                                                                            => 'Status emulieren',
                'Variable Update Semaphore is unavailable'                                                                                                                                  => 'Semaphore für Variablenaktualisierung ist nicht verfügbar',
                'The connection to your Google Home Account was lost. Reconnect to Symcon by opening your Google Home app, clicking the Symcon service, and selecting "Search for devices"' => 'Die Verbindung zu Ihrem Google Home Account wurde getrennt. Zum erneuten Verbinden, öffnen Sie die Google Home App, tippen auf den Symcon-Service und wählen Sie "Nach Geräten suchen"'
            ]
        ];

        foreach (self::$supportedDeviceTypes as $deviceType) {
            foreach (call_user_func(self::classPrefix . $deviceType . '::getTranslations') as $language => $languageTranslations) {
                if (array_key_exists($language, $translations)) {
                    foreach ($languageTranslations as $original => $translated) {
                        if (array_key_exists($original, $translations[$language])) {
                            if ($translations[$language][$original] != $translated) {
                                throw new Exception('Different translations ' . $translated . ' + ' . $translations[$language][$original] . ' for original ' . $original . ' was found!');
                            }
                        } else {
                            $translations[$language][$original] = $translated;
                        }
                    }
                } else {
                    $translations[$language] = $languageTranslations;
                }
            }
        }

        return $translations;
    }

    public function isOK($deviceType, $configuration)
    {
        return (call_user_func(self::classPrefix . $deviceType . '::getStatus', $configuration) == 'OK') && ($configuration['ID'] != '');
    }
}
