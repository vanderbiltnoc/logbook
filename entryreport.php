<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

 	$user = new VUser();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if ( ! $user->RackAdmin ) {
		printf( "<meta http-equiv='refresh' content='0; url=concierge.php'>" );
		exit;
	}

 	if ( @$_REQUEST["report"] == "Submit" ) {
	   	define('FPDF_FONTPATH','../font/');
	    require('../fpdf.php');

      $startDate = @$_REQUEST["startdate"];
      $endDate = @$_REQUEST["enddate"];
      $datacenterid = @$_REQUEST["datacenterid"];      		

  		if ( $startDate == "" )
  		   $reportStart = "2010-06-01";
      else
	       $reportStart = $startDate;
  		   
	    if ( $endDate == "" )
	      $reportEnd = "now";
      else
        $reportEnd = $endDate;

      class PDF extends FPDF {
      	function Header() {
      		$this->Image('../css/masthead3.png',10,8,100);
      		$this->SetFont('Arial','B',12);
      		$this->Cell(120);
      		$this->Cell(30,20,'Information Technology Services',0,0,'C');
      		$this->Ln(20);
      		$this->SetFont( 'Arial','',10 );
      		$this->Cell( 50, 6, "Data Center Entry Report", 0, 1, "L" );
      		$this->Cell( 50, 6, "Report Date: " . date( "m/d/y" ), 0, 1, "L" );
       		$this->Ln(10);
      	}
      
      	function Footer() {
      	    	$this->SetY(-15);
          		$this->SetFont('Arial','I',8);
          		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
      	}
      }
      
      $pdf=new PDF();
      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('Arial','',10);
      
      $pdf->SetFillColor( 0, 0, 0 );
      $pdf->SetTextColor( 255 );
      $pdf->SetDrawColor( 128, 0, 0 );
      $pdf->SetLineWidth( .3 );
 
   		$pdf->Write( 6, "Reporting Period:  " . date( "m/d/Y", strtotime( $reportStart ) ) . " to " . date( "m/d/Y", strtotime( $reportEnd ) ) );		
      $pdf->Ln();
      
      $headerTags = array( "Location", "User", "Entry", "Exit", "Authorized By" );
      $cellWidths = array( 35, 35, 30, 30, 35 );
      
      for ( $col = 0; $col < count( $headerTags ); $col++ )
        $pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, "C", 1 );
      
      $pdf->Ln();
      
      $pdf->SetFont('Arial','',6 );
      
      $pdf->SetfillColor( 224, 235, 255 );
      $pdf->SetTextColor( 0 );
      
      $fill = 0;
      
      $startClause = " AND TimeIn>=\"" . date( "Y-m-d 00:00:00", strtotime( $reportStart ) ) . "\"";
      $endClause = " AND TimeIn<=\"" . date( "Y-m-d 23:59:59", strtotime( $reportEnd ) ) . "\"";
      
      $sql = "select a.*, b.Name as AuthorizeName, c.LastName, c.FirstName, d.Name as DataCenter from fac_DataCenterLog a, fac_User b, fac_Contact c, fac_DataCenter d where a.AuthorizedBy=b.UserID and a.VUNetID=c.UserID and a.DataCenterID=d.DataCenterID and a.DataCenterID=\"" . $datacenterid . "\"" . $startClause . $endClause . " order by RequestTime ASC";
      // $pdf->Write( 6, "SQL: " . $sql );
      $result = mysql_query( $sql, $facDB );
      
      while ( $logRow = mysql_fetch_array( $result ) ) {
   			$pdf->Cell( $cellWidths[0], 6, $logRow["DataCenter"], 0, 0, "L", $fill );
   			$pdf->Cell( $cellWidths[1], 6, sprintf( "%s, %s", $logRow["LastName"], $logRow["FirstName"] ), 0, 0, "L", $fill );
   			$pdf->Cell( $cellWidths[2], 6, date( "m/d/Y\nH:i:s", strtotime( $logRow["TimeIn"] ) ), 0, 0, "L", $fill );
   			$pdf->Cell( $cellWidths[3], 6, date( "m/d/Y\nH:i:s", strtotime( $logRow["TimeOut"] ) ), 0, 0, "L", $fill );
   			
   			if ( $logRow["EscortRequired"] )
   			  $pdf->Cell( $cellWidths[4], 6, $logRow["AuthorizeName"] . " (Escort)", 0, 0, "L", $fill );
   			else
   			  $pdf->Cell( $cellWidths[4], 6, $logRow["AuthorizeName"], 0, 0, "L", $fill );
   			  
   			$pdf->Ln();
   			
   			if ( $logRow["Reason"] != "" ) {
   			   $pdf->Cell( $cellWidths[0], 6, "Purpose:", 0, 0, "R", $fill );
           $pdf->MultiCell( array_sum( $cellWidths ) - $cellWidths[0], 6, $logRow["Reason"], 0, "L", $fill );
        }
   			   
        if ( $logRow["GuestList"] != "" ) {
   			   $pdf->Cell( $cellWidths[0], 6, "Guests:", 0, 0, "R", $fill );
           $pdf->MultiCell( array_sum( $cellWidths ) - $cellWidths[0], 6, $logRow["GuestList"], 0, "L", $fill );
        }
   			
   			$fill =! $fill;
      }
      
      
     	$pdf->Cell( array_sum( $cellWidths ), 0, "", "T" );
     	
     	$pdf->AddPage();
     	
     	$sql = "select count(a.EntryID) as Entries, a.VUNetID, b.LastName, b.FirstName from fac_DataCenterLog a, fac_Contact b where a.VUNetID=b.UserID and a.DataCenterID=\"" . $datacenterid . "\" group by a.VUNetID order by Entries DESC";
      $result = mysql_query( $sql, $facDB );
        
      $pdf->SetFont('Arial','',10 );
      $headerTags = array( "Entries", "Contact Name", "VUNetID" );
      $cellWidths = array( 25, 40, 35 );

      $pdf->SetFillColor( 0, 0, 0 );
      $pdf->SetTextColor( 255 );
      $pdf->SetDrawColor( 128, 0, 0 );
      $pdf->SetLineWidth( .3 );
            
      for ( $col = 0; $col < count( $headerTags ); $col++ )
          $pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, "C", 1 );
      
      $pdf->Ln();
      
      $pdf->SetfillColor( 224, 235, 255 );
      $pdf->SetTextColor( 0 );
      $pdf->SetFont('Arial','',8 );
      
      $pdf->SetfillColor( 224, 235, 255 );
      $pdf->SetTextColor( 0 );
      
      $fill = 0;
      
      while ( $logRow = mysql_fetch_array( $result ) ) {
            $pdf->Cell( $cellWidths[0], 6, $logRow["Entries"], "LRBT", 0, "L", $fill );
            $pdf->Cell( $cellWidths[1], 6, sprintf( "%s, %s", $logRow["LastName"], $logRow["FirstName"] ), "LRBT", 0, "L", $fill );
            $pdf->Cell( $cellWidths[2], 6, $logRow["VUNetID"], "LRBT", 0, "L", $fill );
            
            $pdf->Ln();
            $fill =! $fill;
      }

      header( "Cache-Control: no-store, no-cache, must-revalidate" );
      header( "Content-Description: File Transfer" );
      header( "Content-Transfer-Encoding: binary" );
      header( "Content-Type: application/pdf", true );
      header( "Content-Disposition: attachment; filename=\"logreport.pdf\"", true );

	    $pdf->Output();
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
  <script type="text/javascript" src="Calendar/calendar_us.js"></script>
  <link rel="stylesheet" href="Calendar/calendar.css" type="text/css">
  <script src="js/jquery.min.js" type="text/javascript"></script>
</head>
<body>
<div id="header"></div>
<?php
	include( "logmenu.inc.php" );
	
	$dc = new DataCenter();
	$dcList = $dc->GetDCList( $facDB );
?>
<div class="main">
<h2>Enter criteria for the report:</h2>
<form name="logreport" method="post" action="<?php print $_SERVER["PHP_SELF"]; ?>">
<table align="center" width="60%">
   <tr>
       <th style="text-align: right;">Data Center:</th>
       <td><select name="datacenterid">
<?php
	foreach( $dcList as $dcRow ) {
	  if ( $dcRow->EntryLogging )
			printf( "<option value=%d>%s</option>\n", $dcRow->DataCenterID, $dcRow->Name );
	}
?>
	</select></td>
   </tr>
   <tr>
       <th style="text-align: right;">Start Date:</th>
       <td><input type="text" id="startdate" name="startdate" value="" size=12 maxlength=12>
                  <script type="text/javascript">
                          new tcal ({
                              'formname': 'logreport',
                              'controlname': 'startdate'
                          });
                  </script></td>
   </tr>
   <tr>
       <th style="text-align: right;">End Date:</th>
       <td><input type="text" id="enddate" name="enddate" value="" size=12 maxlength=12>
                  <script type="text/javascript">
                          new tcal ({
                              'formname': 'logreport',
                              'controlname': 'enddate'
                          });
                  </script></td>
   </tr>
   <tr>
       <td colspan=2 align=center><input type="submit" name="report" value="Submit"></td>
   </tr>
</table>
</form>
</div>
</body>
</html>
       
