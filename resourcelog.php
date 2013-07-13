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

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Vanderbilt ITS Facilities Resource Concierge</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="elogs.css" type="text/css">
  <script src="js/jquery-min.js" type="text/javascript"></script>
</head>
<body>
<div id="header"></div>
<?php
	include( "logmenu.inc.php" );

?>
