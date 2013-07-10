<?php 
/*
 * PHP/Web to Salesforce Lead
 *
 * @created 06/10/2013
 * @author Chris Cagle <admin@cagintranet.com>
 * 
 * This was ported from the code we used at DocumentLeads: http://documentleads.com
 *
 * Adapted from a few sources, most notably: 
 *	http://ahmeddirie.com/technology/web-development/salesforce-soap-api-and-php/
 *	http://wiki.developerforce.com/page/Getting_Started_with_the_Force.com_Toolkit_for_PHP
 *
 * NOTICE:
 * We had the issue that SOAP was not installed on our server. Since we were on MediaTemple, we followed these directions. If you are on a differnt host, you will have to find your own link.
 *	Is SOAP installed? - http://forums.cpanel.net/f5/php-soap-installed-166078.html
 *	Install SOAP on (mt) - https://kb.mediatemple.net/questions/1947/Configure+PHP+with+SOAP#dv_40
 *
 */

# Define variables
define('ABSPATH', '/path/to/your/installation');
define('ADMIN_EMAIL', 'support@domain.com');

# These variables would typically come from a database, but we show them here for completeness.
$salesforce['username'] = 'login@domain.com'; // Sign up for the free trial at salesforce.com
$salesforce['password'] = 'yoursalesforcepwd';
$salesforce['security_token'] = 'XXXXXXXXXXXXXX'; // Get it here: https://help.salesforce.com/help/doc/en/user_security_token.htm


require_once (ABSPATH.'salesforce-toolkit/SforcePartnerClient.php');

try {
	
	$wsdl = ABSPATH.'salesforce-toolkit/partner.wsdl.xml';
	
	# Create Salesforce connection
	$mySforceConnection = new SforcePartnerClient();
	$mySforceConnection->createConnection($wsdl);
	$mySforceConnection->login($salesforce['username'], $salesforce['password'].$salesforce['security_token']);

	
	# Salesforce Create New Lead 
	$records = array();
	$records[0] = new SObject();
	$records[0]->type = 'Lead';
	$records[0]->fields = array(
	    'FirstName'     => $row['first_name'],
	    'LastName'      => $row['last_name'],
	    'Title'      		=> $row['job_title'],
	    'LeadSource'    => 'DocumentLeads.com',
	    'Company'       => $row['organization'],
	    'NumberOfEmployees' => (int)$row['no_employees'],
	    'PostalCode' 		=> $row['postal_code'],
	    'State' 				=> $row['geo_state'],
	    'Email'         => $row['email'],
	    'Country'       => $row['country'],
	    'Phone'   			=> $row['phone']
	);
	
	# Submit the Lead to Salesforce 
	$response = $mySforceConnection->create($records);
	/*
	 * Note #1: my `$row` array variable above will need to be replaced with the lead details 
	 * you want to generate the lead with. Our variable was created from a MySQL query that got 
	 * the lead details that were just submitted.
	 * 
	 * Note #2: When checking in Salesforce to see if the leads have been generated, go to 
	 * 'Leads' then from the top dropdown choose "Today's Leads" then click "Go!"
	 *
	 */

} catch (Exception $e) {
	
	# Catch and send out email to support if there is an error
  $errmessage =  "Exception ".$e->faultstring."<br/><br/>\n";
	$errmessage .= "Last Request:<br/><br/>\n";
	$errmessage .= $mySforceConnection->getLastRequestHeaders();
	$errmessage .= "<br/><br/>\n";
	$errmessage .= $mySforceConnection->getLastRequest();
	$errmessage .= "<br/><br/>\n";
	$errmessage .= "Last Response:<br/><br/>\n";
	$errmessage .= $mySforceConnection->getLastResponseHeaders();
	$errmessage .= "<br/><br/>\n";
	$errmessage .= $mySforceConnection->getLastResponse();
	$status = sendmail(ADMIN_EMAIL,'ERROR! Salesforce Error', $errmessage);
}




# Sendmail function - Might be best to move this to your functions file, but can stay here if you want...
function sendmail($to,$subject,$message,$from='no-reply@yourdomain.com') {

	$headers  = "From: ".$from . PHP_EOL;
  $headers .= "Reply-To: ".$from . PHP_EOL;
  $headers .= "Return-Path: ".$from . PHP_EOL;
  $headers .= "MIME-Version: 1.0" . PHP_EOL;
  $headers .= "Content-type: text/html; charset=UTF-8" . PHP_EOL;
  
  if( mail($to,'=?UTF-8?B?'.base64_encode($subject).'?=',"$message",$headers) ) {
     return true;
  } else {
     return false;
  }
}