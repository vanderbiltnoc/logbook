<?php
	require_once('../db.inc.php');
	require_once('../facilities.inc.php');
	require_once('vusurplus.inc.php');

	if(!$person->RackAdmin){
		// No soup for you.
		header('Location: '.redirect("concierge.php"));
		exit;
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="elogs.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="js/jquery.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui.min.js"></script>

  <style type="text/css">
	#target { margin-top: 5px; }

	#target .table > div ~ div:nth-child(2n) { background-color: lightgrey; }

	.search.table label { margin-right: 5px; }
	.search.table span { display: block; font-size: smaller; }
	.search.table > div > div { text-align: right; vertical-align: bottom; }
	.search.table > div > div.left { text-align: left; }

	.header > div { border-bottom: 1px solid black; font-weight: bold; }

	.link { cursor: pointer; text-decoration: underline; }
  </style>

  <script type="text/javascript">
	$(document).ready(function(){
		$('input[name*="Date"]').datepicker();

		$('input[name="SurplusStartDate"]').change(function(){
			$('input[name="SurplusEndDate"]').val($(this).val()).datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
			$('input[name="DestroyedStartDate"]').datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
			$('input[name="DestroyedEndDate"]').datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
		});
		$('input[name="DestroyedStartDate"]').change(function(){
			$('input[name="DestroyedEndDate"]').val($(this).val()).datepicker("destroy").datepicker({ minDate: $.datepicker.parseDate('mm/dd/yy',$(this).val()) });
		});

		$('.search.table span').each(function(){
			if(this.nextSibling.nodeName=="LABEL"){
				$(this).css({'padding-left':$(this).parent('div').width()-$(this).next().next().outerWidth(),'text-align':'left'});
			}
		});

		$('.search.table input:not([name*="Date"])').autocomplete({
			minLength: 0,
			autoFocus: true,
			source: function(req, add){
				var st={};
				st['vusurplus'] = '';
				st['autocomplete'] = '';
				st[this.element[0].name] = req.term;
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

		function BuildTable(searchresults,edit){
			var table=$('<div>').addClass('table');	
			table.append(BuildHeader(searchresults[0]));

			for(var x in searchresults){
				table.append(BuildRow(searchresults[x],edit));
			}

			return table;
		}

		function BuildHeader(row){
			var hrow=$('<div>').addClass('header');
			for(var x in row){
				hrow.append($('<div>').text(x));
			}
			return hrow;
		}

		function BuildRow(row,edit){
			var srow=$('<div>');
			for(var x in row){
				if(edit && x=='DestructionCertificationID' && row[x]==''){
					srow.append($('<div>').append($('<input>').text(row[x]).attr('name','hd'+row['DiskID'])));
				}else{
					clickable=(x=='Disks' && row[x]>0)?'link':'';
					srow.append($('<div>').text(row[x]).addClass(clickable));
				}
			}
			if(row['Disks']>0){
				srow.click(function(){
					var DriveForm=$('<form>');
					var modal=$('<div>').append(DriveForm);

					modal.dialog({
						autoOpen: true,
						modal: true,
						minWidth: 800,
						minHeight: 400,
						buttons: {
							Cancel: function() {
								modal.dialog( "close" );
							}
						},
						appendTo: "#target"
					});

					$.ajax({
						type: 'get',
						dataType: 'json',
						data: {vusurplus:'',getdrives:'',SurplusID: row['SurplusID']},
						async: true,
						success: function(data){
							DriveForm.html(BuildTable(data,<?php echo (SurplusConfig::CheckUser($person->UserID))?'true':'false'; ?>));
							var dataset=DriveForm.serialize();
							if(dataset){
								var buttons=$.extend({}, {
									"Update": function(){
										var dataset=DriveForm.serialize();
										$.post('',dataset+'&'+$.param({vusurplus:'',updatedrive:'',SurplusID: row['SurplusID']}),function(subdata){
											if(subdata.length){
												modal.dialog( "close" );
												srow.click();
											}
										});
									}
								},modal.dialog("option","buttons"));
								modal.dialog("option","buttons",buttons);
							}
						}
					});
				});
			}
			return srow;
		}

		$('#btnreport').click(function(){
			$('#btnsearch').click();

			var form=$('.main form');
			var dataset=form.serializeArray();
			var download = new iframeform('report_vusurplussearch.php');

			download.addParameter('search','');
			for(var x in dataset){
				download.addParameter(dataset[x].name,dataset[x].value);
			}

			download.send();

		});
		$('#btnsearch').click(function(){
			var form=$('.main form');
			var dataset=form.serialize();
			var newform=$('<form>').html('Item destroyed<br><br>');
			$.post('',dataset+'&'+$.param({vusurplus:'',search:''}),function(data){
				if(data.length){
					$('#target').html(BuildTable(data));
					$('#btnreport').removeClass('hide');
				}else{
					$('#target').html('No Results');
					$('#btnreport').addClass('hide');
				}
			});
		});

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
	});
  </script>
</head>
<body>
<div id="header"></div>
<div class="page index">
<?php
	include( "logmenu.inc.php" );
?>
<div class="main">
<h2>Surplus Search</h2>
<div class="center"><div>

<!-- CONTENT GOES HERE -->
<form>
	<div class="table search">
		<div>
			<div><label for="SurplusID">SurplusID</label><input id="SurplusID" name="SurplusID"></div>
			<div><label for="Serial">Serial Number</label><input id="Serial" name="Serial"></div>
			<div><label for="AssetTag">Asset Tag</label><input id="AssetTag" name="AssetTag"></div>
		</div>
		<div>
			<div><label for="DevType">Device Type</label><input name="DevType"></div>
			<div><label for="Model">Model</label><input name="Model"></div>
			<div><label for="Manufacturer">Manufacturer</label><input name="Manufacturer"></div>
		</div>
		<div>
			<div><span>Start</span><label for="SurplusStartDate">Surplused</label><input name="SurplusStartDate"></div>
			<div class="left"><span>End</span><input name="SurplusEndDate"></div>
			<div><label for="UserID">Surplused By</label><input name="UserID"></div>
		</div>
		<div>
			<div><span>Start</span><label for="DestroyedStartDate">Drives Destroyed</label><input name="DestroyedStartDate"></div>
			<div class="left"><span>End</span><input name="DestroyedEndDate"></div>
			<div><button type="button" id="btnreport" class="hide">Report</button><button type="button" id="btnsearch">Search</button></div>
		</div>
	</div>
</form>

<div id="target"></div>

</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
