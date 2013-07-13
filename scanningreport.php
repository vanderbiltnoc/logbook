<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

 	$user = new VUser();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if(!$user->RackAdmin){
		header('Location: '.redirect("concierge.php"));
		exit;
	}

	// AJAX REQUESTS

	// course number autocomplete queries
	if(isset($_GET['c'])){
		$courselist=array();
		$results=mysql_query("SELECT DISTINCT CourseNumber FROM fac_ScanningLog WHERE CourseNumber LIKE '%".addslashes($_GET['c'])."%';", $facDB);
		while($row=mysql_fetch_row($results)){
			$courselist[]=$row[0];
		}
	    header('Content-Type: application/json');
    	echo json_encode($courselist);
		exit;
	}

	// vunetid autocomplete queries
	if(isset($_GET['v'])){
		$courselist=array();
		$results=mysql_query("SELECT DISTINCT vunetid FROM fac_ScanningUsers WHERE vunetid LIKE '%".addslashes($_GET['v'])."%';", $facDB);
		while($row=mysql_fetch_row($results)){
			$courselist[]=$row[0];
		}
	    header('Content-Type: application/json');
    	echo json_encode($courselist);
		exit;
	}

	// search and respond with results for hidden div
	if(isset($_POST['action']) && $_POST['action']=="search"){
		// build sql query based on parameters submitted

		$staticsql='SELECT * FROM `fac_ScanningLog`';		
		$sql="";
		// CourseNumber
		if(isset($_POST['dropcourse']) && $_POST['dropcourse']!=''){
			$sql.=" AND CourseNumber LIKE '%".addslashes($_POST['dropcourse'])."%'";
		}elseif(isset($_POST['pickcourse']) && $_POST['pickcourse']!=''){
			$sql.=" AND CourseNumber LIKE '%".addslashes($_POST['pickcourse'])."%'";
		}
		
		// NOC Analyst
		if(isset($_POST['nocid']) && $_POST['nocid']!=''){
			$sql.=" AND NOCAnalyst LIKE '%".addslashes($_POST['nocid'])."%'";
		}

		// Person that dropped off
		if(isset($_POST['dropid']) && $_POST['dropid']!=''){
			$sql.=" AND Dropoff LIKE '%".addslashes($_POST['dropid'])."%'";
		}

		// Picked up by
		if(isset($_POST['pickid']) && $_POST['pickid']!=''){
			$sql.=" AND Pickup LIKE '%".addslashes($_POST['pickid'])."%'";
		}

		// Date dropped off
		if(isset($_POST['dropdate1']) && $_POST['dropdate1']!=''){
			$sql.=" AND DateSubmitted BETWEEN '".date('Y-m-d H:i', strtotime($_POST['dropdate1']))."' AND '".date('Y-m-d H:i', strtotime($_POST['dropdate2'].'+23 hours 59 minutes'))."'";
		}

		// Date picked up
		if(isset($_POST['pickdate1']) && $_POST['pickdate1']!=''){
			$sql.=" AND DatePickedUp BETWEEN '".date('Y-m-d H:i', strtotime($_POST['pickdate1']))."' AND '".date('Y-m-d H:i', strtotime($_POST['pickdate2'].'+23 hours 59 minutes'))."'";
		}

		// Correct syntax of sql filters
		$sql=preg_replace('/^ AND/', ' WHERE', $sql, 1);
		$sql=$staticsql.$sql;

		// Search
		$results=mysql_query($sql,$facDB);

		// Output
		if(mysql_num_rows($results)>0){
			$pickedup='<table><tr><th>ScanID</th><th>Date Submitted</th><th>Date Scanned</th><th>Date Pickedup</th><th>Course Number</th><th>Section</th><th>Forms</th><th>Picked up by</th></tr>';
			while($row=mysql_fetch_assoc($results)){
				if($row['Authorized']==1){
					$class=' class="noauth"';
				}elseif($row['Authorized']=='0'){
					$class=' class="auth"';
				}else{
					$class='';
				}
				$pickedup.="<tr$class><td>{$row['ScanID']}</td><td>{$row['DateSubmitted']}</td><td>{$row['DateScanned']}</td><td>{$row['DatePickedUp']}</td><td>{$row['CourseNumber']}</td><td>{$row['Section']}</td><td>{$row['NumForms']}</td><td>{$row['Pickup']}</td></tr>";
				($row['Authorized']==1)?$pickedup.="<tr$class><td colspan=8>{$row['Notes']}</td></tr>":"";
				// need to add in some js to add input blanks for number of forms scanned, email addresses for contacts
			}
			$pickedup.="</table>";
			echo $pickedup;
		}else{
			echo 'No search results.';
		}
		exit;
	}
	// END - AJAX REQUESTS

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
		$('.searchcourse').autocomplete({
			minLength: 0,
			autoFocus: true,
			source: function(req, add){
				$.getJSON('scanningreport.php', {c: req.term}, function(data){
					var suggestions=[];
					$.each(data, function(i,val){
						suggestions.push(val);
					});
					add(suggestions);
				});
			},
			change: function(){
				if($(this).attr('id')=='pickcourse'){
					if($(this).val()!=''){
						$('#dropcourse').prop('disabled', true).val($(this).val());
					}else{
						$('#dropcourse').prop('disabled', false).val('');
					}
				}else{
					if($(this).val()!=''){
						$('#pickcourse').prop('disabled', true).val($(this).val());
					}else{
						$('#pickcourse').prop('disabled', false).val('');
					}

				}
			}
		});
		$('.searchid').autocomplete({
			minLength: 0,
			autoFocus: true,
			source: function(req, add){
				$.getJSON('scanningreport.php', {v: req.term}, function(data){
					var suggestions=[];
					$.each(data, function(i,val){
						suggestions.push(val);
					});
					add(suggestions);
				});
			}
		});
		$('input[name*="date"]').datepicker();
		$('input[name="dropdate1"]').change(function(){
			$('input[name="dropdate2"]').val($(this).val()).datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
			$('input[name="pickdate1"]').datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
			$('input[name="pickdate2"]').datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
		});
		$('input[name="pickdate1"]').change(function(){
			$('input[name="pickdate2"]').val($(this).val()).datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
			$('input[name="dropdate1"]').datepicker("destroy").datepicker({ maxDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
			$('input[name="dropdate2"]').datepicker("destroy").datepicker({ maxDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
		});
		$('#search').click(function(){
			$.ajax({
				type: "POST",
				data: $('form[name="logreport"]').serializeArray(),
				success: function(data){
					$('div.results').html(data);

				}
			});
		});
		$('.results').css({'max-width':$('.main').width()+'px'});
	});
  </script>
</head>
<body>
<div id="header"></div>
<?php
	include( "logmenu.inc.php" );
?>
<div class="main report">
<h2>Enter criteria for the report:</h2>
<form name="logreport" method="post"> 
<table>
	<tr>
		<th></th><th>Course Number</th><th>Start Date</th><th>End Date</th><th>VUNetID</th>
	</tr>
	<tr>
		<th>Dropped off</th><td><input type="text" name="dropcourse" id="dropcourse" class="searchcourse"></td><td><input type="text" name="dropdate1"></td><td><input type="text" name="dropdate2"></td><td><input type="text" name="dropid" class="searchid"></td>
	</tr>
	<tr>
		<th>Picked up</th><td><div><input type="text" name="pickcourse" id="pickcourse" class="searchcourse"></div></td><td><input type="text" name="pickdate1"></td><td><input type="text" name="pickdate2"></td><td><input type="text" name="pickid" class="searchid"></td>
	</tr>
	<tr>
		<th>Processed</th><td colspan=3></td><td><input type="text" name="nocid" class="searchid"></td>
	</tr>
	<tr>
		<td colspan=5><button type="button" id="search">Search</button><input type="hidden" name="action" value="search"></td>
	</tr>
</table>
<p>You do not need to fill out every box to search.  The options are there if they are ever needed.</p>
</form>
<div class="results">

</div>
</div>
</body>
</html>
       
