<?php

declare(strict_types=1);

class DeviceTypeRegistry extends CommonConnectRegistry
{
    private $sendDebug = null;

    public function __construct(int $instanceID, callable $registerProperty, callable $sendDebug)
    {
        parent::__construct($instanceID, $registerProperty, 'DeviceType');
        $this->sendDebug = $sendDebug;
    }

    public function doSyncDevices(): array
    {
        $devices = [];

        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                if ($this->isOK($deviceType, $configuration)) {
                    $devices[] = $this->generateDeviceTypeObject($deviceType)->doSync($configuration);
                }
                else {
                    $devices[] = $this->generateDeviceTypeObject($deviceType)->getStatus($configuration);
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
                    return $this->generateDeviceTypeObject($deviceType)->doQuery($configuration);
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
                    return $this->generateDeviceTypeObject($deviceType)->doExecute($configuration, $deviceCommand, $deviceParams, $emulateStatus);
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

    public function ReportState($variableUpdates)
    {
        $states = [];
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                $deviceTypeObject = $this->generateDeviceTypeObject($deviceType);
                $variableIDs = $deviceTypeObject->getObjectIDs($configuration);
                if ((count(array_intersect($variableUpdates, $variableIDs)) > 0) && ($this->isOK($deviceType, $configuration))) {
                    $queryResult = $deviceTypeObject->doQuery($configuration);
                    if (!isset($queryResult['status']) || ($queryResult['status'] != 'ERROR')) {
                        $states[$configuration['ID']] = $queryResult;
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
}
