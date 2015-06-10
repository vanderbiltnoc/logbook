<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	if(!$person->RackAdmin){
		// No soup for you.
		header('Location: '.redirect('concierge.php'));
		exit;
	}

	$header="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"><html>\n<head>\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n<title>Vanderbilt ITS Facilities Resource Concierge</title>\n<link rel=\"stylesheet\" href=\"css/inventory.php\" type=\"text/css\">\n<link rel=\"stylesheet\" href=\"elogs.css\" type=\"text/css\">\n<script src=\"js/jquery.min.js\" type=\"text/javascript\"></script>\n</head>\n<body>\n<div id=\"header\"></div>\n<div class=\"page\">\n";

        //Show me the goods
        echo $header;
        flush();  //immediate send header to browser to start the rendering

	include( "logmenu.inc.php" );

	$body="<div class=\"main\"><h2>ITS Network Operations Center</h2>\n<h3>Resource Concierge</h3>\n";
	$res = new Resource();

	if(@$_REQUEST["resourceid"] > 0){
  		$res->ResourceID = $_REQUEST["resourceid"];
		$res->GetResource();
	}

	if((@$_REQUEST["action"] == "Create") || (@$_REQUEST["action"] == "Update")){
		$res->ResourceID = $_REQUEST["resourceid"];
		$res->CategoryID = $_REQUEST["categoryid"];
		$res->Description = $_REQUEST["description"];
		$res->UniqueID = $_REQUEST["uniqueid"];
		@$res->Active = $_REQUEST["active"];

		if($_REQUEST["action"] == "Create"){
			if (sizeof($res->Description) > 0){
  				$res->CreateResource();
			}
		}else{
			$res->UpdateResource();
		}
	}
	$resourceList = $res->GetResources();

	$cat = new ResourceCategory();
	$catList = $cat->GetCategoryList();

	$body.="<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">\n<table><tr><th>Resource</th><td><input type=\"hidden\" name=\"action\" value=\"query\"><select name=\"resourceid\" onChange=\"form.submit()\">\n<option value=0>New Resource</option>\n";

	foreach( $resourceList as $resourceRow ) {
		if ( $res->ResourceID == $resourceRow->ResourceID )
			$selected = " selected";
		else
			$selected = "";

		$body.="<option value=$resourceRow->ResourceID $selected>$resourceRow->Description - $resourceRow->UniqueID</option>\n";
	}

	$body.="</select>\n</td></tr>\n<tr><th>Description</th><td><input type=\"text\" class=\"wide\" name=\"description\" size=\"50\" value=\"$res->Description\"></td></tr>\n<tr><th>Unique ID</th><td><input type=\"text\" class=\"wide\" name=\"uniqueid\" size=\"50\" value=\"$res->UniqueID\"></td></tr>\n<tr><th>Resource Category</th><td><input type=\"hidden\" name=\"action\" value=\"query\"><select name=\"categoryid\"><option value=0>Choose Category...</option>\n";

	foreach( $catList as $catRow ) {
		if ( $res->CategoryID == $catRow->CategoryID )
			$selected = " selected";
		else
			$selected = "";

		$body.="<option value=$catRow->CategoryID $selected>$catRow->Description</option>\n";
	}

	$a=($res->Active) ? "checked" : "";

	if ($res->ResourceID > 0){
		$action="Update";
	}else{
		$action="Create";
	}
	$body.="</select></td></tr>\n<tr><th>Active</th><td><input type=\"checkbox\" name=\"active\" $a value=\"1\"></td></tr>\n<tr><td colspan=\"2\"><input type=\"submit\" name=\"action\" value=\"$action\"></td></tr>\n</table>\n</form>\n</div>\n</div>\n</body>\n</html>\n";

	echo $body;

?>
