<?php

declare(strict_types=1);

include_once __DIR__ . '/oauth.php';
include_once __DIR__ . '/simulate.php';
include_once __DIR__ . '/helper.php';
include_once __DIR__ . '/registry.php';
include_once __DIR__ . '/traits/autoload.php';
include_once __DIR__ . '/types/autoload.php';

class Assistant extends IPSModule
{
    use WebOAuth;
    use Simulate;

    private $registry = null;

    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID);

        $this->registry = new DeviceTypeRegistry(
            $this->InstanceID,
            function ($Name, $Value) {
                $this->RegisterPropertyString($Name, $Value);
            }
        );
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Each accessory is allowed to register properties for persistent data
        $this->registry->registerProperties();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->RegisterOAuth('google_smarthome');

        // We need to check for IDs that are empty and assign a proper ID
        $this->registry->updateProperties();
    }

    private function ProcessSync(): array
    {
        return [
            'agentUserId' => md5(IPS_GetLicensee()),
            'devices' => $this->registry->doSyncDevices()
        ];
    }

    private function ProcessQuery($payload): array
    {
        if (!isset($payload['devices'])) {
            throw new Exception('devices is undefined');
        }
        if (!is_array($payload['devices'])) {
            throw new Exception('devices is malformed');
        }
        $devices = [];
        foreach ($payload['devices'] as $device) {
            if (!isset($device['id'])) {
                throw new Exception('id is undefined');
            }
            $devices[$device['id']] = $this->registry->doQueryDevice($device['id']);
        }
        return [
            'devices' => $devices
        ];
    }

    private function ProcessExecute($payload): array
    {
        if (!isset($payload['commands'])) {
            throw new Exception('commands is undefined');
        }
        if (!is_array($payload['commands'])) {
            throw new Exception('commands is malformed');
        }
        $results = [];

        foreach ($payload['commands'] as $command) {
            if (!isset($command['devices'])) {
                throw new Exception('devices is undefined');
            }
            if (!is_array($command['devices'])) {
                throw new Exception('devices is malformed');
            }
            if (!isset($command['execution'])) {
                throw new Exception('execution is undefined');
            }
            if (!is_array($command['execution'])) {
                throw new Exception('execution is malformed');
            }
            //Execute each executions command for each device
            foreach ($command['execution'] as $execute) {
                foreach ($command['devices'] as $device) {
                    $results[] = $this->registry->doExecuteDevice($device['id'], $execute['command'], $execute['params']);
                }
            }
        }

        //Merge results into Google's result format
        $commands = [];

        foreach ($results as $result) {
            $found = false;
            foreach ($commands as $command) {
                //lets assume for now there can only be one result per state
                if ($command['state'] == $result['state']) {
                    $commands['ids'][] = $result['id'];
                    $found = true;
                }
            }
            if (!$found) {
                $command = $result;
                $command['ids'] = [$command['id']];
                unset($command['id']);
                $commands[] = $command;
            }
        }

        return [
            'commands' => $commands
        ];
    }

    //See: https://developers.google.com/actions/smarthome/create-app
    private function ProcessRequest($request): array
    {
        if (!isset($request['requestId'])) {
            throw new Exception('requestId is undefined');
        }
        if (!isset($request['inputs'])) {
            throw new Exception('inputs is undefined');
        }
        if (!is_array($request['inputs']) || count($request['inputs']) != 1) {
            throw new Exception('inputs is malformed');
        }
        //Google has defined an array but ony sends one value!
        $input = $request['inputs'][0];

        switch ($input['intent']) {
            case 'action.devices.SYNC':
                $payload = $this->ProcessSync();
                break;
            case 'action.devices.QUERY':
                $payload = $this->ProcessQuery($input['payload']);
                break;
            case 'action.devices.EXECUTE':
                $payload = $this->ProcessExecute($input['payload']);
                break;
            default:
                throw new Exception('Invalid intent');
        }

        return [
            'requestId' => $request['requestId'],
            'payload'   => $payload
        ];

    }

    protected function ProcessData(array $data): array
    {
        $this->SendDebug('Request', print_r($data, true), 0);

        //Redirect errors to our variable to push them into Debug
        ob_start();
        $result = $this->ProcessRequest($data);
        $error = ob_get_contents();
        if ($error != '') {
            $this->SendDebug('Error', $error, 0);
        }
        ob_end_clean();

        $this->SendDebug('Response', print_r($result, true), 0);

        return $result;
    }

    protected function ProcessOAuthData()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $this->ProcessData($data);
        echo json_encode($result);
    }

    public function GetConfigurationForm()
    {
        //Check Connect availability
        $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');
        if (IPS_GetInstance($ids[0])['InstanceStatus'] != 102) {
            $message = 'Error: Symcon Connect is not active!';
        } else {
            $message = 'Status: Symcon Connect is OK!';
        }

        $connect = [
            [
                'type'  => 'Label',
                'label' => $message
            ]
        ];

        $deviceTypes = $this->registry->getConfigurationForm();

        return json_encode(['elements' => array_merge($connect, $deviceTypes)]);
    }
}
