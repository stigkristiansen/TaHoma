<?php

declare(strict_types=1);
class TaHomaDevice extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Connect to available splitter or create a new one
        $this->ConnectParent('{6F83CEDB-BC40-63BB-C209-88D6B252C9FF}');

        $this->RegisterPropertyString('SiteID', '');
        $this->RegisterPropertyString('DeviceID', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function RequestStatus()
    {
        $result = json_decode($this->SendDataToParent(json_encode([
            'DataID'   => '{656566E9-4C78-6C4C-2F16-63CDD4412E9E}',
            'Endpoint' => '/v1/device/' . $this->ReadPropertyString('DeviceID'),
            'Payload'  => ''
        ])));

        var_dump($result);
    }

    public function SendCommand($name)
    {
        $result = json_decode($this->SendDataToParent(json_encode([
            'DataID'   => '{656566E9-4C78-6C4C-2F16-63CDD4412E9E}',
            'Endpoint' => '/v1/device/' . $this->ReadPropertyString('DeviceID') . '/exec',
            'Payload'  => json_encode([
                'name'       => $name,
                'parameters' => []
            ])
        ])));

        var_dump($result);
    }
}
