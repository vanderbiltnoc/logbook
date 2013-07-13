<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$user = new VUser();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	// Init Variables
	$thisfile = $_SERVER["PHP_SELF"];
	$reqpurpose="";
	$reqtime="";
	$reqid="";
	$reqresid="";
	$statusmessage="";
	$res = new Resource();

	// AJAX returns
	if(isset($_GET['rid'])){
		$res->ResourceID=$_GET["rid"];
		$res->GetResource($facDB);
		echo $res->Status;
		exit; // make sure we don't call any of the rest of the page
	}
	// END AJAX

        $header="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n<html>\n<head>\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n<title>Vanderbilt ITS Resource Concierge</title>\n<link rel=\"stylesheet\" href=\"css/inventory.php\" type=\"text/css\">\n<link rel=\"stylesheet\" href=\"elogs.css\" type=\"text/css\">\n<link rel=\"stylesheet\" href=\"css/validationEngine.jquery.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\">\n

		<link rel=\"stylesheet\" href=\"css/jquery-ui.css\" type=\"text/css\">

		<script src=\"js/jquery.min.js\" type=\"text/javascript\"></script>\n
		<script src=\"js/jquery-ui.min.js\" type=\"text/javascript\"></script>\n
		<script src=\"js/jquery.validationEngine-en.js\" type=\"text/javascript\"></script>\n
		<script src=\"js/jquery.validationEngine.js\" type=\"text/javascript\"></script>\n
		<script src=\"js/jquery.timepicker.js\" type=\"text/javascript\"></script>\n
		<script type=\"text/javascript\">
			$(document).ready(function(){
				$('#resourceid option.reserved').each(function(){
					updateTimer($(this).val());
				});
			});
			function updateTimer(resourceid){
				setTimeout(function(){
					updateStatus(resourceid);
				},60000);
			}
			function updateStatus(resourceid){
				$.get('',{rid: resourceid})
				.done(function(data){
					if(data.trim()=='Reserved'){
						updateTimer(resourceid);
					}else if(data.trim()=='Out'){
						$('#resourceid option[value='+resourceid+']').removeClass('reserved').addClass('checkedout');
					}else{
						var opt=$('#resourceid option[value='+resourceid+']');
						opt.text(opt.text().substring(0,opt.text().lastIndexOf('[')));
						opt.removeClass('reserved').addClass('available');
					}
				});
			}
		</script>
		<script type=\"text/javascript\">\n
		 $(function(){
		         $('#resourcecheckout').validationEngine({});
		         $('#returntime').datetimepicker({
	                 ampm: true,
                	 timeFormat: 'hh:mm TT',
					 stepMinute: 15,
        	         minuteMax: 45,
    	             minDate: 0,
	                 maxDate: 30
		         });
	    });
        </script>\n</head>\n<body>\n<div id=\"header\"></div><div class=\"page\">\n";

        //Show me the goods
        echo $header;
        flush();  //immediate send header to browser to start the rendering

	include( "logmenu.inc.php" );

	$body="<div class=\"main\">\n<h2>ITS Network Operations Center</h2>\n<h3>Resource Checkout Concierge</h3>\n";

	$resLog = new ResourceLog();
	$tmpUser = new VUser();

	// Expire reservations that are older than 15 minutes
	$res->ExpireReservations($facDB);
	
	$action = @$_REQUEST["action"];
	
	if ( $action == "Request" ) {
		$res->ResourceID = $_REQUEST["resourceid"];
		$res->GetResource( $facDB );
		
		// You can only check out an available resource
		if($res->Status == "Available"){
			// Validate the user
			$user->UserID = $_REQUEST["vunetid"];
			if ( $user->ValidateCredentials( $facDB, $_REQUEST["password"] ) ) {
				$resLog->ResourceID = $_REQUEST["resourceid"];
				$resLog->VUNetID = $user->UserID;
				$resLog->Note = $_REQUEST["purpose"];
				$resLog->EstimatedReturn = date( "Y-m-d H:i:s", strtotime( $_REQUEST["estimatedreturn"] ) );
				$resLog->RequestResource( $facDB );
			}else{  // If password fails refill the form
				$reqpurpose=$_REQUEST["purpose"];
				$reqtime=$_REQUEST["estimatedreturn"];
				$reqid=$_REQUEST["vunetid"];
				$reqresid=$_REQUEST["resourceid"];
				$statusmessage="I cannot find that username / password combination.  Try again.";
			}
		}elseif(($res->Status == "Out") || ($res->Status == "Reserved")){
                        $reqpurpose=$_REQUEST["purpose"];
                        $reqtime=$_REQUEST["estimatedreturn"];
                        $reqid=$_REQUEST["vunetid"];
			if($res->Status == "Out"){
				$statusnote="checked out";
				$statusnoteext=".";
			}else{ // else it is reserved
				$x=distanceOfTimeInWords($res->ReservationTTL($facDB));
				$statusnote="reserved";
				$statusnoteext=" and they have $x minutes to claim it.";
			}
			if(date("md")=="0401"){ // Add Rocky and Bullwinkle joke for April fools if someone tries to reserve a resource that is already in use or reserved
				$statusmessage="<div class=\"april\"><span>Hey rocky! Watch me pull a $res->Description outta my ...</span></div>";
			}else{
				$statusmessage="Silly moose, that has already been $statusnote by somebody else $statusnoteext";
			}

		}else{
			//Put any extra notices here after a successful reservation.
		}
	} 
	$cat = new ResourceCategory();
	$catList = $cat->GetCategoryList($facDB);
	
	if(@$_REQUEST["categoryid"] > 0){
		$cat->CategoryID = $_REQUEST["categoryid"];
		$cat->GetCategory($facDB);
	}
	
	$body.="<h3 class=\"error\">$statusmessage</h3><form action=\"$thisfile\" id=\"resourcecheckout\" method=\"POST\">\n<table>\n<tr>\n<th>Resource Category</th>\n<td><input type=\"hidden\" name=\"action\" value=\"query\"><select name=\"categoryid\" onChange=\"form.submit()\">\n<option value=0>All Categories</option>\n";

	foreach( $catList as $catRow ) {
		if ( $cat->CategoryID == $catRow->CategoryID ){
			$selected = " selected";
		}else{
			$selected = "";
		}
		$body.="<option value=$catRow->CategoryID $selected>$catRow->Description</option>\n";
	}
	$body.="</select></td>\n</tr>\n<tr>\n<th>Resources</th>\n<td><select name=\"resourceid\" id=\"resourceid\" class=\"validate[required]\" size=\"6\">\n";
	
	$res->CategoryID = $cat->CategoryID;
	
	if ($cat->CategoryID > 0){
		$resList = $res->GetActiveResourcesByCategory($facDB);
	}else{
		$resList = $res->GetActiveResources($facDB);
	}
	foreach($resList as $resourceRow){
		$reserved="";

		// used in filling out the form details if something failed on the first go around
		if($resourceRow->ResourceID==$reqresid){$selected="selected=\"selected\"";}else{$selected="";}

		if($resourceRow->Status == "Available"){
			$class="available";
		}else{
			$resLog->ResourceID = $resourceRow->ResourceID;
			$resLog->GetCurrentStatus( $facDB );

			$tmpUser->UserID = $resLog->VUNetID;
			$userName = $tmpUser->GetName($facDB);

			$reserved=" [$userName return ".date("M j H:i", strtotime($resLog->EstimatedReturn))."]";
			if($resourceRow->Status == "Out"){
				$class="checkedout";
			}else{
				$class="reserved";
			}
		}
		$body.="<option value=\"$resourceRow->ResourceID\" class=\"$class\" $selected>$resourceRow->Description $reserved</option>\n";
	}
	$body.="</select></td>\n</tr>\n<tr>\n<th>Purpose</th>\n<td><input class=\"validate[required,length[4,60]] wide\" id=\"purpose\" type=\"text\" name=\"purpose\" size=\"60\" value=\"$reqpurpose\"></td>\n</tr>\n<tr>\n<th>Estimated Return</th>\n<td><input class=\"validate[required] wide\" id=\"returntime\" type=\"text\" name=\"estimatedreturn\" size=\"20\" value=\"$reqtime\"></td>\n</tr>\n<tr>\n<th>VUNetID</th>\n<td><input type=\"text\" class=\"validate[required]\" id=\"vunetid\" name=\"vunetid\" value=\"$reqid\"></td>\n</tr>\n<tr>\n<th>Password</th>\n<td><input type=\"password\" class=\"validate[required]\" id=\"password\" name=\"password\"></td>\n</tr>\n<tr>\n<td colspan=\"2\"><input type=\"submit\" name=\"action\" value=\"Request\"></td>\n</tr>\n</table>\n<span class=\"notice\">Resource reservations are valid for 15 minutes.  Please pick up your requested resource promptly.</span>\n</form>\n</div>\n</div>\n</body>\n</html>\n";

	echo $body;
?>
