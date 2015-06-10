<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	if(!$person->RackAdmin){
		// No soup for you.
		header('Location: '.redirect('concierge.php'));
		exit;
	}

	$res = new Resource();

	// Expire reservations that are older than 15 minutes
	$res->ExpireReservations();

	// AJAX returns
	if(isset($_GET['updatelist'])){
		echo buildresourcelist($res->GetActiveResources());
		exit;
	}
	// END AJAX

	function buildresourcelist($resList){
		$reqresid=(isset($_GET["resourceid"]))?$_GET["resourceid"]:'';

		$body='<select name="resourceid" id="resourceid" class="validate[required]" size="6">';
		foreach($resList as $rrow){
			$reserved="";

			// used in filling out the form details if something failed on the first go around
			$selected=($rrow->ResourceID==$reqresid)?' selected="selected"':'';

			if($rrow->Status == "Available"){
				$class="available";
			}else{
				$resLog=new ResourceLog();
				$resLog->ResourceID=$rrow->ResourceID;
				$resLog->GetCurrentStatus();

				$tmpUser=new VUser();
				$tmpUser->UserID=$resLog->VUNetID;
				$userName=$tmpUser->GetName();

				$rrow->Description.=" [$userName return ".date("M j H:i", strtotime($resLog->EstimatedReturn))."]";
				if($rrow->Status == "Out"){
					$class="checkedout";
				}else{
					$class="reserved";
				}
			}
			$body.="<option data-cat=$rrow->CategoryID value=\"$rrow->ResourceID\" class=\"$class\"$selected>$rrow->Description</option>\n";
		}
		$body.='</select>';

		return $body;
	}


        $header="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n<html>\n<head>\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n<title>Vanderbilt ITS Resource Concierge</title>\n<link rel=\"stylesheet\" href=\"css/inventory.php\" type=\"text/css\">\n<link rel=\"stylesheet\" href=\"elogs.css\" type=\"text/css\">\n
		<link rel=\"stylesheet\" href=\"css/jquery-ui.css\" type=\"text/css\">

		<script src=\"js/jquery.min.js\" type=\"text/javascript\"></script>\n
		<script src=\"js/jquery-ui.min.js\" type=\"text/javascript\"></script>\n
		<script type=\"text/javascript\">
			$(document).ready(function(){
				setInterval(function(){ updateList(); },60000);
			});
			function updateList(){
				var selected=0;
				$('#resourceid option').each(function(){
					if($(this)[0].selected){ selected=$(this).val(); }
				});
				$.get('',{updatelist:''}).done(function(data){
					var cat=$('select[name=categoryid]').val();
					if(cat!=0){
						$(data).find('option').each(function(){
							if($(this).data('cat')!=cat){ $(this).addClass('hide'); }
						});
					}
					var scrollpos=$('#resourceid').scrollTop();
					$('#resourceid').replaceWith(data).scrollTop(scrollpos);
					if(selected){ $('#resourceid').val(selected); }
				});
			}
		</script>\n</head>\n<body>\n<div id=\"header\"></div>\n<div class=\"page\">\n";

        //Show me the goods
        echo $header;
        flush();  //immediate send header to browser to start the rendering

	include( "logmenu.inc.php" );

	$body="<div class=\"main\">\n<h2>ITS Network Operations Center</h2>\n<h3>Resource Administration</h3>\n";

	$resLog = new ResourceLog();
	$tmpUser = new VUser();

	$action = @$_REQUEST["action"];

	if($action == "CheckOut"){
		$res->ResourceID = $_REQUEST["resourceid"];
		$res->GetResource();
		// You can only check out a reserved resource
		if($res->Status == "Reserved"){
			$resLog->ResourceID = $_REQUEST["resourceid"];
			$resLog->CheckoutResource();
			if(preg_match("/paid\ parking/i",$res->Description)){
				$resLog->CheckinResource();
			}
		}
	}elseif($action == "CheckIn"){
		$res->ResourceID = $_REQUEST["resourceid"];
		$res->GetResource();
		// You can only check in a checked out resource
		if($res->Status == "Out"){
			$resLog->ResourceID = $_REQUEST["resourceid"];
			$resLog->CheckinResource();
		}
	}elseif($action == "ClearReservation"){
                $res->ResourceID = $_REQUEST["resourceid"];
                $res->GetResource();
                // Makes it where can you can only clear a reservation and not a checked out item
                if($res->Status == "Reserved"){
	                $res->ClearReservation();
		}
        }

	$cat = new ResourceCategory();
	$catList = $cat->GetCategoryList();
	
	if(@$_REQUEST["categoryid"] > 0){
		$cat->CategoryID = $_REQUEST["categoryid"];
		$cat->GetCategory();
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
	$body.="</select></td>\n</tr>\n<tr>\n<th>Resources</th>\n<td>\n";
	
	$res->CategoryID = $cat->CategoryID;
	
	if($cat->CategoryID > 0){
		$resList = $res->GetActiveResourcesByCategory();
	}else{
		$resList = $res->GetActiveResources();
	}

	$body.=buildresourcelist($resList);

	$body.="</td>\n</tr>\n<tr>\n<th>Action</th>\n<td><input type=\"submit\" name=\"action\" value=\"CheckOut\">&nbsp;<input type=\"submit\" name=\"action\" value=\"CheckIn\">&nbsp;<input type=\"submit\" name=\"action\" value=\"ClearReservation\"></td>\n</tr>\n</table>\n</form>\n</div>\n</div>\n</body>\n</html>\n";

	echo $body;
?>
