<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	if(!$person->RackAdmin){
		// No soup for you.
		header('Location: '.redirect('concierge.php'));
		exit;
	}
	
 	$req = new DataCenterLog();

	$thisfile = $_SERVER["PHP_SELF"];
	
	$header="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n<html>\n<head>\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n<title>Vanderbilt ITS Resource Concierge</title>\n<link rel=\"stylesheet\" href=\"css/inventory.php\" type=\"text/css\">\n<link rel=\"stylesheet\" href=\"elogs.css\" type=\"text/css\">\n
<!--[if lt IE 9]<link rel=\"stylesheet\" href=\"iequirks.css\" type=\"text/css\"><![endif]-->\n
  <script src=\"js/jquery.min.js\" type=\"text/javascript\"></script>\n
  <script type=\"text/javascript\">
  function verify(name,newurl) {
           var agree = confirm( \"Are you sure that you want to mark \"+name+\" as exited from the data center?\" );
           if (agree)
              location.href=newurl;
  } 
  </script>\n</head>\n
<body>\n<div id=\"header\"></div>\n<div class=\"page dcentryadmin\">\n";

	//Show me the goods
	echo $header;
	flush();  //immediate send header to browser to start the rendering

	include( "logmenu.inc.php" );  // import common menu from external file
	
	$body="<div class=\"main\">\n<div>\n<h2>ITS Network Operations Center</h2>\n<h3>Data Center Entry Administration</h3>\n";

	$contact=new People();
	$dc = new DataCenter();

	$action = @$_REQUEST["action"];
	
	$user=new VUser();
	if($action=="Grant Access"){
	   $req->EntryID=$_REQUEST["reqid"];
	   $req->AuthorizedBy=$user->UserID;
	   $req->ApproveEntry();
	}elseif($action=="Deny Access"){
	   $req->EntryID=$_REQUEST["reqid"];
	   $req->RemoveRequest();
	}elseif($action=="exit"){
	   $req->EntryID=$_REQUEST["reqid"];
	   $req->CloseEntry();
	}
	
	if($action=="verify"){
	   $req->EntryID=$_REQUEST["reqid"];
	   $req->GetRequest();
	   $dc->DataCenterID=$req->DataCenterID;
	   $dc->GetDataCenterbyID();
	   $contact->UserID=$req->VUNetID;
	   $contact->GetPersonByUserID();
	   $user->UserID=$req->VUNetID;
	   $photoURL=$user->GetPhoto();

	   // Prevents a blank name from appearing on the confirmation screen, as well as providing another reminder that this person needs to be escorted.
	   if($contact->LastName==""){$contact->LastName="<span class=\"error\">User is not listed in contact manager";$contact->FirstName="Escort is required</span>";}
	   
	   $body.="<form method=\"post\" action=\"$thisfile\">\n<input type=\"hidden\" name=\"reqid\" value=\"$req->EntryID\">\n<table>\n<caption>Verify Request and Choose Action</caption>\n<tr>\n<th>Data Center</th>\n<td>$dc->Name</td>\n</tr>\n<tr>\n<th>Name</th>\n<td><img width=\"200\" src=\"$photoURL\" alt=\"$req->VUNetID\"><br>$contact->LastName, $contact->FirstName</td>\n</tr>\n<tr>\n<th>Guests</th>\n<td><textarea name=\"guests\" rows=\"4\" cols=\"55\" disabled>$req->GuestList</textarea></td>\n</tr>\n<tr>\n<th>Purpose</th>\n<td><input type=\"text\" name=\"reason\" value=\"$req->Reason\" size=\"72\" disabled></td>\n</tr>\n</table>\n<input type=\"submit\" name=\"action\" value=\"Grant Access\">\n<input type=\"submit\" name=\"action\" value=\"Deny Access\">\n</form>\n";
	}else{
  	   $reqList = $req->GetOpenRequests();
  	   $occList = $req->GetDCOccupants();
  	   $dcList = $dc->GetDCList();

	   $body.="<table>\n<caption>Pending Requests</caption>\n";
	   if(count($reqList)==0){$body.="<tr><td>There are no current requests for datacenter access.</td></tr>";}; // display message if nobody is present.
	   foreach($reqList as $reqRow){
		$contact->UserID = $reqRow->VUNetID;
		$contact->GetPersonByUserID();

		$dc->DataCenterID = $reqRow->DataCenterID;
		$dc->GetDataCenterbyID();

		$user->UserID = $reqRow->VUNetID;
		$photoURL = $user->GetPhoto();

		if($reqRow->EscortRequired){
		    $RequestorName = $user->GetName();
		    $class="dangerwillrobinson";
		}else{
		    $RequestorName = $contact->LastName . ", " . $contact->FirstName;
		    $class="noclass";
		}
		$body.="<tr><td class=\"stupidie\"><a href=\"$thisfile?action=verify&reqid=$reqRow->EntryID\"><img class=\"userimage\" src=\"$photoURL\" alt=\"$RequestorName\"></a></td><td><table><tr><th>Data Center:</th><td>$dc->Name</td></tr><tr class=\"$class\"><th>Name:</th><td>$RequestorName</td></tr><tr><th>Guests:</th><td>$reqRow->GuestList</td></tr><tr><th>Purpose:</th><td>$reqRow->Reason</td></tr></table></td></tr>\n"; 
  	   }
	   $body.="</table>\n<table>\n<caption>Current Occupants</caption>\n";
	   if(count($occList)==0){$body.="<tr><td>The datacenter is currently vacant</td></tr>";}; // display message if nobody is present.
  	   foreach($occList as $reqRow){
  	       $contact->UserID = $reqRow->VUNetID;
  	       $contact->GetContactByUserID();

	       $dc->DataCenterID = $reqRow->DataCenterID;
	       $dc->GetDataCenterByID();

	       $user->UserID = $reqRow->VUNetID;
	       $photoURL = $user->GetPhoto();

	       if($reqRow->EscortRequired){
		   $RequestorName = $user->GetName();
		   $class="dangerwillrobinson";
	       }else{
		   $RequestorName = $contact->LastName . ", " . $contact->FirstName;
		   $class="noclass";
    	       }
	       if(date("md")=="0401"){$a="Excuse";}else{$a="Purpose";} // Change purpose to excuse on April Fools
               $body.="<tr><td class=\"stupidie\"><a href=\"#\" onClick=\"verify('$RequestorName','$thisfile?action=exit&reqid=$reqRow->EntryID')\"><img class=\"userimage\" src=\"$photoURL\" alt=\"$RequestorName\"></a></td><td><table><tr><th>Data Center:</th><td>$dc->Name</td></tr><tr class=\"$class\"><th>Name:</th><td>$RequestorName</td></tr><tr><th>Entry Time:</th><td>".date("m/d/Y H:i", strtotime($reqRow->TimeIn))."</td></tr><tr><th>Guests:</th><td>$reqRow->GuestList</td></tr><tr><th>$a:</th><td>$reqRow->Reason</td></tr></table></td></tr>";
	   }
	   $body.="</table>\n";
	}
	$body.="<br>\n<h3>Click on a name to process entry/exit from the data center.</h3>\n</div>\n</div>\n</div>\n</body>\n</html>\n";

	echo $body;
?>
