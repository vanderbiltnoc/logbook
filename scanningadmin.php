<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );
	require_once( '../swiftmailer/swift_required.php' );

 	$user=new VUser();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB); 
	$error="";

	if(!$user->RackAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// AJAX REQUESTS

	// This is to validate an email address and add it to an existing job
	if(isset($_POST['e'])&&isset($_POST['s'])){
		$tmpuser=new VUser();
		$tmpuser->Email=$_POST['e'];
		$check=$tmpuser->CheckScanUser($facDB);
		if($check){
			$scanjob=New ScanJob();
			$scanjob->ScanID=$_POST['s'];
			echo ''.($scanjob->AddAuthorizedUsers($facDB,$check))?'1':'0';
		}else{
			echo '0';
		}		
		exit;
	}

	// NO AJAX REQUESTS BELOW HERE

	if(isset($_POST['scanid'])&&isset($_POST['action'])){
		$scanjob=New ScanJob();
		$scanjob->ScanID=$_POST['scanid'];
		$scanjob->GetRecord($facDB);
		if($_POST['action']=="processscan"){
			if(isset($_POST['email'])&&isset($_POST['forms'])&&isset($_FILES['scanning'])){
				// If any port other than 25 is specified, assume encryption and authentication
				if($config->ParameterArray['SMTPPort']!= 25){
					$transport=Swift_SmtpTransport::newInstance()
						->setHost($config->ParameterArray['SMTPServer'])
						->setPort($config->ParameterArray['SMTPPort'])
						->setEncryption('ssl')
						->setUsername($config->ParameterArray['SMTPUser'])
						->setPassword($config->ParameterArray['SMTPPassword']);
				}else{
					$transport=Swift_SmtpTransport::newInstance()
						->setHost($config->ParameterArray['SMTPServer'])
						->setPort($config->ParameterArray['SMTPPort']);
				}
				$mailer=Swift_Mailer::newInstance($transport);

				$message=Swift_Message::NewInstance()->setSubject('Scanning Results');
				$message->setFrom(array('noc@vanderbilt.edu' => 'Network Operations Center'));
				$message->setBcc(array('noc@vanderbilt.edu' => 'Network Operations Center'));

				$validusers=array();
				foreach($_POST['email'] as $email){
					// Check and see if we know this user's vunet id.  If not add it to a lookup table.	
					$tmpuser=new VUser();
					$tmpuser->Email=$email;
					// If we get a vunetid returned then go ahead and email them.
					$tempid=$tmpuser->CheckScanUser($facDB);
					if($tempid!=""){
						$validusers[]=$tempid;
						try{
							$message->addTo($email);
						}catch(Swift_RfcComplianceException $e){
							$error.=$e->getMessage();
						}
					}
				}

				$attachmentlist="<ul>";
				$count=0;
				foreach($_FILES['scanning']['name'] as $key => $filename){
					if($_FILES['scanning']['error'][$key]==0){
						$attachmentlist.="<li>{$_FILES['scanning']['name'][$key]}</li>";
						$message->attach(Swift_Attachment::fromPath($_FILES['scanning']['tmp_name'][$key], $_FILES['scanning']['type'][$key])->setFilename($_FILES['scanning']['name'][$key]));
						$count++;
					}
				}
				$attachmentlist.="</ul>Total attachment count: $count";

				$logo='images/'.$config->ParameterArray["PDFLogoFile"];
				$logo=$message->embed(Swift_Image::fromPath($logo)->setFilename('logo.png'));

				$htmlMessage='<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>ITS Data Center Inventory</title></head><body><div id="header" style="padding: 5px 0;background: '.$config->ParameterArray["HeaderColor"].';"><center><img src="'.$logo.'"></center></div><div class="page"><p><h3>Scanning Results</h3>'."\n";

				$htmlMessage.="<p>The results of your scanning job are attached.  If you requested any prints made they are now available for pickup.</p><p>If you have any questions please contact the NOC at 322-2954</p>$attachmentlist</body></html>";

        	    $message->setBody($htmlMessage,'text/html');

				// update record here, before sending email.
				$scanjob->DateScanned=date('Y-m-d H:i');
				$scanjob->NOCAnalyst=$user->UserID;
				$scanjob->NumForms=$_POST['forms'];
				// don't attempt to update the record if there are no valid users.
				if(count($validusers)>0 && $scanjob->ScanningJob($validusers,$facDB)==1){
					try{
						$result=$mailer->send($message);
					}catch(Swift_RfcComplianceException $e){
						$error.="Send: ".$e->getMessage()."<br>\n";
					}catch(Swift_TransportException $e){
						$error.="Server: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
					}
				}else{
					$error.="Something broke, the record didn't update.  Were the email addresses valid vanderbilt.edu accounts?";
				}
			}
		}elseif($_POST['action']=="override"){
			$scanjob->Notes=$_POST['notes']." - $user->UserID";
			$scanjob->Override($facDB);
		}elseif($_POST['action']=="mail"){
			$scanjob->DatePickedUp=date('Y-m-d H:i');
			$scanjob->Authorized=1;
			$scanjob->Pickup=$user->UserID;
			$scanjob->Notes="Sent back via campus mail - $user->UserID";
			$scanjob->PickupJob($facDB);
			$scanjob->Override($facDB);
		}
	}

	$scanjob=New ScanJob();
	$jobs=$scanjob->GetOpenJobs($facDB);
	if(is_array($jobs)){
		//we have open jobs
		$open='<tr><th>ScanID</th><th>Date Submitted</th><th>Course Number</th><th>Section</th></tr>';
		foreach($jobs as $key => $row){
			$open.="<tr><td>{$row['ScanID']}</td><td>{$row['DateSubmitted']}</td><td>{$row['CourseNumber']}</td><td>{$row['Section']}</td></tr>";
			// need to add in some js to add input blanks for number of forms scanned, email addresses for contacts
		}
	}else{
		$open='<tr><td>There are no jobs waiting to be scanned.</td></tr>';
	}
	
	$jobs=$scanjob->GetWaitingJobs($facDB);
	if(is_array($jobs)){
		//we have open jobs
		$waiting='<tr><th>ScanID</th><th>Date Submitted</th><th>Date Scanned</th><th>Course Number</th><th>Section</th><th>Forms</th></tr>';
		foreach($jobs as $key => $row){
			$class=($row['Authorized']==1)?' class="noauth"':'';
			$scanjob->ScanID=$row['ScanID'];
			$users=$scanjob->GetAuthorizedUsers($facDB);
			if(is_array($users)){
				$title='Authorized Users:<br>';
				foreach($users as $key => $email){
					$title.="$email<br>";
				}
				$title.='"';
			}else{
				$title='No Authorized Users Listed"';
			}
			$title=($row['Authorized']==1)?' title="REASON FOR OVERRIDE REQUIRED<br><br>'.$title:' title="'.$title;
			$waiting.="<tr$class$title><td>{$row['ScanID']}</td><td>{$row['DateSubmitted']}</td><td>{$row['DateScanned']}</td><td>{$row['CourseNumber']}</td><td>{$row['Section']}</td><td>{$row['NumForms']}</td></tr>";
			// need to add in some js to add input blanks for number of forms scanned, email addresses for contacts
		}
	}else{
		$waiting='<tr><td>There are no scans waiting for pickup.</td></tr>';
	}

	$jobs=$scanjob->GetCompletedJobs($facDB);
	if(is_array($jobs)){
		//we have scans that have recently been picked up
		$pickedup='<tr><th>ScanID</th><th>Date Submitted</th><th>Date Scanned</th><th>Date Pickedup</th><th>Course Number</th><th>Section</th><th>Forms</th><th>Picked up by</th></tr>';
		foreach($jobs as $key => $row){
			($row['Authorized']==1)?$class=' class="noauth"':$class=' class="auth"';
			$pickedup.="<tr$class><td>{$row['ScanID']}</td><td>{$row['DateSubmitted']}</td><td>{$row['DateScanned']}</td><td>{$row['DatePickedUp']}</td><td>{$row['CourseNumber']}</td><td>{$row['Section']}</td><td>{$row['NumForms']}</td><td>{$row['Pickup']}</td></tr>";
			($row['Authorized']==1)?$pickedup.="<tr$class><td colspan=8>{$row['Notes']}</td></tr>":"";
			// need to add in some js to add input blanks for number of forms scanned, email addresses for contacts
		}
	}else{
		$pickedup='<tr><td>There have been no scans picked up in the last 24 hours.</td></tr>';
	}


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
  <script type="text/javascript" src="js/jquery-min.js"></script>
  <script type="text/javascript" src="js/jquery-ui-min.js"></script>
  <script type="text/javascript" src="js/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="js/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="js/jquery.MultiFile.pack.js"></script>
  <script type="text/javascript">
	$(document).ready(function() {
		$(document).tooltip({
			track: true
		});
		function closeit(){
			$(this).next('tr').toggle();
		}
		$('.scanme tr:first-child ~ tr').each(function(){
			$(this).click(function(){
				if(!($(this).hasClass('fileform'))){
					$(this).after('<tr><td colspan=4><form method="post" enctype="multipart/form-data"><div><label>File(s):</label><input type="file" name="scanning[]" class="multi"></div><div><label>Email Address(es):</label><div><div class="add" title="Add additional authorized user"><img id="newline" src="images/add.gif"></div><input type="text" name="email[]" class="validate[required]"></div></div><div><label>Forms</label><input type="text" name="forms" class="validate[required,custom[onlyNumberSp]]"><button type="submit">Email Results</button></div><input type="hidden" name="scanid" value="'+$(this).find('td:first-child').text()+'"><input type="hidden" name="action" value="processscan"></form></td></tr>').addClass('fileform');
					$(this).unbind('click');
					$(this).click(closeit);
					$(this).next('tr').find('form').validationEngine();
					$(this).next('tr').find('div:first-child input').MultiFile({
						STRING: {
							file: '<em title="Click to remove" onclick="$(this).parent().prev().click()">$file</em>',
							remove: '<img src="bin.gif" height="16" width="16" alt="x"/>'
						}
					});
					$(this).next('tr').find('div:first-child + div input').prev('div').click(function(){
						$(this).parent('div').clone().insertAfter($(this).parent('div')).children('div.add').html('<img src="images/del.gif">').click(function() {
							$(this).parent().remove();
						});
					});
				}
			});
		});
		$('.scanned tr:first-child ~ tr').each(function(){
			if($(this).hasClass('noauth')&&(!($(this).next('tr').hasClass('override')))){
				$(this).click(function(){
					$(this).after('<tr><td colspan=6><form method="post"><div><label>Reason for override</label><textarea name="notes"></textarea></div><div><button type="submit" name="action" value="override">Override</button></div><div><label>Send this back via campus mail?</label><button type="submit" name="action" value="mail">Yes</button><button type="reset" onclick="$(this).parents(\'tr\').hide();">No</button></div><input type="hidden" name="scanid" value="'+$(this).find('td:first-child').text()+'"></form></td></tr>');
					$(this).unbind('click');
					$(this).click(closeit);
				});
			}else{
				$(this).click(function(){
					var scanid=$(this).find('td:first-child').text();
					$(this).after('<tr><td colspan=6><form method="post"><div><label>Send this back via campus mail?</label><button type="submit" name="action" value="mail">Yes</button><button type="reset" onclick="$(this).parents(\'tr\').hide();">No</button></div><input type="hidden" name="scanid" value="'+scanid+'"></form></td></tr>');
					$(this).next('tr').find('div:first-child').after('<div><form method="post"><div><label>Add Email Address:</label><input type="text" name="email[]" class="validate[required]"><button>Add Email</button></div></form></div>');
					$(this).next('tr').find('div:first-child + div').each(function(){
						$(this).find('button').click(function(e){
							e.preventDefault();
							$.ajax({
								type: 'POST',
								data: 'e='+$(this).prev('input').val()+'&s='+scanid,
								success: function(data){
									if(data=='1'){
										console.log('added');
									}else{
										//display error
									}
								}
							});
						});
					});
					$(this).unbind('click');
					$(this).click(closeit);
				});
			};
		});
	});

  </script>


</head>
<body>
<div id="header"></div>
<div class="page scanningadmin">
<?php
	include( "logmenu.inc.php" );
?>
<div class="main">
<h2>ITS Network Operations Center</h2>
<h3>Scanning Administration</h3>
<h3 class="error"><?php echo $error; ?></h3>

<table class="scanme">
<caption>Waiting to be scanned</caption>
<?php echo $open; ?>
</table>

<table class="scanned">
<caption>Waiting to be picked up</caption>
<?php echo $waiting; ?>
</table>

<table class="pickedup">
<caption>Scan jobs picked up in the last 24 hours</caption>
<?php echo $pickedup; ?>
</table>
</div>

</div>
</body>
</html>

