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
	var $api_version = "2.0";

  // Company ID that you're connecting to
	var $co_id = "";
  // Integrator Username
	var $username = "";
  // Integrator Password
	var $password = "";

  /* You shouldn't need to edit anything beyond this point */
	var $base_url_ext = '';
	var $base_url = '';
	var $actionName = '';
	var $xmlBody = '';
  var $lastXML = '';
  var $lastXMLBody = '';
  var $lastURL = '';
	var $errorFlag = FALSE;
	var $parms = array();
	var $headers = array();

	public function __construct($init_vars=array()) {
		$this->setBaseUrlExt();
		if(isset($init_vars['cw_host'])) { $this->setCWHost($init_vars['cw_host']); }
		$this->base_url = ($this->use_ssl?"https://":"http://").$this->cw_host;
	}
	  
	public function useSSL($yn_ssl=TRUE) { $this->use_ssl = $yn_ssl; }
	public function validSSL($yn_ssl=TRUE) { $this->valid_ssl_cert = $yn_ssl; }

	public function setErrorFlag($newErrorFlag) { $this->errorFlag = $newErrorFlag; }
	public function getErrorFlag() { return $this->errorFlag; }
	public function getCWUrl() { return $this->base_url; }
	public function setCOID($new_co_id) { $this->co_id = $new_co_id; }
	public function getCOID() { return $this->co_id; }
	public function setUsername($username) { $this->username = $username; }
	public function getUsername() { return $this->username; }
	public function setPassword($password) { $this->password = $password; }
	public function getPassword() { return $this->password; }
	public function setAction($new_action) { $this->actionName = $new_action; }
	public function getAction() { return $this->actionName; }
	public function setParameters($new_parms) { $this->parms = $new_parms; }
	public function getParameters() { return $this->parms; }
	public function setParameterValue($parm_key, $parm_val) { $this->parms[$parm_key] = $parm_val; }
	public function getParameterValue($parm_key) { return $this->parms[$parm_key]; }
	public function setCWHost($newCWHost) { $this->cw_host = $newCWHost; }
	public function getCWHost() { return $this->cw_host; }
	public function setXMLBody($newXMLBody) { $this->xmlBody = $newXMLBody; }
	public function getXMLBody() { return $this->xmlBody; }
  	
	public function makeCall() {
		$xml = $this->genActionString();
    $this->lastXML = $xml;
    $this->lastXmlBody = $this->xmlBody;
    $this->xmlBody = '';

		$cp = curl_init();
    $this->lastURL=$this->base_url.'/'.$this->cw_release
                  .'/apis/'.$this->api_version.'/'.$this->base_url_ext;
		curl_setopt($cp, CURLOPT_URL, $this->lastURL);
		curl_setopt($cp, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($cp, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($cp, CURLOPT_MAXREDIRS, 10);
		curl_setopt($cp, CURLOPT_TIMEOUT, 40);
		if($this->use_ssl) {
		  curl_setopt($cp, CURLOPT_PORT, 443);
		  curl_setopt($cp, CURLOPT_SSL_VERIFYPEER, $this->valid_ssl_cert);
		  curl_setopt($cp, CURLOPT_SSL_VERIFYHOST, false);
		}
		curl_setopt($cp, CURLOPT_POST, TRUE);
		curl_setopt($cp, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($cp, CURLOPT_HTTPHEADER, $this->headers);
		$rawXML = curl_exec($cp);
		curl_close($cp);
		
		// Load the response into an object
		$xmlObj = new SimpleXMLElement($rawXML);
		// Check the return from CW, if it has an errorDetail element store it
		$retError = $xmlObj->xpath('//errorDetail');
		// if CW's response is empty your request didn't hit the CW API
		if(empty($rawXML)) {
			return 'Response from ConnectWise is not available. Please check your URL';
		} elseif ($retError == TRUE) {
      // Does the response from CW include an errorDetail element?
			// I want to place a switch function from here
			// but I need all the error codes from CW to handle it properly
			$this->errorFlag = TRUE;
			return $retError;
		} else{
      // Cut out SOAP Envelope, we just want what's in the body
      $name_spaces = $xmlObj->getNamespaces(true);
      $retObj = $xmlObj->children($name_spaces['soap'])->Body->children();
      // And we want it as an array
			return json_decode(json_encode($retObj), TRUE);
		}
	}

	public function genActionString() {
    if(empty($this->xmlBody)) {
      // Do we have options that we should use to generate the body?
      $this->xmlBody = $this->genXMLFromParameters();
    }
		$xml = '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
						<soap:Body>
							<'.$this->actionName.' xmlns="http://connectwise.com">
								<credentials>
									<CompanyId>'.$this->co_id.'</CompanyId>
									<IntegratorLoginId>'.$this->username.'</IntegratorLoginId>
									<IntegratorPassword>'.$this->password.'</IntegratorPassword>
								</credentials>
								'.$this->xmlBody.'
							</'.$this->actionName.'>
						</soap:Body>
					</soap:Envelope>';
		$this->headers = array(
			"Content-type: text/xml;charset=\"utf-8\"", 
			"Accept: text/xml", 
			"Cache-Control: no-cache", 
			"Pragma: no-cache", 
			"Content-length: ".strlen($xml),);
		return $xml;
	}

  public function genXMLFromParameters($parm_arr = NULL) {
    $ret = '';
    if($parm_arr == NULL) { $parm_arr = $this->parms; }
    foreach($this->parms as $k => $v) {
      if(is_array($v)) {
        $ret.='<'.$k.'>'.$this->genXMLFromParameters($v).'</'.$k.'>';
      } else {
        $ret.='<'.$k.'>'.$v.'</'.$k.'>';
      }
    }
    return $ret;
  }

	public function setBaseUrlExt () {
		Switch ($this->actionName) {
      case "AddActivity":
      case "AddOrUpdateActivity":
      case "DeleteActivity":
      case "FindActivities":
      case "GetActivity":
      case "LoadActivity":
      case "UpdateActivity":
				$this->base_url_ext="ActivityApi.asmx?wsdl";
				break;

      case "AddOrUpdateAgreement":
      case "AddOrUpdateAgreementAdjustment":
      case "AddOrUpdateAgreementSite":
      case "AddOrUpdateAgreementWorkRole":
      case "AddOrUpdateAgreementWorkType":
      case "DeleteAgreement":
      case "DeleteAgreementAdjustment":
      case "DeleteAgreementSite":
      case "DeleteAgreementWorkRole":
      case "DeleteAgreementWorkType":
      case "FindAgreementAdjustments":
      case "FindAgreements":
      case "FindAgreementSites":
      case "FindAgreementWorkRoles":
      case "FindAgreementWorkTypes":
      case "GetAgreement":
      case "GetAgreementAdjustment":
      case "GetAgreementSite":
      case "GetAgreementWorkRole":
      case "GetAgreementWorkType":
      case "GetAgreementAddition":
      case "AddOrUpdateAgreementAddition":
      case "FindAgreementAdditions":
      case "DeleteAgreementAddition":
      case "GetAgreementWorkRoleExclusion":
      case "GetAgreementWorkTypeExclusion":
      case "FindAgreementExclusions":
      case "AddOrRemoveAgreementWorkTypeExclusion":
      case "AddOrRemoveAgreementWorkRoleExclusion":
      case "GetAgreementBoardDefault":
      case "FindAgreementBoardDefaults":
      case "AddOrUpdateAgreementBoardDefault":
      case "DeleteAgreementBoardDefault":
				$this->base_url_ext="AgreementApi.asmx?wsdl";
				break;

      case "FindCompanies":
      case "AddCompany":
      case "AddOrUpdateCompany":
      case "AddOrUpdateCompanyNote":
      case "DeleteCompany":
      case "DeleteCompanyNote":
      case "DeletePartnerCompanyNote":
      case "GetAllCompanyNotes":
      case "GetAllPartnerCompanyNotes":
      case "GetCompany":
      case "GetCompanyNote":
      case "GetPartnerCompanyNote":
      case "GetCompanyProfile":
      case "GetPartnerCompanyProfile":
      case "LoadCompany":
      case "SetCompanyDefaultContact":
      case "SetPartnerCompanyDefaultContact":
      case "UpdateCompany":
      case "UpdateCompanyProfile":
      case "UpdatePartnerCompanyProfile":
				$this->base_url_ext="CompanyApi.asmx?wsdl";
				break;
			
			case "AddConfiguration":
			case "AddConfigurationType":
			case "AddOrUpdateConfiguration":
			case "AddOrUpdateConfigurationType":
			case "DeleteConfiguration":
			case "DeleteConfigurationType":
			case "DeleteConfigurationTypeQuestion":
			case "DeletePossibleResponse":
			case "FindConfigurationCount":
			case "FindConfigurationsCount":
			case "FindConfigurationTypes":
			case "FindConfigurations":
			case "GetConfiguration":
			case "GetConfigurationType":
			case "LoadConfiguration":
			case "LoadConfigurationType":
			case "UpdateConfigration":
			case "UpdateConfigrationType":
				$this->base_url_ext="ConfigurationApi.asmx?wsdl";
				break;

      case "AddContactToGroup":
      case "AddOrUpdateContact":
      case "AddOrUpdateContactCommunicationItem":
      case "AddOrUpdateContactNote":
      case "Authenticate":
      case "DeleteContact":
      case "DeleteContactCommunicationItem":
      case "DeleteNote":
      case "FindContactCount/FindContactsCount":
      case "FindContacts":
      case "GetAllCommunicationTypesAndDescription":
      case "GetAllContactCommunicationItems":
      case "GetAllContactNotes":
      case "GetAvatarImage":
      case "GetContact":
      case "GetContactCommunicationItem":
      case "GetContactNote":
      case "GetPortalConfigSettings":
      case "GetPortalLoginCustomizations":
      case "GetPortalSecurity":
      case "GetPresenceStatus":
      case "LoadContact":
      case "RemoveContactFromGroup":
      case "RequestPassword":
      case "SetDefaultContactCommunicationItem":
      case "UpdatePresenceStatus":
				$this->base_url_ext="ContactApi.asmx?wsdl";
				break;
			
      case "AddDocuments":
      case "DeleteDocument":
      case "FindDocuments":
      case "GetDocument":
				$this->base_url_ext="DocumentApi.asmx?wsdl";
				break;

      case "AddOrUpdateSpecialInvoice":
      case "AddOrUpdateSpecialInvoiceProduct":
      case "DeleteSpecialInvoice":
      case "DeleteSpecialInvoiceByInvoiceNumber":
      case "DeleteSpecialInvoiceProduct":
      case "FindInvoiceCount":
      case "FindInvoices":
      case "FindSpecialInvoices":
      case "GetApplyToForCompanyByType":
      case "GetInvoice":
      case "GetInvoiceByInvoiceNumber":
      case "GetInvoicePdf":
      case "GetSpecialInvoice":
      case "GetSpecialInvoiceByInvoiceNumber":
      case "LoadInvoice":
				$this->base_url_ext="InvoiceApi.asmx?wsdl";
				break;

      case "GetManagedGroup":
      case "GetManagedServers":
      case "GetManagedWorkstations":
      case "GetManagementItSetupsName":
      case "UpdateManagedDevices":
      case "UpdateManagedServers":
      case "UpdateManagedWorkstations":
      case "UpdateManagementSolution":
      case "UpdateManagementSummaryReports":
      case "UpdateSpamStatsDomains":
				$this->base_url_ext="ManagedDeviceApi.asmx?wsdl";
				break;

      case "RecordCampaignImpression":
      case "RecordEmailOpened":
      case "RecordFormSubmission":
      case "RecordLinkClicked":
				$this->base_url_ext="MarketingApi.asmx?wsdl";
				break;

      case "FindMembers":
      case "AuthenticateSession":
      case "CheckConnectWiseAuthenticationCredentials":
      case "CreateAuthenticatedMemberHashToken":
      case "GetMemberIdByRemoteSupportPackageAuthenticationCredentials":
      case "IsValidMemberIdAndPassword":
				$this->base_url_ext="MemberApi.asmx?wsdl";
				break;

      case "AddForecastAndRecurringRevenue":
      case "AddOpportunity":
      case "AddOpportunityDocuments":
      case "AddOpportunityItem":
      case "AddOrUpdateForecastAndRecurringRevenue":
      case "AddOrUpdateOpportunity":
      case "AddOrUpdateOpportunityItem":
      case "DeleteForecast":
      case "DeleteOpportunity":
      case "DeleteOpportunityDocument":
      case "DeleteOpportunityItem":
      case "DeleteOpportunityNote":
      case "DeleteRecurringRevenue":
      case "FindOpportunities":
      case "FindOpportunityCount":
      case "GetOpportunity":
      case "GetOpportunityDocuments":
      case "LoadOpportunity":
      case "UpdateForecastAndRecurringRevenue":
      case "UpdateOpportunity":
      case "UpdateOpportunityItem":
				$this->base_url_ext="OpportunityApi.asmx?wsdl";
				break;

      case "OpportunityToProjectConversion":
      case "OpportunityToSalesOrderConversion":
      case "OpportunityToTicketConversion":
				$this->base_url_ext="OpportunityConversionApi.asmx?wsdl";
				break;

      case "AddOrUpdateProduct":
      case "AddProduct":
      case "DeleteProduct":
      case "FindProducts":
      case "GetProduct":
      case "GetQuantityOnHand":
      case "LoadProduct":
      case "UpdateProduct":
      case "AddOrUpdateProductPickedandShipped":
      case "GetProductPickedandShipped":
      case "DeleteProductPickedandShipped":
				$this->base_url_ext="ProductApi.asmx?wsdl";
				break;

      case "AddOrUpdateProject":
      case "AddOrUpdateProjectContact":
      case "AddOrUpdateProjectNote":
      case "AddOrUpdateProjectPhase":
      case "AddOrUpdateProjectTeamMember":
      case "AddOrUpdateProjectTicket":
      case "AddOrUpdateProjectWorkPlan":
      case "ConvertServiceTicketToProjectTicket":
      case "DeleteProject":
      case "DeleteProjectContact":
      case "DeleteProjectNote":
      case "DeleteProjectPhase":
      case "DeleteProjectTeamMember":
      case "DeleteProjectTicket":
      case "FindPhases":
      case "FindProjectContacts":
      case "FindProjectCount":
      case "FindProjectNotes":
      case "FindProjectTeamMembers":
      case "FindProjectTickets":
      case "FindProjects":
      case "GetProject":
      case "GetProjectContact":
      case "GetProjectNote":
      case "GetProjectPhase":
      case "GetProjectTeamMember":
      case "GetProjectTicket":
      case "GetProjectWorkPlan":
      case "LoadProjectWorkPlan":
				$this->base_url_ext="ProjectApi.asmx?wsdl";
				break;

      case "AddOrUpdatePurchaseOrder":
      case "AddOrUpdatePurchaseOrderLineItem":
      case "AddPurchaseOrder":
      case "AddPurchaseOrderLineItem":
      case "CreatePurchaseOrderFromProductDemandsAction":
      case "DeletePurchaseOrder":
      case "DeletePurchaseOrderLineItem":
      case "FindPurchaseOrders":
      case "GetAllOpenProductDemands":
      case "GetPurchaseOrder":
      case "LoadPurchaseOrder":
      case "UpdatePurchaseOrder":
      case "UpdatePurchaseOrderLineItem":
				$this->base_url_ext="PurchasingApi.asmx?wsdl";
				break;

      case "GetPortalReports":
      case "GetReportFields":
      case "GetReports":
      case "RunPortalReport":
      case "RunReportCount":
      case "RunReportQuery":
      case "RunReportQueryWithFilters":
      case "RunReportQueryWithTimeout":
        $this->base_url_ext="ReportingApi.asmx?wsdl";
        break;

      case "AddOrUpdateActivityScheduleEntry":
      case "AddOrUpdateMiscScheduleEntry":
      case "AddOrUpdateTicketScheduleEntry":
      case "DeleteActivityScheduleEntry":
      case "DeleteMiscScheduleEntry":
      case "DeleteTicketScheduleEntry":
      case "FindScheduleEntries":
      case "GetActivityScheduleEntry":
      case "GetMiscScheduleEntry":
      case "GetTicketScheduleEntry":
				$this->base_url_ext="SchedulingApi.asmx?wsdl";
				break;

      case "AddOrUpdateServiceTicketViaCompanyIdentifier":
      case "AddOrUpdateServiceTicketViaCompanyId":
      case "AddOrUpdateServiceTicketViaManagedIdentifier":
      case "AddOrUpdateServiceTicketManagedId":
      case "AddOrUpdateTicketNote":
      case "AddOrUpdateTicketProduct":
      case "AddServiceTicketToKnowledgebase":
      case "AddServiceTicketViaCompanyIdentifier":
      case "AddServiceTicketViaManagedIdentifier":
      case "AddTicketDocuments":
      case "AddTicketProduct":
      case "DeleteServiceTicket":
      case "DeleteTicketDocument":
      case "DeleteTicketProduct":
      case "FindServiceTicketCount":
      case "GetTicketCount":
      case "FindServiceTickets":
      case "GetDocument":
      case "GetServiceStatuses":
      case "GetServiceTicket":
      case "GetTicketDocuments":
      case "GetTicketProductList":
      case "LoadServiceTicket":
      case "SearchKnowledgebase":
      case "SearchKnowledgebaseCount":
      case "UpdateServiceTicketViaCompanyIdentifier":
      case "UpdateServiceTicketViaManagedIdentifier":
      case "UpdateTicketProduct":
				$this->base_url_ext="ServiceTicketApi.asmx?wsdl";
				break;

      case "GetConnectWiseVersion":
      case "GetConnectWiseVersionInfo":
      case "IsCloud":
				$this->base_url_ext="SystemApi.asmx?wsdl";
				break;

      case "AddOrUpdateTimeEntry":
      case "AddTimeEntry":
      case "DeleteTimeEntry":
      case "FindTimeEntries":
      case "GetTimeEntry":
      case "LoadTimeEntry":
      case "UpdateTimeEntry":
				$this->base_url_ext="TimeEntryApi.asmx?wsdl";
				break;
		}
	}
}
