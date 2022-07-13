<?php

declare(strict_types=1);
class TaHomaCloud extends IPSModule
{
    private static $endpoint = 'https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyString('GatewayPIN', '');

        $this->RegisterAttributeString('Token', '');

        $this->RegisterTimer('Fetch', 0, "TAHOMA_Fetch(\$_IPS['TARGET']);");
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if ($this->ReadAttributeString('Token') == '') {
            $this->SetStatus(IS_INACTIVE);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }
    }

    public function ForwardData($data)
    {
        $data = json_decode($data);
        if (strlen($data->Payload) > 0) {
            $this->SendDebug('ForwardData', $data->Endpoint . ', Payload: ' . $data->Payload, 0);
            return $this->PostData($this->MakeLocalEndpoint($data->Endpoint), $data->Payload);
        } else {
            $this->SendDebug('ForwardData', $data->Endpoint, 0);
            return $this->GetData($this->MakeLocalEndpoint($data->Endpoint));
        }
    }

    public function GetConfigurationForm()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/form.json'));

        if ($this->ReadAttributeString('Token') == '') {
            $data->actions[0]->label = $this->Translate('TaHoma: Please acquire a Token using your Somfy account!');
        } else {
            $data->actions[0]->label = sprintf('Token: ' . substr($this->ReadAttributeString('Token'), 0, 16));
        }

        return json_encode($data);
    }

    public function Login(string $Username, string $Password)
    {
        if (!$this->ReadPropertyString('GatewayPIN')) {
            echo $this->Translate('Please configure your Gateway PIN first!');
            return;
        }

        // Docs: https://github.com/Somfy-Developer/Somfy-TaHoma-Developer-Mode

        // Login
        $content = http_build_query([
            'userId'       => $Username,
            'userPassword' => $Password
        ]);

        $opts = [
            'http'=> [
                'method'        => 'POST',
                'header'        => 'Content-Type: application/x-www-form-urlencoded' . "\r\n" . 'Content-Length: ' . strlen($content) . "\r\n",
                'content'       => $content,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);

        $result = file_get_contents(self::$endpoint . '/login', false, $context);

        $json = json_decode($result);
        if (!$json) {
            echo $result;
            return;
        }

        if (isset($json->error)) {
            echo $json->error;
            return;
        }

        // Extract cookie, Credit: https://stackoverflow.com/a/10958820/10288655
        $cookies = [];
        foreach ($http_response_header as $hdr) {
            if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
                parse_str($matches[1], $tmp);
                $cookies += $tmp;
            }
        }

        $cookie = $cookies['JSESSIONID'];

        // Generate Token
        $opts = [
            'http'=> [
                'method'        => 'GET',
                'header'        => 'Cookie: JSESSIONID=' . $cookie . "\r\n" . 'Content-Type: application/json' . "\r\n",
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);

        $result = file_get_contents(self::$endpoint . '/config/' . $this->ReadPropertyString('GatewayPIN') . '/local/tokens/generate', false, $context);

        $json = json_decode($result);
        if (!$json) {
            echo $result;
            return;
        }

        if (isset($json->error)) {
            echo $json->error;
            return;
        }

        $token = $json->token;

        // Activate Token
        $content = json_encode([
            'label' => 'IP-Symcon',
            'token' => $token,
            'scope' => 'devmode',
        ]);

        $opts = [
            'http'=> [
                'method'        => 'POST',
                'header'        => 'Cookie: JSESSIONID=' . $cookie . "\r\n" . 'Content-Type: application/json' . "\r\n" . 'Content-Length: ' . strlen($content) . "\r\n",
                'content'       => $content,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);

        $result = file_get_contents(self::$endpoint . '/config/' . $this->ReadPropertyString('GatewayPIN') . '/local/tokens', false, $context);

        $json = json_decode($result);
        if (!$json) {
            echo $result;
            return;
        }

        if (isset($json->error)) {
            echo $json->error;
            return;
        }

        $this->WriteAttributeString('Token', $token);
        $this->SetStatus(IS_ACTIVE);

        // Reload form to show token
        $this->ReloadForm();
    }

    public function Register()
    {
        $this->SendDebug('Register', '', 0);
        $result = json_decode($this->PostData($this->MakeLocalEndpoint('/events/register'), ''));

        $this->SetBuffer('ListenerID', $result->id);

        $this->SetTimerInterval('Fetch', 5000);
    }

    public function Fetch()
    {
        $this->SendDebug('Fetch', 'ListenerID: ' . $this->GetBuffer('ListenerID'), 0);
        $result = json_decode($this->PostData($this->MakeLocalEndpoint(sprintf('/events/%s/fetch', $this->GetBuffer('ListenerID'))), ''));
    }

    private function MakeLocalEndpoint($endpoint)
    {
        return sprintf('https://%s:8443/enduser-mobile-web/1/enduserAPI%s', $this->ReadPropertyString('Host'), $endpoint);
    }

    private function GetData($url)
    {
        $opts = [
            'http'=> [
                'method'        => 'GET',
                'header'        => 'Authorization: Bearer ' . $this->ReadAttributeString('Token') . "\r\n" . 'Content-Type: application/json' . "\r\n",
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];
        $context = stream_context_create($opts);

        $result = file_get_contents($url, false, $context);

        $this->SendDebug('DATA', $result, 0);

        return $result;
    }

    private function PostData($url, $content)
    {
        $opts = [
            'http'=> [
                'method'        => 'POST',
                'header'        => 'Authorization: Bearer ' . $this->ReadAttributeString('Token') . "\r\n" . 'Content-Type: application/json' . "\r\n" . 'Content-Length: ' . strlen($content) . "\r\n",
                'content'       => $content,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];
        $context = stream_context_create($opts);

        $result = file_get_contents($url, false, $context);

        $this->SendDebug('DATA', $result, 0);

        return $result;
    }
}
