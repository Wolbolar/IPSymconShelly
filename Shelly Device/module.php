<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';
require_once __DIR__ . '/../libs/ConstHelper.php';


// Modul für Amazon Echo Remote

class Shelly extends IPSModule
{

	const STATUS_INST_DEVICETYPE_IS_EMPTY = 210; // devicetype must not be empty.
	const STATUS_INST_HOST_IS_EMPTY = 211; // host must not be empty.
	const STATUS_INST_HOST_WRONG = 212; // host is not valid

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.

		$this->RegisterPropertyString("Host", "");
		$this->RegisterPropertyInteger("Devicetype", 0);
		$this->RegisterPropertyInteger("UpdateInterval", 0);
		$this->RegisterPropertyBoolean("MQTT", false);
		$this->RegisterPropertyBoolean("PowerConsumption", false);
		$this->RegisterPropertyBoolean("ExtendedInformation", false);
		$this->RegisterPropertyBoolean("ShellyWebInterface", false);

		$this->RegisterTimer('UpdateInterval', 0, 'Shelly_UpdateStatus(' . $this->InstanceID . ');');
		$this->RegisterTimer('UpdateIntervalPowerConsumption', 0, 'Shelly_UpdatePowerConsumption(' . $this->InstanceID . ');');

		$this->ConnectParent("{33AD822D-F404-11C2-73DE-F45AC91C8125}");

		//we will wait until the kernel is ready
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);

	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() != KR_READY) {
			return;
		}

		if (!$this->ValidateConfiguration()) {
			return;
		}


		$this->RegisterVariables();

	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
	{

		switch ($Message) {
			case IM_CHANGESTATUS:
				if ($Data[0] == IS_ACTIVE) {
					$this->ApplyChanges();
				}
				break;

			case IPS_KERNELMESSAGE:
				if ($Data[0] == KR_READY) {
					$this->ApplyChanges();
				}
				break;

			default:
				break;
		}
	}

	private function ValidateConfiguration()
	{
		$host = $this->GetHost();
		$devicetype = $this->GetDevicetype();
		if ($host == "") {
			$this->SetStatus(self::STATUS_INST_HOST_IS_EMPTY);
		}
		//IP check
		if (!filter_var($host, FILTER_VALIDATE_IP) === false) {
			//IP ok
			$ipcheck = true;
		} else {
			$ipcheck = false;
			$this->SetStatus(self::STATUS_INST_HOST_WRONG);
		}

		if ($devicetype == 0) {
			$this->SetStatus(self::STATUS_INST_DEVICETYPE_IS_EMPTY);
		}
		if ($ipcheck) {
			$this->SetStatus(IS_ACTIVE);
			$this->SetUpdateInterval();

			return true;
		}

		return false;
	}

	private function RegisterVariables()
	{
		$devicetype = $this->GetDevicetype();
		$this->RegisterVariableBoolean("STATE1", $this->Translate("State"), "~Switch", 1);
		$this->EnableAction("STATE1");
		if ($devicetype == 2 || $devicetype == 3) {
			$this->SendDebug('Register Variables', 'Shelly Switch', 0);
			$this->RegisterVariableBoolean("STATE2", $this->Translate("State 2"), "~Switch", 3);
			$this->EnableAction("STATE2");
		}
		if ($devicetype == 3) {
			$this->SendDebug('Register Variables', 'Shelly 4 Pro', 0);
			$this->RegisterVariableBoolean("STATE3", $this->Translate("State 3"), "~Switch", 5);
			$this->EnableAction("STATE3");
			$this->RegisterVariableBoolean("STATE4", $this->Translate("State 4"), "~Switch", 7);
			$this->EnableAction("STATE4");
		}
		if ($devicetype == 4) {
			$this->SendDebug('Register Variables', 'Shelly Plug', 0);
		}
		if ($devicetype == 5) {
			$this->SendDebug('Register Variables', 'Shelly Bulb', 0);
		}
		if ($devicetype == 6) {
			$this->SendDebug('Register Variables', 'Shelly Sense', 0);
		}
		$power_comsumption = $this->ReadPropertyBoolean("PowerConsumption");
		$extended_information = $this->ReadPropertyBoolean("ExtendedInformation");
		if ($power_comsumption) {
			$this->SendDebug('Register Variables', 'power consumption', 0);
			if ($devicetype == 2 || $devicetype == 3) {
				$this->RegisterVariableFloat("POWER_CONSUMPTION", $this->Translate("Power Consumption"), "~Watt.3680", 2);
				$this->RegisterVariableFloat("POWER_CONSUMPTION2", $this->Translate("Power Consumption 2"), "~Watt.3680", 4);
			}
			if ($devicetype == 3) {
				$this->RegisterVariableFloat("POWER_CONSUMPTION3", $this->Translate("Power Consumption 3"), "~Watt.3680", 6);
				$this->RegisterVariableFloat("POWER_CONSUMPTION4", $this->Translate("Power Consumption 4"), "~Watt.3680", 8);
			}
		} else {
			$this->SendDebug('Unregister Variables', 'power comsumption', 0);
			if ($devicetype == 2 || $devicetype == 3) {
				$this->UnregisterVariable("POWER_CONSUMPTION");
				$this->UnregisterVariable("POWER_CONSUMPTION2");
			}
			if ($devicetype == 3) {
				$this->UnregisterVariable("POWER_CONSUMPTION3");
				$this->UnregisterVariable("POWER_CONSUMPTION4");
			}
		}
		if ($extended_information) {
			$this->SendDebug('Register Variables', 'extended information', 0);
			$this->RegisterVariableString("TYPE", $this->Translate("Type"), "", 10);
			IPS_SetIcon($this->GetIDForIdent("TYPE"), "Information");
			$this->RegisterVariableString("MAC", $this->Translate("MAC"), "", 11);
			IPS_SetIcon($this->GetIDForIdent("MAC"), "Notebook");
			$this->RegisterVariableString("FIRMWARE", $this->Translate("Firmware"), "", 12);
			IPS_SetIcon($this->GetIDForIdent("FIRMWARE"), "Robot");
			$this->RegisterProfileAssociation(
				'Shelly.CloudEnabled',
				'Cloud',
				'',
				'',
				0,
				1,
				0,
				0,
				0,
				[
					[FALSE, $this->Translate('disabled'), 'Cloud', -1],
					[TRUE, $this->Translate('enabled'), 'Cloud', 0x387a3c]
				]
			);
			$this->RegisterVariableBoolean("CLOUDENABLED", $this->Translate("Cloud enabled"), "Shelly.CloudEnabled", 13);
			$this->RegisterProfileAssociation(
				'Shelly.CloudConnected',
				'Cloud',
				'',
				'',
				0,
				1,
				0,
				0,
				0,
				[
					[FALSE, $this->Translate('disconnect'), 'Cloud', -1],
					[TRUE, $this->Translate('connected'), 'Cloud', 0x387a3c]
				]
			);
			$this->RegisterVariableBoolean("CLOUDCONNECTED", $this->Translate("Cloud connected"), "Shelly.CloudConnected", 14);
			$this->RegisterProfileAssociation(
				'Shelly.FirmwareUpdate',
				'Download',
				'',
				'',
				0,
				1,
				0,
				0,
				0,
				[
					[FALSE, $this->Translate('no update'), 'Download', -1],
					[TRUE, $this->Translate('update available'), 'Download', 0x387a3c]
				]
			);
			$this->RegisterVariableBoolean("UPDATE_AVAILABLE", $this->Translate("Update Available"), "Shelly.FirmwareUpdate", 15);
			$this->RegisterProfile('Shelly.RAM', 'Speedo', '', " bytes", 0, 100, 1, 0, 1);
			$this->RegisterVariableInteger("RAM_TOTAL", $this->Translate("RAM total"), "Shelly.RAM", 16);
			$this->RegisterVariableInteger("RAM_FREE", $this->Translate("RAM free"), "Shelly.RAM", 17);
		} else {
			$this->SendDebug('Unregister Variables', 'extended information', 0);
			$this->UnregisterVariable("TYPE");
			$this->UnregisterVariable("MAC");
			$this->UnregisterVariable("FIRMWARE");
			$this->UnregisterVariable("CLOUDENABLED");
			$this->UnregisterVariable("CLOUDCONNECTED");
			$this->UnregisterVariable("UPDATE_AVAILABLE");
			$this->UnregisterVariable("RAM_TOTAL");
			$this->UnregisterVariable("RAM_FREE");
		}
		$shelly_webinterface = $this->ReadPropertyBoolean("ShellyWebInterface");
		if($shelly_webinterface)
		{
			$this->RegisterVariableString("WEBINTERFACE", $this->Translate("Webinterface"), "", 20);
			$host = $this->GetHost();
			$webinterface = '<iframe src=\'http://'.$host.'\' height=500px width=600px>';
			$this->SetValue("WEBINTERFACE", $webinterface);
		}
		else
		{
			$this->UnregisterVariable("WEBINTERFACE");
		}
		$this->GetState();
		$this->GetInfo();
	}

	private function SetUpdateInterval()
	{
		$echointerval = $this->ReadPropertyInteger("UpdateInterval");
		$interval = $echointerval * 1000;
		$this->SetTimerInterval("UpdateInterval", $interval);
	}

	private function SetUpdatePowerconsumptionOn()
	{
		$this->SetTimerInterval("UpdateIntervalPowerConsumption", 1000);
	}

	private function SetUpdatePowerconsumptionOff()
	{
		$this->SetTimerInterval("UpdateIntervalPowerConsumption", 0);
	}


	/** GetDevicetype
	 *
	 * @return string
	 */
	private function GetDevicetype()
	{
		$devicetype = $this->ReadPropertyInteger("Devicetype");
		return $devicetype;
	}

	/** GetHost
	 *
	 * @return string
	 */
	private function GetHost()
	{
		$host = $this->ReadPropertyString("Host");
		return $host;
	}

	public function UpdateStatus()
	{
		$this->GetState();
		$this->GetDeviceState(1);
		$devicetype = $this->GetDevicetype();
		if ($devicetype == 2) {
			$this->GetDeviceState(2);
		}
		if ($devicetype == 3) {
			$this->GetDeviceState(2);
			$this->GetDeviceState(3);
			$this->GetDeviceState(4);
		}
	}

	public function UpdatePowerConsumption()
	{
		$this->GetState();
	}

	/** Get Info
	 *  type    string    Shelly model identifier
	 * mac    string    MAC address of the device
	 * auth    bool    Whether HTTP requests require authentication
	 * fw    string    Current firmware version
	 * num_outputs    number    Number of outputs for actuators
	 * @return array
	 */
	public function GetInfo()
	{
		$devicetype = $this->GetDevicetype();
		$command = "/shelly";
		$header = [];
		$payload = $this->SendShellyData($command, $header);
		$info = [];
		$http_code = $payload["http_code"];
		$extended_information = $this->ReadPropertyBoolean("ExtendedInformation");
		if ($http_code == 200) {
			$info = $payload["body"];
			$shelly_data = json_decode($info);
			$type = $shelly_data->type;
			$this->SendDebug(__FUNCTION__, 'Type: ' . $type, 0);
			if ($extended_information) {
				$this->SetValue("TYPE", $type);
			}
			$mac = $shelly_data->mac;
			$this->SendDebug(__FUNCTION__, 'MAC: ' . $mac, 0);
			if ($extended_information) {
				$this->SetValue("MAC", $mac);
			}
			$auth = $shelly_data->auth;
			$this->SendDebug(__FUNCTION__, 'auth: ' . $auth, 0);
			$firmware = $shelly_data->fw;
			$this->SendDebug(__FUNCTION__, 'firmware: ' . $firmware, 0);
			if ($extended_information) {
				$this->SetValue("FIRMWARE", $firmware);
			}
			$num_outputs = $shelly_data->num_outputs;
			$this->SendDebug(__FUNCTION__, 'num outputs: ' . $num_outputs, 0);
			if ($devicetype == 2 || $devicetype == 3) {
				$num_meters = $shelly_data->num_meters;
				$this->SendDebug(__FUNCTION__, 'num meters: ' . $num_meters, 0);
				$num_rollers = $shelly_data->num_rollers;
				$this->SendDebug(__FUNCTION__, 'num rollers: ' . $num_rollers, 0);
			}
		}
		return $info;
	}

	protected function SendShellyData(string $command, array $header, array $postfields = null)
	{
		$shelly_ip = $this->GetHost();
		$url = "http://" . $shelly_ip . $command;
		$this->SendDebug(__FUNCTION__, 'url: ' . $url, 0);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		if (!is_null($postfields)) {
			$postdata = http_build_query($postfields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		$result = curl_exec($ch);
		if (curl_error($ch)) {
			trigger_error('Error:' . curl_error($ch));
		}
		$info = curl_getinfo($ch);
		curl_close($ch);

		return $this->getReturnValues($info, $result);
	}

	protected function getReturnValues(array $info, string $result)
	{
		$HeaderSize = $info['header_size'];

		$http_code = $info['http_code'];
		$this->SendDebug(__FUNCTION__, "Response (http_code): " . $http_code, 0);

		$header = explode("\n", substr($result, 0, $HeaderSize));
		$this->SendDebug(__FUNCTION__, "Response (header): " . json_encode($header), 0);

		$body = substr($result, $HeaderSize);
		$this->SendDebug(__FUNCTION__, "Response (body): " . $body, 0);


		return ['http_code' => $http_code, 'header' => $header, 'body' => $body];
	}

	/*
	/settings
GET /settings

{
    "device": {
        "type": "SHSW-21",
        "mac": "16324CAABBCC",
    },
    "wifi_ap": {
        "enabled": false,
        "ssid": "shellyMODEL-16324CAABBCC",
        "key": ""
    },
    "wifi_sta": {
        "enabled": true,
        "ssid": "Castle",
        "ipv4_method": "dhcp",
    "ip": null,
    "gw": null,
    "mask": null,
    "dns": null
    },
    "login": {
        "enabled": false,
        "unprotected": false,
        "username": "admin",
        "password": "admin"
    },
    "name": "shellyMODEL-16324CAABBCC",
    "fw": "20170427-114337/master@79dbb397",
    "cloud": {
        "enabled": true,
        "connected": true
    },
    "timezone": "",
    "time": ""
}
Represents device configuration: all devices support a set of common features which are described here. Look at the device-specific /settings endpoint to see how each device extends it.

Parameters
Parameter	Type	Description
reset	bool	Will perform a factory reset of the device
Attributes
Attribute	Type	Description
device.type	string	Device model identifier
device.mac	string	MAC address of the device in hexadecimal
wifi_ap	hash	WiFi access point configuration, see /settings/ap for details
wifi_sta	hash	WiFi client configuration. See /settings/sta for details
login	hash	credentials used for HTTP Basic authentication for the REST interface. If enabled is true clients must include an Authorization: Basic ... HTTP header with valid credentials when performing TP requests.
name	string	unique name of the device.
fw	string	current FW version
cloud.enabled	bool	cloud enabled flag
time	string	current time in HH:MM format if synced
	*/

	/*
	/settings/ap
GET /settings/ap

{
    "enabled": false,
    "ssid": "shellyswitch-163248",
    "key": ""
}
Provides information about the current WiFi AP configuration and allows changes. The returned document is identical to the one returned by /settings in the wifi_ap key. Shelly devices do not allow the SSID for AP WiFi mode to be changed.

Parameters are applied immediately. Setting the enabled flag for AP mode to 1 will automatically disable STA mode.

Parameters
Parameter	Type	Description
enabled	bool	Set to 1 to return the device to AP WiFi mode
key	string	WiFi password required for association with the device's AP
Attributes
Attribute	Type	Description
enabled	bool	whether AP mode is active.
ssid	string	SSID created by the device's AP
key	string	WiFi password required for association with the device's AP
*/


	/*
	/settings/sta
	GET /settings/sta

	{
		"enabled": true,
		"ssid": "Castle",
		"key": "BigSecretKeyForCastle",
		"ipv4_method": "dhcp",
		"ip": null,
		"gw": null,
		"mask": null,
		"dns": null
	}
	Provides information about the current WiFi Client mode configuration and allows changes. The returned document is identical to the one returned by /settings in the wifi_sta key.

	Parameters are applied immediately. Setting the enabled flag for STA mode to 1 will automatically disable AP mode.

	Parameters
	Parameter	Type	Description
	enabled	bool	Set to 1 to make STA the current WiFi mode
	ssid	string	The WiFi SSID to associate with
	key	string	The password required for associating to the given WiFi SSID
	ipv4 method	string	"dhcp" or "static"
	ip	string	local ip addres if ipv4 method is "static"
	gw	string	local Geateway ip address if ipv4 method is "static"
	mask	string	Mask address if ipv4 method is "static"
	dns	string	DNS address if ipv4 method is "static"
	Attributes
	Attribute	Type	Description
	enabled	bool	whether STA mode is active.
	ssid	string	SSID of STA the device will associate with
	key	string	WiFi password for the selected SSID
	ipv4	method`	string
	ip	string	local ip addres if ipv4 method is "static"
	gw	string	local Geateway ip address if ipv4 method is "static"
	mask	string	Mask address if ipv4 method is "static"
	dns	string	DNS address if ipv4 method is "static"
	*/

	/*
	/settings/login
	GET /settings/login

	{
		"enabled": false,
		"unprotected": false,
		"username": "admin",
		"password": "admin"
	}
	GET /settings/login?enabled=1&username=boss&password=thebigone

	{
		"enabled": true,
		"unprotected": false,
		"username": "boss",
		"password": "thebigone"
	}
	HTTP authentication configuration: enabled flag, credentials. unprotected is initially false and is used by the user interface to show a warning when auth is disabled. If the user wants to keep using Shelly without a password, they can set unprotected to hide the warning.

	Parameters
	Parameter	Type	Description
	username	string	length between 1 and 50
	password	string	length between 1 and 50
	enabled	bool	whether to require HTTP authentication
	unprotected	bool	whether the user is aware of the risks
	Attributes
	Attributes are identical with the parameters and their semantics.
	*/

	/*
	/settings/cloud
	GET /settings/cloud?enabled=1

	{
		"enabled": true
	}
	Can set the "connect to cloud" flag. When set, Shelly will keep a secure connection to Allterco's servers and allow monitoring and control from anywhere.
	*/

	/*
	/status
	GET /status

	{
		"wifi_sta": {
			"connected": true,
			"ssid": "Castle",
			"ip": "192.168.2.65"
		},
		"cloud": {
			"enabled": false,
			"connected": false
		},
		"time": "17:42",
		"has_update": true,
		"ram_total": 50648,
		"ram_free": 38376,
		"uptime": 39
	}
	Encapsulates current device status information. While settings can generally be modified and don't react to the environment, this endpoint provides information about transient data which may change due to external conditions.

	Parameters
	Parameter	Type	Description
	wifi_sta	hash	Current status of the WiFi connection
	wifi_sta.ip	string	IP address assigned to this device by the WiFi router
	cloud	hash	current cloud connection status
	time	string	The current hour and minutes, in HH:MM format
	has_update	bool	If a newer firmware version is available
	ram_total, ram_free	number	Total and available amount of system memory in bytes
	uptime	number	seconds elapsed since boot
	*/

	/*
	/reboot
	GET /reboot

	{}
	When requested will cause a reboot of the device.
	*/

	/*
	Shelly Switch: /settings/relay/{index}
	GET /settings/relay/0

	{
		"ison": false,
		"has_timer": false,
		"overpower": false,
		"default_state": "off",
		"btn_type": "toggle",
		"auto_on": 0,
		"auto_off": 0,
		"schedule": true,
		"schedule_on": "XXXXXXXXXXXXXXXXXXXXXXXXXXXX",
		"schedule_off": "XXXXXXXXXXXXXXXXXXXXXXXXXXXX",
		"sun": true,
		"sun_on_times": "0000000000000000000000000000",
		"sun_off_times": "0000000000000000000000000000"
	}
	The returned document here is identical to the data returned in /settings for the particular output channel in the relays array. The attributes match the set of accepted parameters.

	Parameters
	Parameter	Type	Description
	reset	any	Submitting a non-empty value will reset settings for this relay output channel to factory defaults.
	default_state	string	Accepted values: off, on, last, switch
	btn_type	string	Accepted values: momentary, toggle, edge
	auto_on	number	Automatic flip back timer, seconds. Will engage after turning the channel OFF.
	auto_off	number	Automatic flip back timer, seconds. Will engage after turning the channel ON.
	*/


	/*
	Shelly Switch: /relay/{index}
	Shows current status of each output channel and accepts commands for controlling the channel. This is usable only when device mode is set to relay via /settings.

	Parameters
	Parameter	Type	Description
	turn	string	Accepted values are on and off. This will turn ON/OFF the respective output channel when request is sent .
	timer	number	A one-shot flip-back timer in seconds.
	Attributes
	Attribute	Type	Description
	ison	boolean	Whether the channel is turned ON or OFF
	has_timer	boolean	Whether a timer is currently armed for this channel
	overpower	boolean	Whether an overpower condition turned the channel OFF
	is_valid	boolean	Whether the associated power meter is functioning correctly
	*/


	/*
	Shelly Switch: /status

	Shelly Switch adds information about the current state of the output channels, the logical "roller" device and power metering.

	Attribute	Type	Description
	relays	array of hashes	Contains the current state of the relay output channels. See /relay/N for description of attributes
	rollers	array of hashes	Contains the current state of the logical "roller" device. See /roller/N for description of attributes
	meters	array of hashes	Current status of the power meters
	*/


	/** Get State
	 * @return array|mixed
	 */
	public function GetState()
	{
		$command = "/status";
		$header = [];
		$payload = $this->SendShellyData($command, $header);
		$info = [];
		$extended_information = $this->ReadPropertyBoolean("ExtendedInformation");
		$power_comsumption = $this->ReadPropertyBoolean("PowerConsumption");
		$devicetype = $this->GetDevicetype();
		$http_code = $payload["http_code"];
		if ($http_code == 200) {
			$info = $payload["body"];
			$this->SendDebug(__FUNCTION__, 'Info: ' . $info, 0);
			$shelly_data = json_decode($info);
			$wifi_connection = $shelly_data->wifi_sta->connected;
			$this->SendDebug(__FUNCTION__, 'Wifi Connection: ' . $wifi_connection, 0);
			$ssid = $shelly_data->wifi_sta->ssid;
			$this->SendDebug(__FUNCTION__, 'SSID: ' . $ssid, 0);
			$cloud_enabled = $shelly_data->cloud->enabled;
			$this->SendDebug(__FUNCTION__, 'Cloud Enabled: ' . $cloud_enabled, 0);
			if ($extended_information) {
				$this->SetValue("CLOUDENABLED", $cloud_enabled);
			}
			$cloud_connected = $shelly_data->cloud->connected;
			$this->SendDebug(__FUNCTION__, 'Cloud Connected: ' . $cloud_connected, 0);
			if ($extended_information) {
				$this->SetValue("CLOUDCONNECTED", $cloud_enabled);
			}
			$time = $shelly_data->time;
			$this->SendDebug(__FUNCTION__, 'Time: ' . $time, 0);
			$serial = $shelly_data->serial;
			$this->SendDebug(__FUNCTION__, 'Serial: ' . $serial, 0);
			$update = $shelly_data->has_update;
			$this->SendDebug(__FUNCTION__, 'Update: ' . $update, 0);
			if ($extended_information) {
				$this->SetValue("UPDATE_AVAILABLE", $update);
			}
			$mac = $shelly_data->mac;
			$this->SendDebug(__FUNCTION__, 'MAC: ' . $mac, 0);
			if ($extended_information) {
				$this->SetValue("MAC", $mac);
			}
			$relays = $shelly_data->relays;
			$this->SendDebug(__FUNCTION__, 'Relays: ' . json_encode($relays), 0);
			$relay_1 = $relays[0];
			$this->SendDebug(__FUNCTION__, 'Relay 1: ' . json_encode($relay_1), 0);
			if ($devicetype == 2 || $devicetype == 3) {
				$relay_2 = $relays[1];
				$this->SendDebug(__FUNCTION__, 'Relay 2: ' . json_encode($relay_2), 0);
			}
			if ($devicetype == 2 || $devicetype == 3) {
				$rollers = $shelly_data->rollers;
				$this->SendDebug(__FUNCTION__, 'Rollers: ' . json_encode($rollers), 0);
				$roller = $rollers[0];
				$this->SendDebug(__FUNCTION__, 'Roller: ' . json_encode($roller), 0);
				$meters = $shelly_data->meters[0];
				$this->SendDebug(__FUNCTION__, 'meter 1: ' . json_encode($meters), 0);
				$power = $meters->power;
				$this->SendDebug(__FUNCTION__, 'Power 1: ' . json_encode($power), 0);
				if ($power_comsumption) {
					$this->SetValue("POWER_CONSUMPTION", $power);
				}
			}
			if ($devicetype == 3) {
				$meters_2 = $shelly_data->meters[1];
				$this->SendDebug(__FUNCTION__, 'meter 2: ' . json_encode($meters_2), 0);
				$power_2 = $meters_2->power;
				$this->SendDebug(__FUNCTION__, 'Power 2: ' . json_encode($power_2), 0);
				$meters_3 = $shelly_data->meters[2];
				$this->SendDebug(__FUNCTION__, 'meter 3: ' . json_encode($meters_3), 0);
				$power_3 = $meters_3->power;
				$this->SendDebug(__FUNCTION__, 'Power 3: ' . json_encode($power_3), 0);
				$meters_4 = $shelly_data->meters[3];
				$this->SendDebug(__FUNCTION__, 'meter 4: ' . json_encode($meters_4), 0);
				$power_4 = $meters_4->power;
				$this->SendDebug(__FUNCTION__, 'Power 4: ' . json_encode($power_4), 0);
				if ($power_comsumption) {
					$this->SetValue("POWER_CONSUMPTION2", $power_2);
					$this->SetValue("POWER_CONSUMPTION3", $power_3);
					$this->SetValue("POWER_CONSUMPTION4", $power_4);
				}
			}
			if ($devicetype == 2 || $devicetype == 3) {
				$is_valid = $meters->is_valid;
				$this->SendDebug(__FUNCTION__, 'is valid: ' . json_encode($is_valid), 0);
			}
			if ($devicetype == 3) {
				$counter = $meters->counters;
				$this->SendDebug(__FUNCTION__, 'Counter: ' . json_encode($counter), 0);
			} elseif ($devicetype == 2) {
				$counter = $meters->counter;
				$this->SendDebug(__FUNCTION__, 'Counter: ' . json_encode($counter), 0);
			}
			$ram_total = $shelly_data->ram_total;
			$this->SendDebug(__FUNCTION__, 'RAM Total: ' . $ram_total, 0);
			if ($extended_information) {
				$this->SetValue("RAM_TOTAL", $ram_total);
			}
			$ram_free = $shelly_data->ram_free;
			$this->SendDebug(__FUNCTION__, 'RAM Free: ' . $ram_free, 0);
			if ($extended_information) {
				$this->SetValue("RAM_FREE", $ram_free);
			}
			$uptime = $shelly_data->uptime;
			$this->SendDebug(__FUNCTION__, 'Uptime: ' . $uptime, 0);
		}
		return $info;
	}

	public function GetPowerConsumption($id = NULL)
	{
		$info = $this->GetState();
		if (empty($info)) {
			return false;
		} else {
			$shelly_data = json_decode($info);
			$meters = $shelly_data->meters[0];
			$power = $meters->power;
			if (is_null($id)) {
				$power = $meters->power;
			} elseif ($id == 1) {
				$power = $meters->power;
			} elseif ($id == 2) {
				$power = $meters->power;
			} elseif ($id == 3) {
				$power = $meters->power;
			} elseif ($id == 4) {
				$power = $meters->power;
			}
			return $power;
		}
	}


	/** Get State
	 * @param $id
	 * @return array|mixed
	 */
	public function GetDeviceState($id)
	{
		if ($id < 1 || $id > 4) {
			$this->SendDebug(__FUNCTION__, 'ID has to be 1 -4, ' . $id . " given", 0);
			return false;
		}
		$device = $id - 1;
		$command = "/relay/" . $device;
		$header = [];
		$payload = $this->SendShellyData($command, $header);
		$info = [];
		$http_code = $payload["http_code"];
		if ($http_code == 200) {
			$info = $payload["body"];
			$shelly_data = json_decode($info);
			$ison = $shelly_data->ison;
			$this->SendDebug(__FUNCTION__, 'State: ' . print_r($ison), 0);
			$this->SetValue("STATE" . $id, $ison);
			$has_timer = $shelly_data->has_timer;
			$this->SendDebug(__FUNCTION__, 'has timer: ' . print_r($has_timer), 0);
			$overpower = $shelly_data->overpower;
			$this->SendDebug(__FUNCTION__, 'overpower: ' . print_r($overpower), 0);
			$is_valid = $shelly_data->is_valid;
			$this->SendDebug(__FUNCTION__, 'is valid: ' . print_r($is_valid), 0);
		}
		return $info;
	}

	/** Power On
	 * @param $id
	 * @return bool
	 */
	public function PowerOn($id)
	{
		if ($id < 1 || $id > 4) {
			$this->SendDebug(__FUNCTION__, 'ID has to be 1 -4, ' . $id . " given", 0);
			return false;
		}
		$device = $id - 1;
		$command = "/relay/" . $device;
		$postfields = ['turn' => 'on'];
		$header = ['Content-Type: application/x-www-form-urlencoded'];
		$payload = $this->SendShellyData($command, $header, $postfields);
		$ison = $this->CheckIsOn($payload, "STATE" . $id);
		//$this->SetUpdatePowerconsumptionOn();
		return $ison;
	}


	/** Power Off
	 * @param $id
	 * @return bool
	 */
	public function PowerOff($id)
	{
		if ($id < 1 || $id > 4) {
			$this->SendDebug(__FUNCTION__, 'ID has to be 1 -4, ' . $id . " given", 0);
			return false;
		}
		$device = $id - 1;
		$command = "/relay/" . $device;
		$postfields = ['turn' => 'off'];
		$header = ['Content-Type: application/x-www-form-urlencoded'];
		$payload = $this->SendShellyData($command, $header, $postfields);
		$ison = $this->CheckIsOn($payload, "STATE" . $id);
		//$this->SetUpdatePowerconsumptionOff();
		return $ison;
	}

	private function CheckIsOn($payload, $ident)
	{
		$ison = false;
		$http_code = $payload["http_code"];
		if ($http_code == 200) {
			$info = $payload["body"];
			$shelly_data = json_decode($info);
			$ison = $shelly_data->ison;
			if ($ison) {
				$this->SetValue($ident, true);
			} else {
				$this->SetValue($ident, false);
			}
		}
		return $ison;
	}


	/*
	settings

	{"device":{"type":"SHSW-21","mac":"2E3AE81F6C4B","hostname":"shellyswitch-1F6C4B","num_outputs":2,"num_meters":1,"num_rollers":1},"wifi_ap":{"enabled":false,"ssid":"shellyswitch-1F6C4B","key":""},"wifi_sta":{"enabled":true,"ssid":"FonzoWLAN","key":"2185424192622616"},"login":{"enabled":false,"unprotected":false,"username":"admin","password":"admin"},"pin_code":"qa/})z","name":null,"fw":"20180130-135033/v0.2.0@73a40eef","build_info":{"build_id":"20180130-135033/v0.2.0@73a40eef","build_timestamp":"2018-01-30T13:50:33Z","build_version":"1.0"},"cloud":{"enabled":true,"connected":true},"timezone":"Europe/Berlin","time":"11:56","hwinfo":{"hw_revision":"amazon-2018-01","batch_id":3},"mode":"relay","max_power":1840,"relays":[{"name":null,"ison":false,"has_timer":false,"overpower":false,"default_state":"off","btn_type":"toggle","auto_on":0.00,"auto_off":0.00,"schedule":false,"schedule_rules":[],"sun":false,"sun_on_times":"0000000000000000000000000000","sun_off_times":"0000000000000000000000000000"},{"name":null,"ison":false,"has_timer":false,"overpower":false,"default_state":"off","btn_type":"toggle","auto_on":0.00,"auto_off":0.00,"schedule":false,"schedule_rules":[],"sun":false,"sun_on_times":"0000000000000000000000000000","sun_off_times":"0000000000000000000000000000"}],"rollers":[{"maxtime":20.00,"default_state":"stop","swap":false,"input_mode":"openclose","button_type":"toggle","state":"stop","power":0.00,"is_valid":true,"safety_switch":false,"schedule":false,"schedule_rules":[],"sun":false,"sun_open_times":"0000000000000000000000000000","sun_close_times":"0000000000000000000000000000","obstacle_mode":"disabled","obstacle_action":"stop","obstacle_power":200,"obstacle_delay":1,"safety_mode":"while_opening","safety_action":"stop","safety_allowed_on_trigger":"none"}],"meters":[{"power":0.00,"is_valid":true,"counter":0}]}
	*/


	/** Sends Request to IO and get response.
	 *
	 * @param string $method
	 * @param array|null $getfields
	 * @param array|null $postfields
	 * @param null|string $url
	 *
	 * @param null $optpost
	 * @param null $automation
	 *
	 * @return mixed
	 */
	private function SendData(string $method, array $getfields = null, array $postfields = null, $url = null, $optpost = null, $automation = null)
	{
		$this->SendDebug(
			__FUNCTION__,
			'Method: ' . $method . ', Getfields: ' . json_encode($getfields) . ', Postfields: ' . json_encode($postfields) . ', URL: ' . $url
			. ', Option Post: ' . (int)$optpost . ', Automation: ' . json_encode($automation), 0
		);

		$Data['DataID'] = '{8E187D67-F330-2B1D-8C6E-B37896D7AE3E}';

		$Data['Buffer'] = ['method' => $method];

		if (isset($getfields)) {
			$Data['Buffer']['getfields'] = $getfields;
		}
		if (isset($postfields)) {
			$Data['Buffer']['postfields'] = $postfields;
		}
		if (isset($url)) {
			$Data['Buffer']['url'] = $url;
		}
		if (isset($optpost)) {
			$Data['Buffer']['optpost'] = $optpost;
		}
		if (isset($automation)) {
			$Data['Buffer']['automation'] = $automation;
		}

		$ResultJSON = $this->SendDataToParent(json_encode($Data));
		$this->SendDebug(__FUNCTION__, 'Result: ' . $ResultJSON, 0);

		return json_decode($ResultJSON, true); //returns an array of http_code, body and header
	}

	public function RequestAction($Ident, $Value)
	{
		if ($Ident == "STATE1") {
			if ($Value) {
				$this->PowerOn(1);
			} else {
				$this->PowerOff(1);
			}
		}
		if ($Ident == "STATE2") {
			if ($Value) {
				$this->PowerOn(2);
			} else {
				$this->PowerOff(2);
			}
		}
		if ($Ident == "STATE3") {
			if ($Value) {
				$this->PowerOn(3);
			} else {
				$this->PowerOff(3);
			}
		}
		if ($Ident == "STATE4") {
			if ($Value) {
				$this->PowerOn(4);
			} else {
				$this->PowerOff(4);
			}
		}
	}

	/**
	 * register profiles
	 *
	 * @param $Name
	 * @param $Icon
	 * @param $Prefix
	 * @param $Suffix
	 * @param $MinValue
	 * @param $MaxValue
	 * @param $StepSize
	 * @param $Digits
	 * @param $Vartype
	 */
	private function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
	{

		if (!IPS_VariableProfileExists($Name)) {
			IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
		} else {
			$profile = IPS_GetVariableProfile($Name);
			if ($profile['ProfileType'] != $Vartype) {
				$this->SendDebug("Profile", 'Variable profile type does not match for profile ' . $Name, 0);
			}
		}

		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
		IPS_SetVariableProfileValues(
			$Name, $MinValue, $MaxValue, $StepSize
		); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
	}

	/**
	 * register profile association
	 *
	 * @param $Name
	 * @param $Icon
	 * @param $Prefix
	 * @param $Suffix
	 * @param $MinValue
	 * @param $MaxValue
	 * @param $Stepsize
	 * @param $Digits
	 * @param $Vartype
	 * @param $Associations
	 */
	private function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
	{
		if (is_array($Associations) && sizeof($Associations) === 0) {
			$MinValue = 0;
			$MaxValue = 0;
		}
		$this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

		if (is_array($Associations)) {
			foreach ($Associations AS $Association) {
				IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
			}
		} else {
			$Associations = $this->$Associations;
			foreach ($Associations AS $code => $association) {
				IPS_SetVariableProfileAssociation($Name, $code, $this->Translate($association), $Icon, -1);
			}
		}
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
				'actions' => $this->FormActions(),
				'status' => $this->FormStatus()]
		);
	}

	/**
	 * return head form head
	 *
	 * @return array
	 */
	protected function FormHead()
	{
		$form = [
			[
				'type' => 'Label',
				'caption' => 'IP adress can be found in the Shelly App -> Settings -> Device Information'],
			[
				'name' => 'Host',
				'type' => 'ValidationTextBox',
				'caption' => 'IP adress'],
			[
				'type' => 'Label',
				'caption' => 'show webinterface of Shelly in the Webfront'],
			[
				'name' => 'ShellyWebInterface',
				'type' => 'CheckBox',
				'caption' => 'Shelly WebInterface']
		];
		$devicetype = $this->GetDevicetype();
		if ($devicetype == 2 || $devicetype == 3) {
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'caption' => 'create variables for power consumption'],
					[
						'name' => 'PowerConsumption',
						'type' => 'CheckBox',
						'caption' => 'power consumption']
				]
			);
		}

		$form = array_merge_recursive(
			$form,
			[
				[
					'type' => 'Label',
					'caption' => 'setup variables for extended info (firmware, mac etc.)'],
				[
					'name' => 'ExtendedInformation',
					'type' => 'CheckBox',
					'caption' => 'extended information'],
				[
					'type' => 'Label',
					'caption' => 'update interval (seconds)'],
				[
					'name' => 'UpdateInterval',
					'type' => 'NumberSpinner',
					'caption' => 'Seconds',
					'suffix' => 'seconds'],
				[
					'name' => 'Devicetype',
					'type' => 'Select',
					'caption' => 'device type',
					'options' => [
						["caption" => "please select devicetype",
							"value" => 0],
						["caption" => "Shelly 1",
							"value" => 1],
						["caption" => "Shelly Switch",
							"value" => 2],
						["caption" => "Shelly 4 Pro",
							"value" => 3],
						["caption" => "Shelly Plug",
							"value" => 4],
						["caption" => "Shelly Bulb",
							"value" => 5],
						["caption" => "Shelly Sense",
							"value" => 6]
					]]
			]
		);

		return $form;
	}

	/*
	 * [
				'type' => 'Label',
				'caption' => 'use MQTT for state'],
			[
				'name' => 'MQTT',
				'type' => 'CheckBox',
				'caption' => 'MQTT'],
	 *
	 */


	/**
	 * return form actions
	 *
	 * @return array
	 */
	protected function FormActions()
	{
		$form = [
			[
				'type' => 'Button',
				'caption' => 'Get State',
				'onClick' => 'Shelly_GetState($id);'],
			[
				'type' => 'Button',
				'caption' => 'On',
				'onClick' => 'Shelly_PowerOn($id, 1);'],
			[
				'type' => 'Button',
				'caption' => 'Off',
				'onClick' => 'Shelly_PowerOff($id, 1);']];

		return $form;
	}

	/**
	 * return from status
	 *
	 * @return array
	 */
	protected function FormStatus()
	{
		$form = [
			[
				'code' => 201,
				'icon' => 'error',
				'caption' => '201 error.'],
			[
				'code' => 210,
				'icon' => 'error',
				'caption' => 'devicetype field must not be empty.'],
			[
				'code' => 211,
				'icon' => 'error',
				'caption' => 'ip adress field must not be empty.'],
			[
				'code' => 212,
				'icon' => 'error',
				'caption' => 'wrong ip, please check ip and format for example 192.168.0.1']];

		return $form;
	}
}