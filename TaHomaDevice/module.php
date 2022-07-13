<?php

declare(strict_types=1);
class TaHomaDevice extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Connect to available splitter or create a new one
        $this->ConnectParent('{161B0F84-1B8B-2EF0-1C8F-2EFFAC39006E}');

        $this->RegisterPropertyString('DeviceURL', '');
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
            'Endpoint' => '/setup/devices/' . urlencode($this->ReadPropertyString('DeviceURL')),
            'Payload'  => ''
        ])));

        $this->SendDebug('DATA', json_encode($result), 0);

        foreach ($result->states as $state) {
            switch ($state->type) {
                case 1: // Integer
                    $this->RegisterVariableInteger($this->sanitizeName($state->name), $this->beautifyName($state->name));
                    $this->SetValue($this->sanitizeName($state->name), $state->value);
                    break;
                case 3: // String
                    $this->RegisterVariableString($this->sanitizeName($state->name), $this->beautifyName($state->name));
                    $this->SetValue($this->sanitizeName($state->name), $state->value);
                    break;
                case 6: // Boolean
                    $this->RegisterVariableBoolean($this->sanitizeName($state->name), $this->beautifyName($state->name));
                    $this->SetValue($this->sanitizeName($state->name), $state->value);
                    break;
                case 11: // Object
                    $this->SendDebug('UNSUPPORTED', $state->name . ': ' . json_encode($state->value), 0);
                    break;
            }
        }
    }

    public function SendCommand(string $name, array $parameters)
    {
        $result = json_decode($this->SendDataToParent(json_encode([
            'DataID'   => '{656566E9-4C78-6C4C-2F16-63CDD4412E9E}',
            'Endpoint' => '/exec/apply',
            'Payload'  => json_encode([
                'actions' => [
                    [
                        'label'     => $name,
                        'deviceURL' => $this->ReadPropertyString('DeviceURL'),
                        'commands'  => [
                            [
                                'name'       => $name,
                                'parameters' => $parameters
                            ],
                        ],
                    ],
                ],
            ])
        ])));

        if (!isset($result->execId)) {
            var_dump($result);
        }
    }

    private function beautifyName($name)
    {
        return str_replace('core:', '', $name);
    }

    private function sanitizeName($name)
    {
        return str_replace(':', '_', $name);
    }
}
