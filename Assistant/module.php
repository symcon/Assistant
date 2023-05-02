<?php

declare(strict_types=1);

include_once __DIR__ . '/oauth.php';
include_once __DIR__ . '/helper/autoload.php';
include_once __DIR__ . '/registry.php';
include_once __DIR__ . '/traits/autoload.php';
include_once __DIR__ . '/types/autoload.php';
include_once __DIR__ . '/simulate.php';

class Assistant extends IPSModule
{
    use WebOAuth;
    use Simulate, CommonConnectVoiceAssistant {
        Create as private BaseCreate;
        ApplyChanges as private BaseApplyChanges;
        GetConfigurationForm as private BaseGetConfigurationForm;
    }

    private $apiKey = 'AIzaSyAtQwhb65ITHYJZXd-x7ziBfKkNj5rTo1k';

    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID);

        $this->registry = new DeviceTypeRegistry(
            $this->InstanceID,
            function ($Name, $Value)
            {
                $this->RegisterPropertyString($Name, $Value);
            },
            function ($Message, $Data, $Format)
            {
                $this->SendDebug($Message, $Data, $Format);
            }
        );
    }

    public function Create()
    {
        $this->BaseCreate();

        $this->RegisterTimer('ReportStateTimer', 0, 'GA_ReportState($_IPS[\'TARGET\']);');

        $this->RegisterPropertyBoolean('EmulateStatus', false);

        $this->RegisterPropertyBoolean('EnableReportState', true);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->RegisterOAuth('google_smarthome');

        // Transform legacy scenes to new version with action (6.1)
        $wasUpdated = false;
        $simpleScenes = json_decode($this->ReadPropertyString('DeviceSceneSimple'), true);
        if (isset($simpleScenes[0]['SceneSimpleScriptID'])) {
            for ($i = 0; $i < count($simpleScenes); $i++) {
                $simpleScenes[$i]['SceneSimpleAction'] = json_encode([
                    'actionID'   => '{64087366-07B7-A3D6-F6BA-734BDA4C4FAB}',
                    'parameters' => [
                        'BOOLEANPARAMETERS' => json_encode([[
                            'name'  => 'VALUE',
                            'value' => true
                        ]]),
                        'NUMERICPARAMETERS' => json_encode([]),
                        'STRINGPARAMETERS'  => json_encode([[
                            'name'  => 'SENDER',
                            'value' => 'VoiceControl'
                        ]]),
                        'TARGET' => $simpleScenes[$i]['SceneSimpleScriptID']
                    ]
                ]);
                unset($simpleScenes[$i]['SceneSimpleScriptID']);
            }
            IPS_SetProperty($this->InstanceID, 'DeviceSceneSimple', json_encode($simpleScenes));
            $wasUpdated = true;
        }

        $deactivatableScenes = json_decode($this->ReadPropertyString('DeviceSceneDeactivatable'), true);
        if (isset($deactivatableScenes[0]['SceneDeactivatableActivateID'])) {
            for ($i = 0; $i < count($deactivatableScenes); $i++) {
                $deactivatableScenes[$i]['SceneDeactivatableActivateAction'] = json_encode([
                    'actionID'   => '{64087366-07B7-A3D6-F6BA-734BDA4C4FAB}',
                    'parameters' => [
                        'BOOLEANPARAMETERS' => json_encode([[
                            'name'  => 'VALUE',
                            'value' => true
                        ]]),
                        'NUMERICPARAMETERS' => json_encode([]),
                        'STRINGPARAMETERS'  => json_encode([[
                            'name'  => 'SENDER',
                            'value' => 'VoiceControl'
                        ]]),
                        'TARGET' => $deactivatableScenes[$i]['SceneDeactivatableActivateID']
                    ]
                ]);
                $deactivatableScenes[$i]['SceneDeactivatableDeactivateAction'] = json_encode([
                    'actionID'   => '{64087366-07B7-A3D6-F6BA-734BDA4C4FAB}',
                    'parameters' => [
                        'BOOLEANPARAMETERS' => json_encode([[
                            'name'  => 'VALUE',
                            'value' => false
                        ]]),
                        'NUMERICPARAMETERS' => json_encode([]),
                        'STRINGPARAMETERS'  => json_encode([[
                            'name'  => 'SENDER',
                            'value' => 'VoiceControl'
                        ]]),
                        'TARGET' => $deactivatableScenes[$i]['SceneDeactivatableDeactivateID']
                    ]
                ]);
                unset($deactivatableScenes[$i]['SceneDeactivatableActivateID']);
                unset($deactivatableScenes[$i]['SceneDeactivatableDeactivateID']);
            }
            IPS_SetProperty($this->InstanceID, 'DeviceSceneDeactivatable', json_encode($deactivatableScenes));
            $wasUpdated = true;
        }

        if ($wasUpdated) {
            IPS_ApplyChanges($this->InstanceID);
            return;
        }

        // Delay sync until KR_READY is reached or we will cause a deadlock
        // Sync on startup is relevant as we need to update the status
        if (IPS_GetKernelRunlevel() == KR_READY) {
            // RequestSync updates the status as well
            $this->RequestSync();
        }

        $objectIDs = $this->registry->getObjectIDs();

        foreach ($this->GetMessageList() as $variableID => $messages) {
            $this->UnregisterMessage($variableID, VM_UPDATE);
        }

        foreach ($objectIDs as $variableID) {
            if (IPS_VariableExists($variableID)) {
                $this->RegisterMessage($variableID, VM_UPDATE);
            }
        }

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);

        $this->BaseApplyChanges();
    }

    public function MessageSink($timestamp, $senderID, $messageID, $data)
    {
        switch ($messageID) {
            case VM_UPDATE:
                //Only transmit report state on changed values and if reporting is enabled
                if ($data[1] && $this->ReadPropertyBoolean('EnableReportState')) {
                    $variableUpdateSemaphore = IPS_SemaphoreEnter('VariableUpdateSemaphore', 500);
                    if ($variableUpdateSemaphore) {
                        $currentVariableUpdatesString = $this->GetBuffer('VariableUpdates');
                        $currentVariableUpdates = ($currentVariableUpdatesString == '') ? [] : json_decode($currentVariableUpdatesString, true);
                        $currentVariableUpdates[] = $senderID;
                        $this->SetBuffer('VariableUpdates', json_encode($currentVariableUpdates));
                        IPS_SemaphoreLeave('VariableUpdateSemaphore');
                        $this->SetTimerInterval('ReportStateTimer', 1000);
                    } else {
                        $this->LogMessage($this->Translate('Variable Update Semaphore is unavailable'), KL_ERROR);
                    }
                }
                break;

            case IPS_KERNELMESSAGE:
                if ($data[0] == KR_READY) {
                    $this->RequestSync();
                }
                break;
        }
    }

    public function ReportState()
    {
        $reportStateSemaphore = IPS_SemaphoreEnter('ReportStateSemaphore', 0);
        if ($reportStateSemaphore) {
            $variableUpdateSemaphore = IPS_SemaphoreEnter('VariableUpdateSemaphore', 50);
            if ($variableUpdateSemaphore) {
                $this->SetTimerInterval('ReportStateTimer', 0);
                $variableUpdates = $this->GetBuffer('VariableUpdates');
                if ($variableUpdates != '') {
                    $this->SetBuffer('VariableUpdates', '');
                    IPS_SemaphoreLeave('VariableUpdateSemaphore');
                    $this->registry->ReportState(json_decode($variableUpdates, true));
                } else {
                    IPS_SemaphoreLeave('VariableUpdateSemaphore');
                }
            }
            IPS_SemaphoreLeave('ReportStateSemaphore');
        }
    }

    public function GetConfigurationForm()
    {
        $configurationForm = json_decode($this->BaseGetConfigurationForm(), true);

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
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Request device update',
                            'onClick' => 'GA_RequestSync($id);'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'caption' => 'Transmit state changes to Google',
                            'name'    => 'EnableReportState'
                        ]
                    ]
                ]
            ]
        ];

        $configurationForm['status'][] = [
            'code'    => 201,
            'icon'    => 'error',
            'caption' => 'The connection to your Google Home Account was lost. Reconnect to Symcon by opening your Google Home app, clicking the Symcon service, and selecting "Search for devices"'
        ];

        $configurationForm['translations']['de']['Expert Options'] = 'Expertenoptionen';
        $configurationForm['translations']['de']['Please check the documentation before handling these settings. These settings do not need to be changed under regular circumstances.'] = 'Bitte prüfen Sie die Dokumentation bevor Sie diese Einstellungen anpassen. Diese Einstellungen müssen unter normalen Umständen nicht verändert werden.';
        $configurationForm['translations']['de']['Emulate Status'] = 'Status emulieren';
        $configurationForm['translations']['de']['Request device update'] = 'Geräteupdate anfragen';
        $configurationForm['translations']['de']['Transmit state changes to Google'] = 'Zustandsänderungen an Google übertragen';
        $configurationForm['translations']['de']['The connection to your Google Home Account was lost. Reconnect to Symcon by opening your Google Home app, clicking the Symcon service, and selecting "Search for devices"'] = 'Die Verbindung zu Ihrem Google Home Account wurde getrennt. Zum erneuten Verbinden, öffnen Sie die Google Home App, tippen auf den Symcon-Service und wählen Sie "Nach Geräten suchen"';
        $configurationForm['translations']['de']['Variable Update Semaphore is unavailable'] = 'Semaphore für Variablenaktualisierung ist nicht verfügbar';
        $configurationForm['translations']['de']['If you enjoy our Assistant integration, please rate our skill by clicking the icon.'] = 'Wenn Ihnen unsere Assistant-Integration gefällt, würden wir uns sehr über eine Bewertung freuen. Klicken Sie dafür bitte auf das Icon.';

        $configurationForm['elements'] = array_merge($configurationForm['elements'], $expertMode);

        $configurationForm['actions'] = [
            [
                'type'    => 'Label',
                'caption' => 'If you enjoy our Assistant integration, please rate our skill by clicking the icon.'
            ],
            [
                'type'    => 'Image',
                'image'   => 'data:image/gif;base64,R0lGODlhlgCWAPf/ANDY3gErT4iZq4GJl9Tb4QUuUgAZOgIoTZCapr3DygIrTwQsT4SNmYWWqHiMoIGUpkhkf+fr7gAoTI+ZpitNapWmtYSNmr7I0RY6W4aXqQAgQwAhRQAgRAcwUwAlSoKVqLnFzoGKlwAnTIOMmQgtUHWKnpOislx1jM/W3Ro+XhE2WKW0wJuruo+erh5CYwYuUQAfQwUuUWF6kDVUcFdyiiM+WQQqTgAiRw42V/v7/PLy8/Pz8/z8/Pf39/b29/v7+/Hx8gAjSPb29vX19fPz9Pj4+PT09fr6+v7+/v39/fHx8QQtUPn4+fn5+fDw8P///gAkSYyer//8+3OBkZWks5Kbqfz8+wcvUyE8V+3s7gAnS2+FmgQsUH+Tpu/v8AAmSgMrT5GhsWt6i3uPooCTpgYvUv///ZCgsP/+/YaYqfLy8f37+oKLmAAiSPLy8n2RpIGTpv/9+4WWp//9/H2Qo3yQo/39/vv6+/78+oSWpwUtUP/+/HyPo5iot3uOoZmpuPX19oKKl/r5+gUtUYeZqpmot4OLmfj493uPo/T09AMsT4aYqvP193mOofz9/fj394OLmAAjSQAmS5mouPX19Ojs752suvTz8/f3+Jinttrg5XqOof/8+vf39tzi5/b19vPz8vv4+P36+vL09oeYqvf29m17jAATOf79/Ehedvb19HeNoP39/PDz9QIpTfj3+P/9/Y+gsK+8x7C8yPn6+/n4+KCuvfj4+fPy84maq/r7+5GisZKjs4ybq4aOm2d1h4udrn+SpW9+kAciQhEvTQsqTvj5+uvu8XqPoZChsZGhsAAcQ/37+fv6+oWYqv/++4CJlvX09QAYPiNEYyZJaJujsAEnTFZviGt7jJSjsmx6i218jQARNqCpte3w8jlWcjpadpCbqfz6+/f4+f77+fDx84yToI2Voo2Xpv78/EFZc0BeeXqOonqPo4GTpay5xQkwUwwxUw8zVXGInImarE1oggAeRAAcQPz8/Wd8klFthdLZ4Obn6f///wcvUv///yH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNy4xLWMwMDAgNzkuYjBmOGJlOTAsIDIwMjEvMTIvMTUtMjE6MjU6MTUgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCAyMy4yIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo1RkQ4MzYyNTk1ODUxMUVDOEQ5Q0U4QzdBNjBFNThGRCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo1RkQ4MzYyNjk1ODUxMUVDOEQ5Q0U4QzdBNjBFNThGRCI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjVGRDgzNjIzOTU4NTExRUM4RDlDRThDN0E2MEU1OEZEIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjVGRDgzNjI0OTU4NTExRUM4RDlDRThDN0E2MEU1OEZEIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+Af/+/fz7+vn49/b19PPy8fDv7u3s6+rp6Ofm5eTj4uHg397d3Nva2djX1tXU09LR0M/OzczLysnIx8bFxMPCwcC/vr28u7q5uLe2tbSzsrGwr66trKuqqainpqWko6KhoJ+enZybmpmYl5aVlJOSkZCPjo2Mi4qJiIeGhYSDgoGAf359fHt6eXh3dnV0c3JxcG9ubWxramloZ2ZlZGNiYWBfXl1cW1pZWFdWVVRTUlFQT05NTEtKSUhHRkVEQ0JBQD8+PTw7Ojk4NzY1NDMyMTAvLi0sKyopKCcmJSQj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',
                'onClick' => 'echo "https://assistant.google.com/services/a/uid/000000ef1406f62c";',
                'width'   => '150px'
            ]
        ];

        return json_encode($configurationForm);
    }

    public function RequestSync()
    {
        //Check Connect availability
        $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}'); // Connect Control

        if ((count($ids) < 1) || (IPS_GetInstance($ids[0])['InstanceStatus'] != 102)) {
            $this->SetStatus(104);
            if (method_exists($this, 'ReloadForm')) {
                $this->ReloadForm();
            }
            return;
        } else {
            $this->SetStatus(102);
        }
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
            echo "Request Sync Failed: \n" . json_encode(error_get_last());
        } elseif (json_decode($result, true) !== []) {
            $this->SendDebug('Request Sync Failed', $result, 0);
            $decode = json_decode($result, true);
            if (isset($decode['error']['message'])) {
                switch ($decode['error']['message']) {
                    case 'Requested entity was not found.':
                        $this->SetStatus(104);
                        if (method_exists($this, 'ReloadForm')) {
                            $this->ReloadForm();
                        }
                        break;

                    case 'The caller does not have permission':
                        $this->SetStatus(201);
                        break;

                    default:
                        echo "Request Sync Failed: \n" . $decode['error']['message'];
                        break;
                }
            } else {
                echo 'Request Sync Failed!';
            }
        }
    }

    protected function ProcessData(array $data): array
    {
        $this->SendDebug('Request', json_encode($data), 0);

        // If we receive a message, then everything must be fine
        $this->SetStatus(102);

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

        $this->SendDebug('Response', json_encode($result), 0);

        return $result;
    }

    protected function ProcessOAuthData()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $this->ProcessData($data);
        echo json_encode($result);
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
                    $this->SendDebug('Execute - Params', json_encode($execute['params']), 0);
                    $results[] = $this->registry->doExecuteDevice($device['id'], $execute['command'], $execute['params']);
                }
            }
        }

        //Merge results into Google's result format
        $commands = [];

        $this->SendDebug('Results', json_encode($results), 0);
        foreach ($results as $result) {
            $found = false;
            foreach ($commands as &$command) {
                //lets assume for now there can only be one result per state
                if ($command['states'] == $result['states']) {
                    $command['ids'] = array_merge($command['ids'], $result['ids']);
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
}
