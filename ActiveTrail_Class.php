<?php
@ini_set ("soap.wsdl_cache_enabled", "0" );
@ini_set("default_socket_timeout" , "300");
@ini_set("memory_limit","256M");

class MSSoapClient extends SoapClient {
	public $name_space;

	function __doRequest($request, $location, $action, $version, $one_way = 0) {
		$request = preg_replace ( '/<ns1:(\w+)/', '<$1 xmlns="' . $this->name_space . '"', $request, 1 );
		$s = array ('SOAP-ENV', 'xsd:', 'ns1:' );
		$r = array ('soap', '', '' );
		$request = str_ireplace ( $s, $r, $request );
		$request = str_replace ( 'xsi:type="ms_soap_comp"', 'xmlns="http://tempuri.org/"', $request );
        $request = str_replace ( '<WebCustomer>', '', $request );
        $request = str_replace ( '</WebCustomer>', '', $request );
		$request = str_replace ('<SOAP-ENC:Struct xsi:type="WebCustomer">', '<WebCustomer>', $request );        
		$request = str_replace ('</SOAP-ENC:Struct>', '</WebCustomer>', $request );				        
		return parent::__doRequest ( $request, $location, $action, $version );
	}
}

class Active_Trail {

	private $session_id;
	private $user;
	private $pass;
	public $client;
	private $wsdl_uri_ = "http://webapi.mymarketing.co.il/Messaging/MessagingService.asmx?WSDL";
	private $wsdl_uri_report = "http://webapi.mymarketing.co.il/reports/reportservice.asmx?WSDL";
    private $wsdl_uri_userservice = "http://webapi.mymarketing.co.il/users/userservice.asmx?WSDL";
    private $wsdl_uri_customer = "http://webapi.mymarketing.co.il/customers/customerService.asmx?WSDL";

	private $name_space = "http://tempuri.org/";

	function __construct($u, $p, $location = "") {
	
		
		if (isset ( $u ) && isset ( $p )) {
			$this->user = $u;
			$this->pass = $p;
            $this->_Login($location);
		}
	}

	private function _Connect( $location = "") {
		$wsdl_options = array ('features'=>SOAP_SINGLE_ELEMENT_ARRAYS, 'trace' => true, "exceptions" => 0, "user_agent"=> "some_string", 'connection_timeout' => '3600', 'classmap' => array ('webMessage' => "webMessage", 'GetPerformanceReport' => "GetPerformanceReport" ,'GetOverviewReport' => "GetOverviewReport", 'webCampaign' => "webMessage",'ArrayOfBodyPart' => "ArrayOfBodyPart", 'webCustomers' => 'webCustomers', 'WebCustomer' => 'WebCustomer') );

		unset ( $this->client );

		$uri = "wsdl_uri_$location";
		
		//Init the client
		$this->client = new MSSoapClient ( $this->$uri, $wsdl_options );
		$this->client->name_space = $this->name_space;

	}

	private function _Login($location = "") {
	
		$this->_Connect ($location);
		$this->_Auth_Header ();
		$response = $this->client->Login ();

		$this->session_id = $response->LoginResult;
		if ( $response->LoginResult == "" ) {
			//log_data("ERREUR : Erreur connection ou mauvais login");
			die( "ERREUR : Erreur connection ou mauvais login\n");
			exit;
		}
	}

	private function _Auth_Header() {
		$auth_header = new SoapHeader ( $this->name_space, 'AuthHeader', new AuthHeader ( $this->user, $this->pass, $this->session_id ) );
		$this->client->__setSoapHeaders ( array ($auth_header ) );
	}

	
	public function ImportCustomers($webCustomers,$groupId,$mailinglistName)
    {
        $this->_Connect('customer');
        $this->_Auth_Header();
        $param=new stdClass(); 
		
		
        $param->ImportCustomers->webCustomers=$webCustomers;
        $param->ImportCustomers->groupId=$groupId;
		$param->ImportCustomers->mailinglistName=$mailinglistName;

		$ImportCustomers=new SoapVar($param->ImportCustomers, SOAP_ENC_OBJECT, 'ms_soap_comp' );
		
		
		$response=$this->client->ImportCustomers(new SoapParam($ImportCustomers,"ImportCustomers"));
				
		return $response;
    }
	
	/* Function Create Campaign
	 * Set $campaign_name and $campaign_data (array)
	 * Get the campaign Id
	*/
	public function CreateCampaign($campaign_name, $campaign_data)
	{
        $this->_Connect ();
        $this->_Auth_Header ();

        $param = new stdClass ();
        $param->webCampaign = new webMessage ();
        $param->webCampaign->Subject = $campaign_data ['subject'];
        $param->webCampaign->From->FromName = $campaign_data ['from_name'];
        $param->webCampaign->From->FromEmail = $campaign_data ['from_email'];
        $param->webCampaign->Name = "$campaign_name";

        $ArrayOfBodyPart = new ArrayOfBodyPart ( );
        $ArrayOfBodyPart->BodyPart [] = new BodyPart ( 'utf-8', 'HTML', $campaign_data ['body_part'] );
        $param->webCampaign->BodyParts = $ArrayOfBodyPart;

        $CreateCampaign = new SoapVar ( $param, SOAP_ENC_OBJECT, 'ms_soap_comp' );

        $response = $this->client->CreateCampaign ( new SoapParam ( $CreateCampaign, "CreateCampaign" ) );

        if ($response->CreateCampaignResult > 0 && $campaign_data['language_id'] > 0){
	    	$response->LanguageID = $campaign_data['language_id'];
        }

        return $response;
    }

	// Imports an email address to the account
	// https://webapi.mymarketing.co.il/customers/customerservice.asmx?op=ImportEmail
    public function ImportEmail ($Groups, $Mailinglists, $Email){
        $this->_Connect ( 'customer' );
        $this->_Auth_Header ();
        $param = new stdClass ( );

        $param->ImportEmail->email = "$Email";
        $param->ImportEmail->groups = $Groups;
        $param->ImportEmail->mailinglists = $Mailinglists;

        $ImportEmail = new SoapVar ( $param->ImportEmail, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        $response = $this->client->ImportEmail ( new SoapParam ( $ImportEmail, "ImportEmail") );

        return $response;
    }
	
	/* Function Import Customers Email
	 * Set $GroupId and $Email (array)
	 * Get the response success
	*/
    public function ImportCustomersEmail($GroupID, $Emails)
	{
        $this->_Connect ( 'customer' );
        $this->_Auth_Header ();
        $param = new stdClass ( );

        $param->ImportCustomersEmail->emails = $Emails;
        $param->ImportCustomersEmail->groupId = $GroupID;
		$param->ImportCustomersEmail->groupId = $GroupID;

        $ImportCustomersEmail = new SoapVar ( $param->ImportCustomersEmail, SOAP_ENC_OBJECT, 'ms_soap_comp' );
		
			
        $response = $this->client->ImportCustomersEmail ( new SoapParam ( $ImportCustomersEmail, "ImportCustomersEmail") );

        return $response;
    }

	public function SendMessageToEmails ($from, $from_name, $to, $replyto, $subject, $content_html, $content_txt="") {

		//log_data("Send message from activetrail: from ".$from." to : ".$to." subject : ".$subject);

		$this->_Connect ();
		$this->_Auth_Header ();

		$param = new stdClass ( );
		$param->webMessage = new webMessage ( );

		$param->webMessage->Subject = $subject;
        $param->webMessage->From = new stdClass();
        $param->webMessage->From->FromName = $from_name;
		$param->webMessage->From->FromEmail = $from;
		$param->webMessage->ExternalMessageId = "15";
		$param->webMessage->LanguageId=35;
		$param->webMessage->UserPlaceholders= false;
		$param->webMessage->AddStatistics = false;
		$param->webMessage->AddAdvertisement = false;
		$param->webMessage->SignMessage = false;
		$param->webMessage->AddUnsubscribeLink = false;
		$param->webMessage->Note = '';
		$param->webMessage->AddPrintButton = false;
		$param->webMessage->Name = "Campagne depuis OVH";
		$param->webMessage->Priority = '2';

		$ArrayOfBodyPart = new ArrayOfBodyPart ( );
		$ArrayOfBodyPart->BodyPart [] = new BodyPart ( '', 'HTML', $content_html );
		if (! empty ( $content_txt ))
			$ArrayOfBodyPart->BodyPart [] = new BodyPart ( '', 'Text', $content_txt );

		$param->webMessage->BodyParts = $ArrayOfBodyPart;

		$ArrayEmail=new ListEmails($to);
		$param->emails=$ArrayEmail;

		$SendMessageToEmails = new SoapVar ( $param, SOAP_ENC_OBJECT, 'ms_soap_comp' );
		$response = $this->client->SendMessageToEmails ( new SoapParam ( $SendMessageToEmails, "SendMessageToEmails" ) );

		return $response;
	}
 
 public function SendCampaignById($campaign_id, $group_id){
        $this->_Connect ();
        $this->_Auth_Header ();

        $param = new stdClass ( );
        $param->SendCampaignById->campaignId = $campaign_id;
        $param->SendCampaignById->groups = $group_id;

        $SendCampaignById = new SoapVar ( $param->SendCampaignById, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        $response = $this->client->SendCampaignById ( new SoapParam ( $SendCampaignById, "SendCampaignById") );

        return $response;
    }


	public function GetCampaignByName($campaign_name){
		$this->_Connect ();
        $this->_Auth_Header ();

		$param->GetCampaignByName->campaignName = "$campaign_name";

		$GetCampaignByName = new SoapVar ( $param->GetCampaignByName, SOAP_ENC_OBJECT, 'ms_soap_comp' );
		$response = $this->client->GetCampaignByName ( new SoapParam ( $GetCampaignByName, "GetCampaignByName") );

		return $response;
	}

    public function CreateGroup($GroupName){
        $this->_Connect ( 'userservice' );
        $this->_Auth_Header ();
        $param = new stdClass ( );
        $param->CreateGroup->name = "$GroupName";

        $CreateGroup = new SoapVar ( $param->CreateGroup, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        $response = $this->client->CreateGroup ( new SoapParam ( $CreateGroup, "CreateGroup") );

        return $response->CreateGroupResult;
    }

    public function SetCampaignCategory($CampaignID, $CategoryID){
        $this->_Auth_Header ();
        $param = new stdClass ( );
        $param->SetCampaignCategory->campaignId = "$CampaignID";
        $param->SetCampaignCategory->contentCategoryId = "$CategoryID";

        $SetCampaignCategory = new SoapVar ( $param->SetCampaignCategory, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        $response = $this->client->SetCampaignCategory ( new SoapParam ( $SetCampaignCategory, "SetCampaignCategory") );

        return $response;
    }

    public function SetCampaignHeadersLanguage($CampaignID, $LanguageID){
        $this->_Auth_Header ();
        $param = new stdClass ( );
        $param->SetCampaignHeadersLanguage->campaignId = "$CampaignID";
        $param->SetCampaignHeadersLanguage->cultureId = "$LanguageID";
        echo "Setting for: $CampaignID, $LanguageID \n";
        $SetCampaignHeadersLanguage = new SoapVar ( $param->SetCampaignHeadersLanguage, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        $response = $this->client->SetCampaignHeadersLanguage ( new SoapParam ( $SetCampaignHeadersLanguage, "SetCampaignHeadersLanguage") );

        return $response;
    }

    public function ClearGroup($GroupID){
        $this->_Connect ( 'userservice' );
        $this->_Auth_Header ();
        $param = new stdClass ( );
        $param->ClearGroup->id = "$ClearGroup";

        $ClearGroup = new SoapVar ( $param->ClearGroup, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        $response = $this->client->ClearGroup ( new SoapParam ( $ClearGroup, "ClearGroup") );

        return $response;
    }

    public function AddToGroups($GroupID, $Emails, $Gname){
        $this->_Connect ( 'customer' );
        $this->_Auth_Header ();
        $param = new stdClass ( );

        $param->AddToGroups->emails = $Emails;
        #$param->AddToGroups->groupNames = $Gname;
        $param->AddToGroups->groupIds = $GroupID;


        $AddToGroups = new SoapVar ( $param->AddToGroups, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        $response = $this->client->AddToGroups ( new SoapParam ( $AddToGroups, "AddToGroups") );

        return $response;
    }

	// Function Checked
    // public function ImportCustomersEmail($GroupID, $Emails)
	// {
        // $this->_Connect ( 'customer' );
        // $this->_Auth_Header ();
        // $param = new stdClass ( );

        // $param->ImportCustomersEmail->emails = $Emails;
        // $param->ImportCustomersEmail->groupId = $GroupID;

        // $ImportCustomersEmail = new SoapVar ( $param->ImportCustomersEmail, SOAP_ENC_OBJECT, 'ms_soap_comp' );
        // $response = $this->client->ImportCustomersEmail ( new SoapParam ( $ImportCustomersEmail, "ImportCustomersEmail") );

        // return $response;
    // }

	


    /* Reporting Functions */
	public function GetOverviewReport($campaign, $dateStart, $dateEnd){
		$this->_Connect ( 'report' );
		$this->_Auth_Header ();
		$param = new stdClass ( );
		$param->GetOverviewReport = new GetOverviewReport ( );
		$param->GetOverviewReport->objectId = $campaign;
        $param->GetOverviewReport->fromDate = "$dateStart";
        $param->GetOverviewReport->toDate = "$dateEnd";

		$GetOverviewReport = new SoapVar ( $param->GetOverviewReport, SOAP_ENC_OBJECT, 'ms_soap_comp' );

		$response = $this->client->GetOverviewReport ( new SoapParam ( $GetOverviewReport, "GetOverviewReport") );
		return $response;
	}

	public function GetPerformanceReport($campaign){
		$this->_Connect ( 'report' );
		$this->_Auth_Header ();
		$param = new stdClass ( );
		$param->GetPerformanceReport = new GetPerformanceReport ( );
		$param->GetPerformanceReport->objectId = $campaign;

		$GetOverviewReport = new SoapVar ( $param->GetPerformanceReport, SOAP_ENC_OBJECT, 'ms_soap_comp' );

		$response = $this->client->GetPerformanceReport ( new SoapParam ( $GetPerformanceReport, "GetPerformanceReport") );
		return $response;
	}

    public function GetBouncesReport($dateStart, $dateEnd){
        $this->_Connect ( 'report' );
        $this->_Auth_Header ();
        $param = new stdClass ( );
        $param->GetBouncesReport = new GetBouncesReport ( );
        $param->GetBouncesReport->objectId = -1;
        $param->GetBouncesReport->fromDate = "$dateStart";
        $param->GetBouncesReport->toDate = "$dateEnd";

        $GetBouncesReport = new SoapVar ( $param->GetBouncesReport, SOAP_ENC_OBJECT, 'ms_soap_comp' );

        $response = $this->client->GetBouncesReport ( new SoapParam ( $GetBouncesReport, "GetBouncesReport") );
        return $response;
    }
    
	public function GetUnsubscribeReport($dateStart, $dateEnd){
        $this->_Connect ( 'report' );
        $this->_Auth_Header ();
        $param = new stdClass ( );
        $param->GetUnsubscribeReport = new GetUnsubscribeReport ( );
        $param->GetUnsubscribeReport->objectId = -1;
        $param->GetUnsubscribeReport->fromDate = "$dateStart";
        $param->GetUnsubscribeReport->toDate = "$dateEnd";

        $GetUnsubscribeReport = new SoapVar ( $param->GetUnsubscribeReport, SOAP_ENC_OBJECT, 'ms_soap_comp' );

        $response = $this->client->GetUnsubscribeReport ( new SoapParam ( $GetUnsubscribeReport, "GetUnsubscribeReport") );
        return $response;
    }


}

class CampaignByName {
 public $campaignName;
}

// Login credentials to be supplied as a SOAP header
class AuthHeader {
	/** @var string Login */
	public $Username;
	/** @var string Password */
	public $Password;
	/** @var string Token */
	public $Token;

	public function __construct($u, $p, $t) {
		$this->Username = $u;
		$this->Password = $p;
		$this->Token = $t;
	}
}

// Mail details
class WebMessage {
	/** @var string Subject */
	public $Subject;
	public $From;
	public $ExternalMessageId;
	public $BodyParts;
	public $UserPlaceholders = false;
	public $AddStatistics = true;
	public $AddAdvertisement = false;
	public $SignMessage = false;
	public $AddUnsubscribeLink = true;
	public $LanguageId = 119;
	public $AddPrintButton = true;
	#    public $Note;
	public $Name = "Test Campaign";
	#    public $Classification;
	#    public $Identifier;
	public $Priority = '2';
}

class webCustomers  {
	public $WebCustomer = array();
}


class WebCustomer {
 public  $City;
 public  $Email;
 public  $Fax;
 public  $FirstName;
 public  $LastName;
 public  $Phone1;
 public  $Phone2;
 public  $Street;
 public  $ZipCode;
 public  $Birthday;
 public  $Anniversary;
 public  $Ext1;
 public  $Ext2;
 public  $Ext3;
 public  $Ext4;
 public  $Ext5;
 public  $Ext6;
 public  $EncryptedExt1;
 public  $EncryptedExt2;
 public  $EncryptedExt3;
 public  $EncryptedExt4;
}




class GetOverviewReport {
	public $reportType = "OVERVIEW";
	public $objectType = "CAMPAIGN";
	public $objectId = "";
	public $classification = "-1";
	public $fromDate = "2008-12-01";
	public $toDate = "2009-01-30";
}

class GetBouncesReport {
    public $reportType = "BOUNCES";
    public $objectType = "MESSAGE";
    public $objectId = "-1";
    public $classification = "-1";
    public $bounceType = "-1";
    public $fromDate = "2008-12-01";
    public $toDate = "2009-01-30";
}

class GetUnsubscribeReport {
    public $reportType = "UNSUBSCRIBE";
    public $objectType = "MESSAGE";
    public $objectId = "-1";
    public $classification = "-1";
    public $bounceType = "-1";
    public $fromDate = "2008-12-01";
    public $toDate = "2009-01-30";
}

class GetPerformanceReport {
	public $reportType = "OVERVIEW";
	public $objectType = "CAMPAIGN";
	public $objectId = "";
	public $classification = "-1";
	public $fromDate = "2008-12-01";
	public $toDate = "2009-01-30";
}

class ListEmails {
	public $string;
	public function __construct($email) {
		$this->string = $email;
	}

}



class ArrayOfBodyPart {
	public $BodyPart = array ();
}

class BodyPart {
	public $BodyPartEncoding;
	public $BodyPartFormat;
	public $Body;

	public function __construct($bpe, $bpf, $b) {
		$this->BodyPartEncoding = $bpe;
		$this->BodyPartFormat = $bpf;
		$this->Body = $b;
	}
}

?>
