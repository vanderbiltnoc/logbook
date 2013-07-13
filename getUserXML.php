<?php

	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$user = new User();
	$contact = new Contact();

	$user->VUNetID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if ( ! $user->RackAdmin ) {
		printf( "<meta http-equiv='refresh' content='0; url=concierge.php'>" );
		end;
	}

	header( "Content-type: text/xml" );
	$user->VUNetID = $_REQUEST["vunetid"];
	$contact->VUNetID = $user->VUNetID;
	$contact->GetContactByVUNetID( $facDB );
	$photoURL = $user->GetPhoto( $facDB );

	echo '<?xml version="1.0" encoding="ISO-8859-1"?>';
	printf( "<person>\n" );
	printf( "<vunetid>%s</vunetid>\n", $contact->VUNetID );
	printf( "<lastname>%s</lastname>\n", $contact->LastName );
	printf( "<firstname>%s</firstname>\n", $contact->FirstName );
	printf( "<department>%s</department>\n", "ITS" );
	printf( "<photo>%s</photo>\n", $photoURL );
	printf( "</person>\n" );
?>
