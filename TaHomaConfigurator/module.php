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

            $physicalChildren = $this->getPhysicalChildren();

            foreach ($devices as $device) {
                $this->SendDebug('Device', json_encode($device), 0);

                // This type does not have any variables
                if ($device->type == 5 /* PROTOCOL_GATEWAY */) {
                    continue;
                }

                $getAttribute = function ($device, $name)
                {
                    foreach ($device->attributes as $attribute) {
                        if ($attribute->name == $name) {
                            return $attribute->value;
                        }
                    }
                    return '';
                };

                $instanceID = $this->searchDevice($device->deviceURL);
                $physicalChildren = array_diff($physicalChildren, [$instanceID]);

                $data->actions[0]->values[] = [
                    'address'      => $device->deviceURL,
                    'name'         => $device->label,
                    'type'         => $device->definition->type,
                    'manufacturer' => $getAttribute($device, 'core:Manufacturer'),
                    'firmware'     => $getAttribute($device, 'core:FirmwareRevision'),
                    'instanceID'   => $instanceID,
                    'create'       => [
                        'moduleID'      => '{C3F89070-FE4D-A30A-C81F-B28131B32990}',
                        'configuration' => [
                            'DeviceURL' => $device->deviceURL
                        ]
                    ]
                ];
            }

            foreach ($physicalChildren as $instanceID) {
                $data->actions[0]->values[] = [
                    'address'      => IPS_GetProperty($instanceID, 'DeviceURL'),
                    'name'         => IPS_GetName($instanceID),
                    'type'         => '',
                    'manufacturer' => '',
                    'firmware'     => '',
                    'instanceID'   => $instanceID,
                ];
            }
        }

        return json_encode($data);
    }

    private function getPhysicalChildren()
    {
        $connectionID = IPS_GetInstance($this->InstanceID);
        $ids = IPS_GetInstanceListByModuleID('{C3F89070-FE4D-A30A-C81F-B28131B32990}');
        $result = [];
        foreach ($ids as $id) {
            $i = IPS_GetInstance($id);
            if ($i['ConnectionID'] == $connectionID['ConnectionID']) {
                $result[] = $id;
            }
        }
        return $result;
    }
    private function searchDevice($deviceURL)
    {
        $connectionID = IPS_GetInstance($this->InstanceID);
        $ids = IPS_GetInstanceListByModuleID('{C3F89070-FE4D-A30A-C81F-B28131B32990}');
        foreach ($ids as $id) {
            $i = IPS_GetInstance($id);
            if ($i['ConnectionID'] == $connectionID['ConnectionID']) {
                if (IPS_GetProperty($id, 'DeviceURL') == $deviceURL) {
                    return $id;
                }
            }
        }
        return 0;
    }
}
