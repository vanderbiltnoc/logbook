<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$user = new VUser();

	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if ( ! $user->RackAdmin ) {
		printf( "<meta http-equiv='refresh' content='0; url=concierge.php'>" );
		end;
	}

        $header="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n<html>\n<head>\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n<title>Vanderbilt ITS Resource Concierge</title>\n<link rel=\"stylesheet\" href=\"css/inventory.php\" type=\"text/css\">\n<link rel=\"stylesheet\" href=\"elogs.css\" type=\"text/css\">\n<script src=\"js/jquery-min.js\" type=\"text/javascript\"></script>\n
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
				$.get('concierge.php',{rid: resourceid})
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
		</script>\n</head>\n<body>\n<div id=\"header\"></div>\n<div class=\"page\">\n";

        //Show me the goods
        echo $header;
        flush();  //immediate send header to browser to start the rendering

	include( "logmenu.inc.php" );

	$body="<div class=\"main\">\n<h2>ITS Network Operations Center</h2>\n<h3>Resource Administration</h3>\n";

	$res = new Resource();
	$resLog = new ResourceLog();
	$tmpUser = new VUser();

        // Expire reservations that are older than 15 minutes
        $res->ExpireReservations($facDB);
	
	$action = @$_REQUEST["action"];

	if($action == "CheckOut"){
		$res->ResourceID = $_REQUEST["resourceid"];
		$res->GetResource($facDB);
		// You can only check out a reserved resource
		if($res->Status == "Reserved"){
			$resLog->ResourceID = $_REQUEST["resourceid"];
			$resLog->CheckoutResource($facDB);
		}
	}elseif($action == "CheckIn"){
		$res->ResourceID = $_REQUEST["resourceid"];
		$res->GetResource($facDB);
		// You can only check in a checked out resource
		if($res->Status == "Out"){
			$resLog->ResourceID = $_REQUEST["resourceid"];
			$resLog->CheckinResource($facDB);
		}
	}elseif($action == "ClearReservation"){
                $res->ResourceID = $_REQUEST["resourceid"];
                $res->GetResource($facDB);
                // Makes it where can you can only clear a reservation and not a checked out item
                if($res->Status == "Reserved"){
	                $res->ClearReservation($facDB);
		}
        }

	$cat = new ResourceCategory();
	$catList = $cat->GetCategoryList($facDB);
	
	if(@$_REQUEST["categoryid"] > 0){
		$cat->CategoryID = $_REQUEST["categoryid"];
		$cat->GetCategory( $facDB );
	}
	
	$body.="<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">\n<table id=\"resourceadmin\">\n<tr>\n<th>Resource Category</th>\n<td><input type=\"hidden\" name=\"action\" value=\"query\"><select name=\"categoryid\" onChange=\"form.submit()\">\n<option value=0>All Categories</option>\n";

	foreach($catList as $catRow){
		if($cat->CategoryID == $catRow->CategoryID){
			$selected = " selected";
		}else{
			$selected = "";
		}
		$body.="<option value=$catRow->CategoryID $selected>$catRow->Description</option>\n";
	}
	$body.="</select></td>\n</tr>\n<tr>\n<th>Resources</th>\n<td><select name=\"resourceid\" id=\"resourceid\" size=\"6\">\n";
	
	$res->CategoryID = $cat->CategoryID;
	
	if($cat->CategoryID > 0){
		$resList = $res->GetActiveResourcesByCategory($facDB);
	}else{
		$resList = $res->GetActiveResources($facDB);
	}
	foreach($resList as $resourceRow){
		$reserved="";
		if($resourceRow->Status == "Available"){
			$class="available";
		}else{
			$resLog->ResourceID = $resourceRow->ResourceID;
			$resLog->GetCurrentStatus( $facDB );
	    
			$tmpUser->UserID = $resLog->VUNetID;
			$userName = $tmpUser->GetName( $facDB );

			$reserved=" [$userName return ".date("M j H:i", strtotime($resLog->EstimatedReturn))."]";
			if($resourceRow->Status == "Out"){
				$class="checkedout";
			}else{
				$class="reserved";
			}
		}
		$body.="<option value=\"$resourceRow->ResourceID\" class=\"$class\">$resourceRow->Description $reserved</option>\n";
	}

	$body.="</select></td>\n</tr>\n<tr>\n<th>Action</th>\n<td><input type=\"submit\" name=\"action\" value=\"CheckOut\">&nbsp;<input type=\"submit\" name=\"action\" value=\"CheckIn\">&nbsp;<input type=\"submit\" name=\"action\" value=\"ClearReservation\"></td>\n</tr>\n</table>\n</form>\n</div>\n</div>\n</body>\n</html>\n";

	echo $body;
?>
