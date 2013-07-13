<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$user = new User();
	$req = new DataCenterLog();
	
  $user->VUNetID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if ( ! $user->RackAdmin ) {
		printf( "<meta http-equiv='refresh' content='0; url=concierge.php'>" );
		end;
	}
	
	$req->EntryID = $_REQUEST["reqid"];
	$req->GetRequest( $facDB );
	
	$user->VUNetID = $req->VUNetID;
	$photoURL = $user->GetPhoto( $facDB );
	
	printf( "%s", $photoURL );
	
?>
