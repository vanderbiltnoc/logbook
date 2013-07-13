<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$user = new VUser();

	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	// Inititalize variables
        $thisfile = $_SERVER["PHP_SELF"];
        $reqpurpose="";
        $reqguest="";
        $reqid="";
	$statusmessage="";

        $header="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n<html>\n<head>\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n<title>Vanderbilt ITS Resource Concierge</title>\n<link rel=\"stylesheet\" href=\"css/inventory.php\" type=\"text/css\">\n<link rel=\"stylesheet\" href=\"elogs.css\" type=\"text/css\">\n
<link rel=\"stylesheet\" href=\"css/validationEngine.jquery.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\">\n<script src=\"js/jquery-min.js\" type=\"text/javascript\"></script>\n<script src=\"js/jquery.validationEngine-en.js\" type=\"text/javascript\"></script>\n<script src=\"js/jquery.validationEngine.js\" type=\"text/javascript\"></script>

<script type=\"text/javascript\">
	$(document).ready(function(){
		$(\"#datacenter\").validationEngine();
		// Clear the name of the last person that requested entry after 1 minute
		setTimeout(function(){
			$('h3 ~ h3').remove();
		},60000);
	});
</script>\n</head>\n<body>\n<div id=\"header\"></div>\n<div class=\"page\">\n";

        //Show me the goods
        echo $header;
        flush();  //immediate send header to browser to start the rendering

	include( "logmenu.inc.php" );

	$body="<div class=\"main\">\n<h2>ITS Network Operations Center</h2>\n<h3>Data Center Entry Request</h3>\n";

	$req = new DataCenterLog();
	$dc = new DataCenter();
	$contact = new Contact();
	
	$dcList = $dc->GetDCList( $facDB );
  
	$action = @$_REQUEST["action"];
  
	if ( $action == "Request" ) {
		// Validate the user
		$user->UserID = $_REQUEST["vunetid"];
		if($user->ValidateCredentials($facDB, $_REQUEST["password"])){
			$CN = $user->GetName($facDB);
			// Now see if they are authorized for entry
			$contact->UserID = $user->UserID;
			if($contact->GetContactByUserID($facDB)){
				$req->EscortRequired = 0;
				$body.="<h3>Thank you, $CN.  Your request has been received and you are authorized for unescorted access into the data center.  Please proceed to the data center and contact an analyst for entry.</h3>";
			}else{
				$req->EscortRequired = 1;
				$statusmessage="<h3 class=\"unauthorized\">Thank you, $CN.  You are not in the database for unescorted access to the data center.  Your request has been received, but must be approved by Data Center Staff and an authorized escort may be required to accompany you at all times within the data center.  If you feel that this message is an error, please contact Partner Support to ensure that your UserID is listed in the authorized contacts database.</h3>";
			}
			$req->VUNetID = $user->UserID;
			$req->DataCenterID = $_REQUEST["datacenterid"];
			$req->Reason = $_REQUEST["purpose"];
			$req->GuestList = $_REQUEST["guestlist"];
			$req->AddRequest( $facDB );
			// Set the event type to datacenter.  This can be changed for future uses.
			$req->EventType = "dcaccess";

                }else{  // If password fails refill the form
                        $reqpurpose=$_REQUEST["purpose"];
                        $reqguest=$_REQUEST["guestlist"];
                        $reqid=$_REQUEST["vunetid"];
                        $statusmessage="That isn't what I show your password as being.  Try again.";
                }
	}
	
	$body.="<h3 class=\"error\">$statusmessage</h3>\n<form id=\"datacenter\" action=\"$thisfile\" method=\"POST\">\n<h2>Each visitor to the data center who is an employee of Vanderbilt University and/or Medical Center must sign in under a separate and distinct request.</h2>\n<table><tr><th>Data Center</th><td><select name=\"datacenterid\">\n";

	foreach($dcList as $dcRow){
		if($dcRow->EntryLogging){
			$body.="<option value=$dcRow->DataCenterID>$dcRow->Name</option>\n";
		}
	}

	$body.="</select></td></tr>\n<tr><th>Purpose</th><td><input class=\"validate[required,length[4,60]]\" id=\"purpose\" type=\"text\" name=\"purpose\" size=\"60\" value=\"$reqpurpose\"></td></tr>\n<tr><th>Non-VU/VUMC Guest Names</th><td><input type=\"text\" name=\"guestlist\" size=\"60\" value=\"$reqguest\"><br>(Maximum of 6 allowed)</td></tr>\n<tr><th>UserID</th><td><input type=\"text\" class=\"validate[required]\" id=\"vunetid\" name=\"vunetid\" value=\"$reqid\"></td></tr>\n<tr><th>Password</th><td><input type=\"password\" class=\"validate[required]\" id=\"password\" name=\"password\"></td></tr>\n<tr><td colspan=\"2\"><input type=\"submit\" name=\"action\" value=\"Request\"></td></tr>\n</table>\n</form>\n</div>\n</div>\n</body>\n</html>";

	echo $body;
?>
