<?
declare(strict_types=1);

// Modul für Amazon Echo Remote

class ShellyIO extends IPSModule
{

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.

		//Always create our own MultiCast I/O, when no parent is already available
		// todo multicast
		//$this->RequireParent("{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}");
        //the following Properties can be set in the configuration form
		$this->RegisterPropertyInteger("UpdateInterval", 0);
		$this->RegisterTimer('UpdateInterval', 0, 'ShellySplitter_UpdateStatus(' . $this->InstanceID . ');');


    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() != KR_READY) {
			return;
		}

        $this->ValidateConfiguration();

    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt
     * wurden. Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung
     * gestellt:
     *
     *
     */

    private function ValidateConfiguration()
    {
		$this->SetUpdateInterval();
    }


	private function SetUpdateInterval()
	{
		$echointerval = $this->ReadPropertyInteger("UpdateInterval");
		$interval = $echointerval * 1000;
		$this->SetTimerInterval("UpdateInterval", $interval);
	}

	public function UpdateStatus()
	{

	}

	/**
	 * Interne Funktion des SDK.
	 * Wird von der Console aufgerufen, wenn 'unser' IO-Parent geöffnet wird.
	 * Außerdem nutzen wir sie in Applychanges, da wir dort die Daten zum konfigurieren nutzen.
	 * @access public
	 */
	/*
	public function GetConfigurationForParent()
	{
		$Config['Port'] = 1883; // MQTT Port
		$Config['Host'] = "239.255.255.250"; //SSDP Multicast-IP
		$Config['MulticastIP'] = "239.255.255.250"; //SSDP Multicast-IP
		$Config['BindPort'] = 1900; //SSDP Multicast Empfangs-Port
		//$Config['BindIP'] muss der User auswählen und setzen wenn mehrere Netzwerkadressen auf dem IP-System vorhanden sind.
		$Config['EnableBroadcast'] = true;
		$Config['EnableReuseAddress'] = true;
		$Config['EnableLoopback'] = true;
		return json_encode($Config);
	}
	*/


    /**  Send to Echo API
     *
     * @param string    $url
     *
     * @param array     $header
     * @param string    $postfields as json string
     *
     * @param bool|null $optpost
     *
     * @return mixed
     */
    private function SendEcho(string $url, array $header, string $postfields = null, bool $optpost = null)
    {
        $this->SendDebug(__FUNCTION__, "Header: " . json_encode($header), 0);

        $ch = curl_init();

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_TIMEOUT        => 20, //timeout after 20 seconds
            CURLOPT_HEADER         => true,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1];

        if ($this->ReadPropertyBoolean('UseCustomCSRFandCookie')) {
            $options[CURLOPT_COOKIE] = $this->ReadPropertyString("alexa_cookie"); //this content is read
        } else {
            $options [CURLOPT_COOKIEFILE] = $this->ReadPropertyString("CookiesFileName"); //this file is read
        }

        if (isset($postfields)) {
            $this->SendDebug(__FUNCTION__, "Postfields: " . $postfields, 0);
            $options [CURLOPT_POSTFIELDS] = $postfields;
        }

        if (isset($optpost)) {
            $options[CURLOPT_POST] = $optpost;
        }

        $this->SendDebug(__FUNCTION__, "Options: " . json_encode($options), 0);
        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->LogMessage('Error: (' . curl_errno($ch) . ') ' . curl_error($ch), KL_ERROR);
            return false;
        }

        $info      = curl_getinfo($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->SendDebug(__FUNCTION__, "Send to URL: " . print_r($url, true), 0);
        $this->SendDebug(__FUNCTION__, "Curl Info: " . $http_code . ' ' . print_r($info, true), 0);
        curl_close($ch);
        //eine Fehlerbehandlung macht hier leider keinen Sinn, da 400 auch kommt, wenn z.b. der Bildschirm (Show) ausgeschaltet ist
        if (in_array(
            $http_code, [// 400  //bad request
                         //, 500 //internal server error
                      ]
        )) {
            trigger_error('Unexpected HTTP Code: ' . $http_code);
        }

        return $this->getReturnValues($info, $result);
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, 'Incoming: ' . $JSONString, 0);
        // Empfangene Daten von der Device Instanz
        $data = json_decode($JSONString)->Buffer;

        if (!property_exists($data, 'method')) {
            trigger_error('Property \'method\' is missing');
            return false;
        }

        $this->SendDebug(__FUNCTION__, '== started == (Method \'' . $data->method . '\')', 0);
        //$this->SendDebug(__FUNCTION__, 'Method: ' . $data->method, 0);

        $buffer = json_decode($JSONString, true)['Buffer'];

        switch ($data->method) {
            case 'NpCommand':
                $getfields  = $buffer['getfields'];
                $postfields = $buffer['postfields'];
				$result = "";
                //$result = $this->NpCommand($getfields, $postfields);
                break;



            default:
                trigger_error('Method \'' . $data->method . '\' not yet supported');
                return false;
        }

        return json_encode($result);

    }


    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * build configuration form
     *
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
        return json_encode(
            [
                'elements' => $this->FormHead(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus()]
        );
    }

    /**
     * return form configurations on configuration step
     *
     * @return array
     */
    private function FormHead()
    {
        $form = [


        ];
        return $form;
    }

    /**
     * return form actions by token
     *
     * @return array
     */
    private function FormActions()
    {
        $form = [
            ];


        return $form;
    }


    /**
     * return from status
     *
     * @return array
     */
    private function FormStatus()
    {
        $form = [
            [
                'code'    => 210,
                'icon'    => 'error',
                'caption' => 'user name must not be empty.'],
            [
                'code'    => 211,
                'icon'    => 'error',
                'caption' => 'password must not be empty.'],
            [
                'code'    => 212,
                'icon'    => 'error',
                'caption' => 'not authenticated.']];

        return $form;
    }

}