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

        //Register Profiles
        if (!IPS_VariableProfileExists('TAHOMA.OpenClosedState')) {
            IPS_CreateVariableProfile('TAHOMA.OpenClosedState', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('TAHOMA.OpenClosedState', 'open', $this->Translate('Offen'), 'Window-0', -1);
            IPS_SetVariableProfileAssociation('TAHOMA.OpenClosedState', 'stop', $this->Translate('Stop'), '', -1);
            IPS_SetVariableProfileAssociation('TAHOMA.OpenClosedState', 'closed', $this->Translate('Geschlossen'), 'Window-100', -1);
        }
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        // Only receive packets for our Device
        $this->SetReceiveDataFilter(sprintf('.*%s.*', str_replace('/', '\\\\\\/', $this->ReadPropertyString('DeviceURL'))));

        // Update device on first creation
        if ($this->HasActiveParent() && $this->ReadPropertyString('DeviceURL') && empty(IPS_GetChildrenIDs($this->InstanceID))) {
            $this->RequestStatus();
        }
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);

        $this->SendDebug('EVENT', json_encode($data->Event), 0);

        if (isset($data->Event->deviceStates)) {
            foreach ($data->Event->deviceStates as $state) {
                $this->processState($state, $data->Event->deviceStates);
            }
        }
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
            $this->processState($state, $result->states);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'core_TargetClosureState':
            case 'core_ClosureState':
                $this->SendCommand('setPosition', [$Value]);
                break;
            case 'core_SlateOrientationState':
                $this->SendCommand('setOrientation', [$Value]);
                break;
            case 'core_OpenClosedState':
                switch ($Value) {
                    case 'open':
                        $this->SendCommand('open', []);
                        break;
                    case 'stop':
                        $this->SendCommand('stop', []);
                        break;
                    case 'closed':
                        $this->SendCommand('close', []);
                        break;
                }
                break;
            default:
                throw new Exception('Invalid Ident');
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

    private function processState($state, $states)
    {
        if (!$this->filterState($state->name, $states)) {
            switch ($state->type) {
                case 1: // Integer
                    $this->RegisterVariableInteger($this->sanitizeName($state->name), $this->beautifyName($state->name), $this->getProfile($state->name), $this->getPosition($state->name));
                    $this->SetValue($this->sanitizeName($state->name), $state->value);
                    $this->registerAction($state->name);
                    break;
                case 3: // String
                    $this->RegisterVariableString($this->sanitizeName($state->name), $this->beautifyName($state->name), $this->getProfile($state->name), $this->getPosition($state->name));
                    $this->SetValue($this->sanitizeName($state->name), $state->value);
                    $this->registerAction($state->name);
                    break;
                case 6: // Boolean
                    $this->RegisterVariableBoolean($this->sanitizeName($state->name), $this->beautifyName($state->name), $this->getProfile($state->name), $this->getPosition($state->name));
                    $this->SetValue($this->sanitizeName($state->name), $state->value);
                    $this->registerAction($state->name);
                    break;
                case 11: // Object
                    $this->SendDebug('UNSUPPORTED', $state->name . ': ' . json_encode($state->value), 0);
                    break;
            }
        }
    }

    private function beautifyName($name)
    {
        switch ($name) {
            case 'core:OpenClosedState':
                return $this->Translate('Status');
            case 'core:TargetClosureState':
            case 'core:ClosureState':
                return $this->Translate('Position');
            case 'core:SlateOrientationState':
                return $this->Translate('Slate');
            case 'core:DiscreteRSSILevelState':
                return $this->Translate('Connection');
            case 'core:MovingState':
                return $this->Translate('Moving');
            default:
                $name = str_replace('core:', '', $name);
                $name = str_replace('internal:', '', $name);
                $name = str_replace('State', '', $name);
                return $name;
        }
    }

    private function sanitizeName($name)
    {
        return str_replace(':', '_', $name);
    }

    private function getProfile($name)
    {
        switch ($name) {
            case 'core:TargetClosureState':
            case 'core:ClosureState':
            case 'core:SlateOrientationState':
                return '~Intensity.100';
            case 'core:OpenClosedState':
                return 'TAHOMA.OpenClosedState';
            default:
                return '';
        }
    }

    private function getPosition($name)
    {
        switch ($name) {
            case 'core:OpenClosedState':
                return 1;
            case 'core:TargetClosureState':
            case 'core:ClosureState':
                return 2;
            case 'core:SlateOrientationState':
                return 3;
            case 'core:MovingState':
                return 4;
            case 'core:DiscreteRSSILevelState':
                return 5;
            default:
                return 6;
        }
    }

    private function filterState($name, $states)
    {
        switch ($name) {
            // We have the name on the instance itself. Do not waste variables
            case 'core:NameState':
            // Just keep the DiscreteRSSILevelState
            case 'core:RSSILevelState':
            case 'core:StatusState':
            // We do not need the memorized positions/orientations for now
            case 'core:Memorized1PositionState':
            case 'core:Memorized1OrientationState':
            // We do not need the configured secured position
            case 'core:SecuredPositionState':
                return true;
            // We prefer the core:TargetClosureState which will immediately show the new target
            // But for VELUX, we might not get a TargetClosureState. Use this as a fallback
            case 'core:ClosureState':
                foreach ($states as $state) {
                    if ($state->name == 'core:TargetClosureState') {
                        return true;
                    }
                }
                return false;
            // By default, we want to create the variable
            default:
                return false;
        }
    }

    private function registerAction($name)
    {
        switch ($name) {
            case 'core:TargetClosureState':
            case 'core:ClosureState':
            case 'core:SlateOrientationState':
            case 'core:OpenClosedState':
                $this->EnableAction($this->sanitizeName($name));
                break;
        }
    }
}
