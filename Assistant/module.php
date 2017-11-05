<?php

declare(strict_types=1);

include_once __DIR__ . "/oauth.php";

class Assistant extends IPSModule
{

    use WebOAuth;

    public function Create()
    {

        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString("Devices", "[]");

    }

    public function ApplyChanges()
    {

        //Never delete this line!
        parent::ApplyChanges();

        $this->RegisterOAuth("google_smarthome");

    }

    private function ProcessSync(): array {

        $devices = [];

        foreach(json_decode($this->ReadPropertyString("Devices"), true) as $device) {

            $devices[] = [
                "id" => strval($device['ID']),
                "type" => "action.devices.types.LIGHT",
                "traits" => [
                    "action.devices.traits.OnOff"
                ],
                "name" => [
                    "name" => $device['Name']
                ],
                "willReportState" => false
            ];

        }

        return [
            "devices" => $devices
        ];

    }

    private function ProcessQuery($payload): array {

        if(!isset($payload['devices']))
            throw new Exception("devices is undefined");

        if(!is_array($payload['devices']))
            throw new Exception("devices is malformed");

        $devices = Array();
        foreach($payload['devices'] as $device) {

            if(!isset($device['id']))
                throw new Exception("id is undefined");

            if(IPS_VariableExists($device['id'])) {
                $devices[$device['id']] = Array(
                    "on" => GetValue($device['id']),
                    "online" => true
                );
            } else {
                $devices[$device['id']] = Array(
                    "on" => false,
                    "online" => false
                );
            }
        }

        return [
            "devices" => $devices
        ];

    }

    private function ProcessExecute($payload): array {

        if(!isset($payload['commands']))
            throw new Exception("commands is undefined");

        if(!is_array($payload['commands']))
            throw new Exception("commands is malformed");

        $results = [];

        foreach($payload['commands'] as $command) {

            if(!isset($command['devices']))
                throw new Exception("devices is undefined");

            if(!is_array($command['devices']))
                throw new Exception("devices is malformed");

            if(!isset($command['execution']))
                throw new Exception("execution is undefined");

            if(!is_array($command['execution']))
                throw new Exception("execution is malformed");

            //Execute each executions command for each device
            foreach($command['execution'] as $execute) {
                foreach($command['devices'] as $device) {
                    $results[] = $this->ExecuteDevice($device["id"], $execute["command"], $execute["params"]);
                }
            }

        }

        //Merge results into Google's result format
        $commands = [];

        foreach($results as $result) {
            $found = false;
            foreach($commands as $command) {
                //lets assume for now there can only be one result per state
                if($command["state"] == $result["state"]) {
                    $commands["ids"][] = $result["id"];
                    $found = true;
                }
            }
            if(!$found) {
                $command = $result;
                $command["ids"] = [$command["id"]];
                unset($command["id"]);
                $commands[] = $command;
            }
        }

        return [
            "commands" => $commands
        ];

    }

    //See: https://developers.google.com/actions/smarthome/create-app
    private function ProcessRequest($request): array {

        if(!isset($request['requestId']))
            throw new Exception("requestId is undefined");

        if(!isset($request['inputs']))
            throw new Exception("inputs is undefined");

        if(!is_array($request['inputs']) || sizeof($request['inputs']) != 1)
            throw new Exception("inputs is malformed");

        //Google has defined an array but ony sends one value!
        $input = $request['inputs'][0];

        switch($input['intent']) {
            case "action.devices.SYNC":
                $payload = $this->ProcessSync();
                break;
            case "action.devices.QUERY":
                $payload = $this->ProcessQuery($input['payload']);
                break;
            case "action.devices.EXECUTE":
                $payload = $this->ProcessExecute($input['payload']);
                break;
            default:
                throw new Exception("Invalid intent");
        }

        return [
            "requestId" => $request['requestId'],
            "payload" => $payload
        ];

    }

    protected function ProcessOAuthData()
    {

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $this->SendDebug("Request", print_r($data, true), 0);

        //Redirect errors to our variable to push them into Debug
        ob_start();
        $result = $this->ProcessRequest($data);
        $error = ob_get_contents();
        if ($error != "") {
            $this->SendDebug("Error", $error, 0);
        }
        ob_end_clean();

        $this->SendDebug("Response", print_r($result, true), 0);

        //Use workaround for easier result evaluation
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($result);

    }

    public function GetConfigurationForm() {

        $data = json_decode(file_get_contents(__DIR__ . "/form.json"),true);

        //Check Connect availability
        $ids = IPS_GetInstanceListByModuleID("{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}");
        if(IPS_GetInstance($ids[0])['InstanceStatus'] != 102) {
            $message = "Error: Symcon Connect is not active!";
        } else {
            $message = "Status: Symcon Connect is OK!";
        }
        $data['elements'][0] = Array("type" => "Label", "label" => $message);

        //Build device list
        $devices = json_decode($this->ReadPropertyString("Devices"),true);
        foreach($devices as $device) {

            //We only need to add annotations. Remaining data is merged from persistance automatically.
            //Order is determined by the order of array elements
            if(IPS_VariableExists($device['ID'])) {
                $state = IPS_GetVariable($device['ID'])['VariableType'] == 0 ? 'OK' : 'Invalid';
                $data['elements'][1]['values'][] = Array(
                    "Device" => IPS_GetLocation($device['ID']),
                    "State" => $state,
                    "rowColor" => $state ? "" : "#ff0000"
                );
            } else {
                $data['elements'][1]['values'][] = Array(
                    "Device" => "N/A",
                    "State" => "Not found!",
                    "rowColor" => "#ff0000"
                );
            }

        }

        return json_encode($data);

    }

    private function ExecuteDevice($id, $command, $params): array {

        switch($command) {
            case "action.devices.commands.OnOff":
                $this->SendDebug("OnOff", $id . " -> " . ($params["on"] ? "On" : "Off"), 0);
                break;
            default:
                throw new Exception(sprintf("Unsupported command: %s", $command));
        }

        return [
            "id" => $id,
            "status" => "SUCCESS",
            "states" => [
                "on" => $params["on"],
                "online" => true
            ]
        ];

    }

}