<?php

declare(strict_types=1);

include_once __DIR__ . '/oauth.php';
include_once __DIR__ . '/simulate.php';
include_once __DIR__ . '/registry.php';
include_once __DIR__ . '/helper/autoload.php';
include_once __DIR__ . '/traits/autoload.php';
include_once __DIR__ . '/types/autoload.php';

class Assistant extends IPSModule
{
    use WebOAuth;
    use Simulate;

    private $registry = null;
    private $apiKey = 'AIzaSyAtQwhb65ITHYJZXd-x7ziBfKkNj5rTo1k';

    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID);

        $this->registry = new DeviceTypeRegistry(
            $this->InstanceID,
            function ($Name, $Value) {
                $this->RegisterPropertyString($Name, $Value);
            },
            function ($Message, $Data, $Format) {
                $this->SendDebug($Message, $Data, $Format);
            }
        );
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        if (!IPS_VariableProfileExists('ThermostatMode.GA')) {
            IPS_CreateVariableProfile('ThermostatMode.GA', 1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 0, 'Off', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 1, 'Heat', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 2, 'Cool', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 3, 'On', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 4, 'HeatCool', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 5, 'Off', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 6, 'Off', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 7, 'Off', '', -1);
        }

        $this->RegisterTimer('ReportStateTimer', 0, 'GA_ReportState($_IPS[\'TARGET\']);');

        //Each accessory is allowed to register properties for persistent data
        $this->registry->registerProperties();

        $this->RegisterPropertyBoolean('EmulateStatus', false);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->RegisterOAuth('google_smarthome');

        // We need to check for IDs that are empty and assign a proper ID
        $this->registry->updateProperties();

        $objectIDs = $this->registry->getObjectIDs();

        if (method_exists($this, 'GetReferenceList')) {
            $refs = $this->GetReferenceList();
            foreach ($refs as $ref) {
                $this->UnregisterReference($ref);
            }

            foreach ($objectIDs as $id) {
                $this->RegisterReference($id);
            }
        }

        foreach ($this->GetMessageList() as $variableID => $messages) {
            $this->UnregisterMessage($variableID, 10603 /* VM_UPDATE */);
        }

        foreach ($objectIDs as $variableID) {
            if (IPS_VariableExists($variableID)) {
                $this->RegisterMessage($variableID, 10603 /* VM_UPDATE */);
            }
        }
    }

    public function MessageSink($timestamp, $senderID, $messageID, $data)
    {
        if ($messageID == 10603) {
            $currentVariableUpdatesString = $this->GetBuffer('VariableUpdates');
            $currentVariableUpdates = ($currentVariableUpdatesString == '') ? [] : json_decode($currentVariableUpdatesString, true);
            $currentVariableUpdates[] = $senderID;
            $this->SetBuffer('VariableUpdates', json_encode($currentVariableUpdates));
            $this->SetTimerInterval('ReportStateTimer', 1000);
        }
    }

    public function ReportState()
    {
        $variableUpdates = $this->GetBuffer('VariableUpdates');
        if ($variableUpdates != '') {
            $this->registry->ReportState(json_decode($variableUpdates, true));
            $this->SetBuffer('VariableUpdates', '');
        }
        $this->SetTimerInterval('ReportStateTimer', 0);
    }

    private function ProcessSync(): array
    {
        return [
            'agentUserId' => md5(IPS_GetLicensee()),
            'devices'     => $this->registry->doSyncDevices()
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
                    $this->SendDebug('Execute - ID', $device['id'], 0);
                    $this->SendDebug('Execute - Command', $execute['command'], 0);
                    $this->SendDebug('Execute - Params', print_r($execute['params'], true), 0);
                    $results[] = $this->registry->doExecuteDevice($device['id'], $execute['command'], $execute['params']);
                }
            }
        }

        //Merge results into Google's result format
        $commands = [];

        $this->SendDebug('Results', print_r($results, true), 0);
        foreach ($results as $result) {
            $found = false;
            foreach ($commands as $index => $command) {
                //lets assume for now there can only be one result per state
                if ($command['states'] == $result['states']) {
                    $commands[$index]['ids'] = array_merge($commands[$index]['ids'], $result['ids']);
                    $found = true;
                }
            }
            if (!$found) {
                $commands[] = $result;
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

        try {
            $result = $this->ProcessRequest($data);
        } catch (Exception $e) {
            $result = [
                'errorCode'   => 'protocolError',
                'debugString' => $e->getMessage()
            ];
        }
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

        // Translations are just added in the registry
        $connect = [
            [
                'type'  => 'Label',
                'label' => $message
            ]
        ];

        $syncRequest = [
            [
                'type'  => 'Label',
                'label' => 'If you added/updated/removed devices press this button to notify Google'
            ],
            [
                'type'    => 'Button',
                'label'   => 'Request device update',
                'onClick' => 'GA_RequestSync($id);'
            ],
            [
                'type'  => 'Label',
                'label' => ''
            ]
        ];

        $expertMode = [
            [
                'type'    => 'PopupButton',
                'caption' => 'Expert Options',
                'popup'   => [
                    'caption' => 'Expert Options',
                    'items'   => [
                        [
                            'type'    => 'Label',
                            'caption' => 'Please check the documentation before handling these settings. These settings do not need to be changed under regular circumstances.'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'caption' => 'Emulate Status',
                            'name'    => 'EmulateStatus'
                        ]
                    ]
                ]
            ]
        ];

        $deviceTypes = $this->registry->getConfigurationForm();

        return json_encode(['elements'      => array_merge($connect, $syncRequest, $deviceTypes, $expertMode),
                            'translations'  => $this->registry->getTranslations()]);
    }

    public function RequestSync()
    {
        $data = json_encode([
            'agentUserId' => md5(IPS_GetLicensee())
        ]);

        $result = @file_get_contents('https://homegraph.googleapis.com/v1/devices:requestSync?key=' . $this->apiKey, false, stream_context_create([
            'http' => [
                'method'           => 'POST',
                'header'           => "Content-type: application/json\r\nConnection: close\r\nContent-length: " . strlen($data) . "\r\n",
                'content'          => $data,
                'ignore_errors'    => true
            ],
        ]));

        if ($result === false) {
            echo "Failed: \n" . print_r(error_get_last(), true);
        } elseif (json_decode($result, true) !== []) {
            $this->SendDebug('Request Sync Failed', $result, 0);
            $decode = json_decode($result, true);
            if (isset($decode['error']['message'])) {
                echo "Failed: \n" . $decode['error']['message'];
            } else {
                echo 'Failed!';
            }
        } else {
            echo 'OK!';
        }
    }
}
