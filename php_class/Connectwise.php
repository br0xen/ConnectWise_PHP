<?php
class Connectwise {
	// Using SSL?
	var $use_ssl = TRUE;
	// Is the SSL cert valid? 
	// (If there appear to be problems with SSL, you may want to set this to FALSE)
	var $valid_ssl_cert = FALSE;
	// Host to connect to
	var $cw_host = "connectwise.example.com";
	// This apparently can change with the connectwise version
	var $cw_release = "v4_6_release";

	// Company ID that you're connecting to
	var $co_id = "";
	// Integrator Username
	var $username = "";
	// Integrator Password
	var $password = "";

	/* You shouldn't need to edit anything past this point */
	var $base_url_ext = "services/system_io/integration_io/processClientAction.rails";
	var $base_url = "";
	var $actionName = "";
	var $parms = array();

	public function __construct($init_vars=array()) {
		$this->base_url = ($this->use_ssl?"https://":"http://").$this->cw_host."/".$this->cw_release."/".$this->base_url_ext;
		if(isset($init_vars['cw_host'])) {
			$this->setCWHost($init_vars['cw_host']);
		}
	}

	public function useSSL($yn_ssl=TRUE) { $this->use_ssl = $yn_ssl; }
	public function validSSL($yn_ssl=TRUE) { $this->valid_ssl_cert = $yn_ssl; }

	public function setCWHost($new_cw_host=NULL) { 
		if(isset($new_cw_host)) {
			$this->cw_host = $new_cw_host;
		}
		$this->base_url = ($this->use_ssl?"https://":"http://").$this->cw_host."/".$this->cw_release."/".$this->base_url_ext;
	}
	public function getCWHost() { return $this->base_url; } 
	public function setCOID($new_co_id) { $this->co_id = $new_co_id; }
	public function getCOID() { return $this->co_id; }
	public function setUsername($username) { $this->username = $username; }
	public function getUsername() { return $this->username; }
	public function setPassword($password) { $this->password = $password; }
	public function getPassword() { return $this->password; }
	public function setAction($new_action="") { $this->actionName = $new_action; }
	public function getAction() { return $this->actionName; }
	public function setParameters($new_parms) { $this->parms = $new_parms; }
	public function getParameters() { return $this->parms; }
	public function setParameterValue($parm_key, $parm_val) { $this->parms[$parm_key] = $parm_val; }
	public function getParameterValue($parm_key) { return $this->parms[$parm_key]; }

	public function genActionString() {
		$xml =   '<?xml version="1.0" encoding="utf-16"?>'
					.'<'.$this->actionName.' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
					.'<CompanyName>'.$this->co_id.'</CompanyName>'
					.'<IntegrationLoginId>'.$this->username.'</IntegrationLoginId>'
					.'<IntegrationPassword>'.$this->password.'</IntegrationPassword>';
		foreach($this->parms as $parm_name => $parm_val) {
			$xml .= '<'.$parm_name.'>'.$parm_val.'</'.$parm_name.'>';
		}
		$xml .= '</'.$this->actionName.'>';
		return $xml;
	}

	public function makeCall() {
		// Append Action stuff to URL
		$xml = $this->genActionString();
		$cp = curl_init($this->base_url);
		curl_setopt($cp, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($cp, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($cp, CURLOPT_MAXREDIRS, 3);
		if($this->use_ssl) {
			curl_setopt($cp, CURLOPT_PORT, 443);
			curl_setopt($cp, CURLOPT_SSL_VERIFYPEER, $this->valid_ssl_cert);
		}
		curl_setopt($cp, CURLOPT_POST, TRUE);
		curl_setopt($cp, CURLOPT_POSTFIELDS, array('actionString'=>$xml));
		$rawXML = curl_exec($cp);
		curl_close($cp);
		// ConnectWise returns an XML file that claims to be UTF-16 but it
		// actually appears to be utf-8, so a string replace here fixes it
		$rawXML = str_replace("utf-16","utf-8",$rawXML);
		$ret = new SimpleXMLElement($rawXML);
		return json_decode(json_encode($ret), 1);
	}
}