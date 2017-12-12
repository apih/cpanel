<?php
// A wrapper for cPanel API
// Support cPanel API 2, UAPI and WHM API 1

namespace apih\cPanel;

class cPanel
{
	protected $host;
	protected $username;
	protected $password;
	protected $debug_enabled = false;
	protected $debug_filename = null;
	protected $raw_output = false;

	public function __construct($host, $username, $password)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
	}

	public function setDebug($flag = true)
	{
		$this->debug_enabled = (bool) $flag;
	}

	public function setDebugFilename($filename)
	{
		$this->debug_filename = $filename;
	}

	public function setRawOutput($flag = true)
	{
		$this->raw_output = $flag;
	}

	protected function logDebug($message)
	{
		if (!$this->debug_enabled) return;

		if ($this->debug_filename === null) {
			error_log($message);
		} else {
			error_log((new \DateTime())->format('[d-M-y H:i:s e]') . ' ' . $message . PHP_EOL, 3, $this->debug_filename);
		}
	}

	protected function curlRequest($url, $query_data)
	{
		$this->logDebug('URL: ' . $url);

		if ($query_data) {
			$query_string = http_build_query($query_data);
			$url = $url . '?' . $query_string;

			$this->logDebug('DATA: ' . $query_string);
		}

		$auth_str = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
		$this->logDebug($auth_str);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["$auth_str\r\n"]);
		curl_setopt($ch, CURLOPT_URL, $url);

		$response = curl_exec($ch);

		curl_close($ch);

		$this->logDebug('RESPONSE: ' . $response);

		if ($this->raw_output) {
			return response;
		} else {
			return json_decode($response, true);
		}
	}

	// Refer https://documentation.cpanel.net/display/SDK/Guide+to+cPanel+API+2 for list of modules and functions
	public function api2($module, $function, $args = [])
	{
		$url = "$this->host/json-api/cpanel";

		$query_data = [
			'cpanel_jsonapi_user' => $this->username,
			'cpanel_jsonapi_apiversion' => 2,
			'cpanel_jsonapi_module' => $module,
			'cpanel_jsonapi_func' => $function,
		];

		$query_data = array_merge($query_data, $args);

		return $this->curlRequest($url, $query_data);
	}

	// Refer https://documentation.cpanel.net/display/SDK/Guide+to+UAPI for list of modules and functions
	public function uapi($module, $function, $args = [])
	{
		$url = "$this->host/execute/$module/$function";

		return $this->curlRequest($url, $args);
	}

	// Refer https://documentation.cpanel.net/display/SDK/Guide+to+WHM+API+1 for list of functions
	public function whm1($function, $args = [])
	{
		$url = "$this->host/json-api/$function?api.version=1";

		$query_data = array_merge([
			'api.version' => 1
		], $args);

		return $this->curlRequest($url, $query_data);
	}
}
?>