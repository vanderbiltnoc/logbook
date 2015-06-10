<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	if(!$person->RackAdmin){
		// No soup for you.
		header('Location: '.redirect('concierge.php'));
		exit;
	}

// AJAX

	if(isset($_GET['st'])){
		$validsearchkeys=array('Resource','VUNetID','Category');
		$st=(isset($_GET['st']) && in_array($_GET['st'],$validsearchkeys))?$_GET['st']:'Resource';

		switch($st){
			case 'Resource':
				$st="fac_Resource.Description";
				break;
			case 'Category':
				$st="fac_ResourceCategory.Description";
				break;
			default:
		}

		$startClause=($_GET['StartDate']!='')?" AND RequestedTime>=\"".date("Y-m-d 00:00:00",strtotime($_GET['StartDate']))."\"":"";
		$endClause=($_GET['EndDate']!='')?" AND RequestedTime<=\"".date("Y-m-d 23:59:59",strtotime($_GET['EndDate']))."\"":"";

		$nocheckout=($_GET['nocheckout']=='include')?'':' AND Timeout != "0000-00-00 00:00:00" ';
		$searchresults=array();
		if(isset($_GET['autocomplete'])){
			$sql="
SELECT DISTINCT $st FROM fac_ResourceLog
LEFT JOIN fac_Resource ON fac_ResourceLog.ResourceID = fac_Resource.ResourceID
LEFT JOIN fac_ResourceCategory ON fac_Resource.CategoryID = fac_ResourceCategory.CategoryID
WHERE fac_Resource.Description LIKE '%".addslashes($_GET['Resource'])."%'
AND VUNetID LIKE '%".addslashes($_GET['VUNetID'])."%'$nocheckout$startClause$endClause
AND fac_ResourceCategory.CategoryID LIKE '%".addslashes($_GET['Category'])."%'
ORDER BY $st ASC;";
		}else{
			$sql="
SELECT VUNetID, fac_Resource.Description AS Resource, fac_ResourceCategory.Description AS Category, Note, RequestedTime, TimeOut, EstimatedReturn, ActualReturn FROM fac_ResourceLog
LEFT JOIN fac_Resource ON fac_ResourceLog.ResourceID = fac_Resource.ResourceID
LEFT JOIN fac_ResourceCategory ON fac_Resource.CategoryID = fac_ResourceCategory.CategoryID
WHERE fac_Resource.Description LIKE '%".addslashes($_GET['Resource'])."%'
AND VUNetID LIKE '%".addslashes($_GET['VUNetID'])."%'$nocheckout$startClause$endClause
AND fac_ResourceCategory.CategoryID LIKE '%".addslashes($_GET['Category'])."%'
ORDER BY fac_ResourceLog.TimeOut ASC LIMIT 500;";
		}
		foreach($dbh->query($sql) as $row){
			$searchresults[]=(isset($_GET['autocomplete']))?$row[0]:$row;
		}

		$result=$searchresults;

		header('Content-Type: application/json');
		echo json_encode($result);
		exit;
	}


	$cat=new ResourceCategory();
	$catList=$cat->GetCategoryList();

// END - Ajax
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
  <link rel="stylesheet" href="css/jquery.dataTables.css" type="text/css">
  <link rel="stylesheet" href="css/ColVis.css" type="text/css">
  <link rel="stylesheet" href="css/TableTools.css" type="text/css">
  <script type="text/javascript" src="js/jquery.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui.min.js"></script>
  <script type="text/javascript" src="js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="js/ColVis.min.js"></script>
  <script type="text/javascript" src="js/TableTools.min.js"></script>

  <style type="text/css">
	table.dataTable tr.odd { background-color: #444444;}
	table.dataTable tr.even td { color: black;}
	table.dataTable tr.even td.sorting_1 { color: black; background-color: #ffde88;}
	table.dataTable tr.odd  td.sorting_1 { color: black; background-color: #ffcc66;}
  </style>

  <script type="text/javascript">
	$(document).ready(function() {
		$('input[name*="Date"]').datepicker();
		$('input[name="StartDate"]').change(function(){
			if(this.value > $('input[name="EndDate"]').val() || $('input[name="EndDate"]').val() == ''){
				$('input[name="EndDate"]').val($(this).val());
			}
			$('input[name="EndDate"]').datepicker("destroy").datepicker({ 
					minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) 
				});
		});

		$('#btnsearch').click(function(e){
			var st = $('#resourcesearch').serialize();
			st += '&st=';
			$.getJSON('', st, function(data){
				$('#target').html(BuildTable(data));
				$('#target > table').dataTable({
					"iDisplayLength": 25,
					"sDom": 'CT<"clear">lfrtip',
					"oTableTools": {
						"sSwfPath": "scripts/copy_csv_xls.swf",
						"aButtons": []
					}
				});
				if($('div.main').outerWidth() < $('table.dataTable').outerWidth()){
					$('div.main').outerWidth($('table.dataTable').outerWidth()+10);
				}
			});
		});

		$('#resourcesearch input:not([name*="Date"])').autocomplete({
			minLength: 0,
			autoFocus: true,
			source: function(req, add){
				var st = $('#resourcesearch').serialize();
				st += '&autocomplete='+'';
				st += '&st='+this.element[0].name;
				$.getJSON('', st, function(data){
					var suggestions=[];
					$.each(data, function(i,val){
						suggestions.push(val);
					});
					add(suggestions);
				});
			}
		}).click(function(){
			if(!this.value){
				$(this).autocomplete('search');
			}
		}).on('keyup', function(e){
			if(e.keyCode==10 || e.keyCode==13){
				$('#btnsearch').click();
			}
		});

		$('#btnreport').click(function(){
			$('#btnsearch').click();

			var form=$('#resourcesearch');
			var dataset=form.serializeArray();
			var download = new iframeform('report_resourcelog.php');

			download.addParameter('st','');
			for(var x in dataset){
				download.addParameter(dataset[x].name,dataset[x].value);
			}

			download.send();
		});
	});

	function BuildTable(searchresults){
		var table=$('<table>');
		table.append($('<thead>').append(BuildHeader(searchresults[0])));

		var tbody=$('<tbody>');
		for(var x in searchresults){
			tbody.append(BuildRow(searchresults[x]));
		}
		table.append(tbody);

		return table;
	}

	function BuildHeader(row){
		var regex = new RegExp("^[a-zA-Z]");
		var hrow=$('<tr>');
		for(var x in row){
			if(regex.test(x)){
				hrow.append($('<th>').text(x));
			}
		}
		return hrow;
	}

	function BuildRow(row){
		var regex = new RegExp("^[a-zA-Z]");
		var hrow=$('<tr>');
		for(var x in row){
			if(regex.test(x)){
				hrow.append($('<td>').text(row[x]));
			}
		}
		return hrow;
	}
	function iframeform(url){
		var object = this;
		object.time = new Date().getTime();
		object.form = $('<form action="'+url+'" target="iframe'+object.time+'" method="post" style="display:none;" id="form'+object.time+'"></form>');

		object.addParameter = function(parameter,value){
			$("<input type='hidden' />")
			 .attr("name", parameter)
			 .attr("value", value)
			 .appendTo(object.form);
		}
		object.send = function(){
			var iframe = $('<iframe data-time="'+object.time+'" style="display:none;" id="iframe'+object.time+'"></iframe>');
			$( "body" ).append(iframe); 
			$( "body" ).append(object.form);
			object.form.submit();
			iframe.load(function(){  $('#form'+$(this).data('time')).remove();  $(this).remove();   });
		}
	}
  </script>
</head>
<body>
<div id="header"></div>
<?php
	include( "logmenu.inc.php" );
?>
<div class="main">

	<form method="post" id="resourcesearch">
		<table>
			<tr>
				<th>Resource Category</th>
				 <td colspan=3><select name="Category">
						<option value=''>All Categories</option>
<?php
	foreach($catList as $cat){
		echo "\t\t\t\t\t\t<option value=$cat->CategoryID>$cat->Description</option>\n";
	}
?>
					</select>
				</td>
			</tr>
			<tr>
				<th>Requested By</th>
				<td><input name="VUNetID"></td>
				<th>Resource</th>
				<td><input name="Resource"></td>
			</tr>
			<tr>
				<th>Start Date</th>
				<td><input name="StartDate"></td>
				<th>End Date</th>
				<td><input name="EndDate"></td>
			</tr>
			<tr>
				<td colspan=2><select name="nocheckout"><option value="include">Include</option><option value="exclude">Exclude</option></select> items requested but not checked out</td>
				<td colspan=2 class="right"><button type="button" id="btnreport">Report</button><button type="button" id="btnsearch">Search</button></td>
			</tr>
		</table>
	</form>
	<br><br>
	<div id="target"></div>

</div> <!-- end .main -->
</body>
