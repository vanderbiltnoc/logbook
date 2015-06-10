<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

 	$user=new VUser();
	$scanjob=New ScanJob();

	// set $enfoce to 0 to restrict access to the NOC ip range
	// checkip() default behavior is to enforce the range check
	$enforce=0;
	function checkip($enforce=0){
		$upper="129.59.141.222";
		$lower="129.59.141.193";

		$lower_dec=(float)sprintf("%u",ip2long($lower));
		$upper_dec=(float)sprintf("%u",ip2long($upper));
		$ip_dec=(float)sprintf("%u",ip2long($_SERVER['REMOTE_ADDR']));

		if($enforce){
			return "1";
		}else{
			return(($ip_dec>=$lower_dec)&&($ip_dec<=$upper_dec));
		}
	}

	// password check
	if(isset($_POST['p']) && isset($_POST['v']) && checkip($enforce) ){
		$user->UserID=$_POST['v'];
		if($user->ValidateCredentials($_POST["p"])){
			echo '1';
		}
		exit;
	}

	$error="";

	if(isset($_POST['vunetid']) && isset($_POST['password'])){
		$user->UserID=$_POST['vunetid'];
		if($user->ValidateCredentials($_POST["password"])){
			if(isset($_POST['course']) && count($_POST['course']>0)){
				foreach($_POST['course'] as $key => $value){
					if($_POST['course'][$key]!="" && $_POST['section'][$key]!=""){
						// log drop off
						unset($_POST['password']);
						$scanjob->CourseNumber=$_POST['course'][$key];
						$scanjob->Section=$_POST['section'][$key];
						$scanjob->DateSubmitted=date('Y-m-d H:i');
						$scanjob->Dropoff=$_POST['vunetid'];
						$scanjob->Create();
						if($scanjob->ScanID){
							$error='Request logged.  Please hand originals to NOC staff.';
						}
					}
				}
			}
			if(isset($_POST['requestid'])){
				$user->CheckScanUser();
				$scanjob->ScanID=$_POST['requestid'];
				$pickup=$scanjob->GetWaitingJobs();
				if(count($pickup)>0){
					if(isset($pickup[$scanjob->ScanID])){
						$users=$scanjob->GetAuthorizedUsers();
						$scanjob=$pickup[$scanjob->ScanID];
						if(count($users)>0){
							if(isset($users[$user->CheckScanUser()])){
								//user was authorized, log
								$scanjob->DatePickedUp=date('Y-m-d H:i');
								$scanjob->Authorized=0;
								$scanjob->Pickup=$user->UserID;
								if($scanjob->PickupJob()){
									$error.="Please collect originals from NOC worker.";
								}else{
									$error.="Catostrophic failure! RUN!!";
								}
							}else{
								//not authorized log and request override
								$scanjob->DatePickedUp=date('Y-m-d H:i');
								$scanjob->Authorized=1;
								$scanjob->Pickup=$user->UserID;
								$scanjob->PickupJob();
								$error.="User not authorized for pickup.  Please see NOC staff for override approval.";
							}
						}	
					}else{
						$error.="Job already picked up. Job ID: {$_POST['requestid']}";
					}
				}else{
					$error.="Invalid Scan Job";
				}
				// check for user in ScanningUsers table, if not exist add it.
				// then check for scanjob number and verify that user is authorized to pickup scan.
			}
		}else{
			//login failed kick back an error
		}
	}

	$openjobs=$scanjob->GetWaitingJobs();
	$requestselector='<select id="requestid" name="requestid" class="validate[required]"><option></option>';
	foreach($openjobs as $jobid => $job){
		$requestselector.="<option value=$job->ScanID>$job->ScanID :: $job->CourseNumber - $job->Section</option>";
	}
	$requestselector.='</select>';

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Vanderbilt ITS Facilities Resource Concierge</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="elogs.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <script type="text/javascript" src="js/jquery.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui.min.js"></script>
  <script type="text/javascript" src="js/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="js/jquery.validationEngine.js"></script>

  <script type="text/javascript">
	$(document).ready(function() {
		$('#newline').click(function (){
			$(this).parents('tr').prev().clone().insertBefore($(this).parents('tr')).children('td.add').html('<img src="images/del.gif">').click(function() {
				$(this).parent().remove();
			});
		});
		$('#checkin').click(function (){
			$('h3.error').html('');
			if($('#vunetid').val()!="" && $('#password').val()!=""){
				$.post('', {v: $('#vunetid').val(), p: $('#password').val()}, function(data) {
					if(data.trim()==1){
						$('#scanform').submit();
					}else{
						$('h3.error').html('Invalid Login');
					}
				});
			}else{
				$('h3.error').html('Invalid Login');
			}
		});
		$('#pickup').click(function (){
			$('h3.error').html('');
			if($('#vunetid2').val()!="" && $('#password2').val()!=""){
				$.post('', {v: $('#vunetid2').val(), p: $('#password2').val()}, function(data) {
					if(data.trim()==1){
						$('#pickupform').submit();
					}else{
						$('h3.error').html('Invalid Login');
					}
				});
			}else{
				$('h3.error').html('Invalid Login');
			}
		});
		$('#scanform').validationEngine();
		$('#pickupform').validationEngine();
	});
  </script>


</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( "logmenu.inc.php" );
?>
<div class="main">
<h2>ITS Network Operations Center</h2>
<h3>Scanning Request Sign-In</h3>
<h3 class="error"><?php echo $error; ?></h3>


<?php if(checkip($enforce)){ ?>

<form method="post" id="scanform">
<table class="scanning">
	<tr>
		<th></th>
		<th class="add"></th>
		<th>Course Name/Number</th>
		<th>Section</th>
	</tr>
	<tr class="ir">
		<th></th>
		<td class="add"></td>
		<td><input type="text" name="course[]" class="validate[required]"></input></td>
		<td><input type="text" name="section[]" class="validate[required,custom[onlyNumberSp]]"></input></td>
	</tr>
	<tr>
		<th></th>
		<td class="add" colspan=3><img src="images/add.gif" id="newline"></td>
	</tr>
	<tr>
		<th>VUnetID</th>
		<td colspan=3><input type="text" class="validate[required]" id="vunetid" name="vunetid" value=""></td>
	</tr>
	<tr>
		<th>Password</th>
		<td colspan=3><input type="password" class="validate[required]" id="password" name="password"></td>
	</tr>
	<tr>
		<td colspan=4><button type="button" id="checkin">Check In</button></td>
	</tr>
</table>
</form>
<h3>Scanning Job Pickup</h3>
<form method="post" id="pickupform">
<table>
	<tr>
		<th>Request #</th>
		<td colspan=3><?php echo $requestselector; ?></td>
	</tr>
	<tr>
		<th>VUnetID</th>
		<td colspan=3><input type="text" class="validate[required]" id="vunetid2" name="vunetid" value=""></td>
	</tr>
	<tr>
		<th>Password</th>
		<td colspan=3><input type="password" class="validate[required]" id="password2" name="password"></td>
	</tr>
	<tr>
		<td colspan=4><button type="button" id="pickup">Pick Up</button></td>
	</tr>
</table>
</form>

<?php }else{ echo "This function only available in the NOC"; } ?>

</div>

</div>
</body>
</html>

