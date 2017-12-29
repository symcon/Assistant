<?php

declare(strict_types=1);

trait WebOAuth
{
    private function RegisterOAuth($WebOAuth): void
    {
        $ids = IPS_GetInstanceListByModuleID('{F99BF07D-CECA-438B-A497-E4B55F139D37}');
        if (count($ids) > 0 && (IPS_GetInstance($ids[0])['InstanceStatus'] == 102 /* IS_ACTIVE*/)) {
            $clientIDs = json_decode(IPS_GetProperty($ids[0], 'ClientIDs'), true);

            //Search or Update WebHook client to our instanceID
            $found = false;
            foreach ($clientIDs as $index => $clientID) {
                if ($clientID['ClientID'] == $WebOAuth) {
                    if ($clientID['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $clientIDs[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }

            //If no found add a new client for our instanceID
            if (!$found) {
                $clientIDs[] = [
                    'ClientID' => $WebOAuth,
                    'TargetID' => $this->InstanceID
                ];
            }

            IPS_SetProperty($ids[0], 'ClientIDs', json_encode($clientIDs));
            IPS_ApplyChanges($ids[0]);
        }
    }
}
