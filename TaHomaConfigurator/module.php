<?php

declare(strict_types=1);
class TaHomaConfigurator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Connect to available splitter or create a new one
        $this->ConnectParent('{161B0F84-1B8B-2EF0-1C8F-2EFFAC39006E}');
    }

    public function GetConfigurationForm()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/form.json'));

        if ($this->HasActiveParent()) {
            $devices = json_decode($this->SendDataToParent(json_encode([
                'DataID'   => '{656566E9-4C78-6C4C-2F16-63CDD4412E9E}',
                'Endpoint' => '/setup/devices',
                'Payload'  => ''
            ])));

            foreach ($devices as $device) {
                $this->SendDebug('Device', json_encode($device), 0);

                $getAttribute = function ($device, $name)
                {
                    foreach ($device->attributes as $attribute) {
                        if ($attribute->name == $name) {
                            return $attribute->value;
                        }
                    }
                    return '';
                };

                $data->actions[0]->values[] = [
                    'address'      => $device->deviceURL,
                    'name'         => $device->label,
                    'type'         => $device->type,
                    'manufacturer' => $getAttribute($device, 'core:Manufacturer'),
                    'firmware'     => $getAttribute($device, 'core:FirmwareRevision'),
                    'instanceID'   => $this->searchDevice($device->deviceURL),
                    'create'       => [
                        'moduleID'      => '{C3F89070-FE4D-A30A-C81F-B28131B32990}',
                        'configuration' => [
                            'DeviceURL' => $device->deviceURL
                        ]
                    ]
                ];
            }
        }

        return json_encode($data);
    }

    private function searchDevice($deviceURL)
    {
        $ids = IPS_GetInstanceListByModuleID('{C3F89070-FE4D-A30A-C81F-B28131B32990}');
        foreach ($ids as $id) {
            if (IPS_GetProperty($id, 'DeviceURL') == $deviceURL) {
                return $id;
            }
        }
        return 0;
    }
}
