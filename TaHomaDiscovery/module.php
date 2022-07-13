<?php

declare(strict_types=1);
class TaHomaDiscovery extends IPSModule
{
    public function GetConfigurationForm()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/form.json'));

        foreach ($this->discoverDevices() as $device) {
            $data->actions[0]->values[] = [
                'address'    => $device['IPv4'],
                'name'       => $device['Host'],
                'pin'        => $device['PIN'],
                'instanceID' => $this->searchDevice($device['PIN']),
                'create'     => [
                    [
                        'moduleID'      => '{AFAC39AA-770E-2AD7-0C24-D813F5FDC0FB}',
                    ],
                    [
                        'moduleID'      => '{161B0F84-1B8B-2EF0-1C8F-2EFFAC39006E}',
                        'configuration' => [
                            'Host'       => $device['Host'],
                            'GatewayPIN' => $device['PIN'],
                        ],
                    ],
                ]
            ];
        }

        return json_encode($data);
    }
    private function discoverDevices()
    {
        $ids = IPS_GetInstanceListByModuleID('{780B2D48-916C-4D59-AD35-5A429B2355A5}');
        $devices = ZC_QueryServiceType($ids[0], '_kizboxdev._tcp', '');
        $this->SendDebug('QueryServiceType', print_r($devices, true), 0);
        $result = [];

        $getValue = function ($txtRecords, $name)
        {
            foreach ($txtRecords as $txtRecord) {
                $parts = explode('=', $txtRecord);
                if ($parts[0] == $name) {
                    return $parts[1];
                }
            }
            return '';
        };

        foreach ($devices as $device) {
            $deviceInfo = ZC_QueryService($ids[0], $device['Name'], '_kizboxdev._tcp', 'local.');
            if ($deviceInfo) {
                $this->SendDebug('QueryService', print_r($deviceInfo, true), 0);
                $result[] = [
                    'Host' => $deviceInfo[0]['Host'],
                    'IPv4' => $deviceInfo[0]['IPv4'][0],
                    'PIN'  => $getValue($deviceInfo[0]['TXTRecords'], 'gateway_pin'),
                ];
            }
        }
        return $result;
    }

    private function searchDevice($pin)
    {
        $ids = IPS_GetInstanceListByModuleID('{161B0F84-1B8B-2EF0-1C8F-2EFFAC39006E}');
        foreach ($ids as $id) {
            if (IPS_GetProperty($id, 'GatewayPIN') == $pin) {
                return $id;
            }
        }
        return 0;
    }
}
