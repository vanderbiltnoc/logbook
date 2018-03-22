<?php
	require_once('../db.inc.php');
	require_once('../facilities.inc.php');
	require_once('vusurplus.inc.php');

	if(!$person->RackAdmin){
		// No soup for you.
		header('Location: '.redirect("concierge.php"));
		exit;
	}

	$manf=new Manufacturer();
	$manf=$manf->GetManufacturerList();

	// Set the maximum number of drives allowed for a device
	$maxdrives=36;

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
	#target > form { margin-top: 50px; }
	#target > form > label, #drivediv > label { float: left; width: 150px; clear: left; }
	#target > form > input, #drivediv > input { display: block; }

	#drivediv > span { display: block; font-size: 1.25em; font-weight: bold; margin-top: 0.5em; margin-bottom: 0.5em;}
  </style>

  <script type="text/javascript">
	$(document).ready(function(){
		$('#sel_devtype, #sel_manf, #sel_numdrives').on('change',function(e){
			buildForm();
		});
	});

	// Check to make sure all the values are filled before we show the submit button
	function checkReady(e){
		var ready=true;
		$('#target > form input').each(function(){
			if(ready){
				if(this.value.trim()==''){
					ready=false;
				}
			}
		});
		if(ready){
			$('#target > form button').show();
			// If this was triggered by someone hitting <enter> click the button
			if(e.keyCode==10 || e.keyCode==13){
				$('#target > form button').click();
			}
		}else{
			$('#target > form button').hide();
		}
	}

	// Make some magic happen
	function buildForm(){
		var seldev=$('#sel_devtype');
		var selman=$('#sel_manf');
		var seldrv=$('#sel_numdrives');

		var target=$('#target > form');
		var form=$('<form>');
		var devtype=$('<input>').attr({'id':'devtype','name':'devtype'}).data('label','Device Type').val(seldev.val());
		var manf=$('<input>').attr({'id':'manf','name':'manf'}).data('label','Manufacturer').val(selman.val());
		var model=$('<input>').attr({'id':'model','name':'model'}).data('label','Model').val($('#model').val());
		var serial=$('<input>').attr({'id':'serial','name':'serial'}).data('label','Serial').val($('#serial').val());
		var assettag=$('<input>').attr({'id':'assettag','name':'assettag'}).data('label','Asset Tag').val($('#assettag').val());
		var drivediv=$('<div>').attr('id','drivediv');
		var btnsubmit=$('<button>').attr('type','button').text('Submit').hide();

		// Function to make the hard drive input blanks
		function buildDrives(e){
			var seldrv=$('#sel_numdrives');
			var replacementdiv=$('<div>').attr('id','drivediv');
			if(seldrv.val()>0){
				$.ajax({url: '',type: 'get', async: false, data: {vusurplus:'',getlocations:''}}).done(function(data){
					locations=data;
				});
				replacementdiv.append($('<span>').text('Hard drives'));
				// Add in a select box of locations to store the drives for disposal
				var sellocation=$('<select>').attr({'id':'location','name':'location'});
				for(var x in locations){
					sellocation.append($('<option>').val(locations[x].Location).text(locations[x].Location));
				}
				replacementdiv.append($('<label>').attr('for','location').text('Storage bin location'));
				replacementdiv.append(sellocation.val(($('#location').val())?$('#location').val():sellocation.val()));
				for(var i=1;i<=seldrv.val();i++){
					var drive=$('<input>').attr({'id':'hd'+i,'name':'hd[]'}).data('label','Hard Drive Serial #'+i).val($('#hd'+i).val());
					replacementdiv.append(drive);
					addLabel(drive);
					drive.on('change keyup',checkReady);
				}
			}

			// if e is undefined we've called the function directly, otherwise we're in an update operation
			if(e==undefined){
				drivediv.replaceWith(replacementdiv);
			}else{
				$('#drivediv').replaceWith(replacementdiv);
			}

			// Fix the height of the labels to match the inputs
			$('#drivediv label').each(function(){fixHeight($(this))});
		}

		function addLabel(target){
			var label=$('<label>').attr('for',target.attr('id')).text(target.data('label'));
			label.insertBefore(target);
		}

		// The float was causing the labels to align funny and the different browsers have
		// different heights for the input boxes.  This will show em. 
		function fixHeight(target){
			target.css('line-height',target.next('input,select').outerHeight()+'px');
		}

		// This is what we do when we submit the form
		btnsubmit.click(function(){
			var dataset=form.serialize();
			var newform=$('<form>').html('Item destroyed<br><br>');
			$.post('',dataset+'&'+$.param({vusurplus:'',create:''}),function(data){
				if(data.SurplusID){
					form.replaceWith(newform);
					for(var prop in data){
						newform.append(prop + '=' + data[prop] + "<br>");
					}
				}else{
					// replace this with a modal error dialog
					alert('Something just went horribly wrong');
				}
			});
		});

		// Array of all the input fields from above to add to the form and add data events
		$.each([devtype,manf,model,serial,assettag,drivediv], function(i, val){
			form.append(val);
			val.on('change keyup',checkReady);
		});

		// Anything in the form right now, give it a label
		form.children().each(function(){
			var target=$(this);
			if(target.data('label')!=undefined){
				addLabel(target);
			}
		});

		// Check if we need to show some hard drives
		buildDrives();

		// Output the form
		target.replaceWith(form);

		// Add the submit button
		form.append(btnsubmit);

		// Fix the height of the labels to match the inputs
		$('#target > form label').each(function(){fixHeight($(this))});
	}

	if(typeof String.prototype.trim !== 'function') {
		String.prototype.trim = function() {
			return this.replace(/^\s+|\s+$/g, ''); 
		}
	}

  </script>
</head>
<body>
<div id="header"></div>
<div class="page index">
<?php
	include( "logmenu.inc.php" );
?>
<div class="main">
<h2>Surplus equipment</h2>
<div class="center"><div>

<label for="sel_devtype">Device Type</label>
<select id="sel_devtype">
	<option></option>
	<option value="Laptop">Laptop</option>
	<option value="Desktop">Desktop</option>
	<option value="Server">Server</option>
	<option value="Array">Array</option>
	<option value="Printer">Printer</option>
	<option value="Other">Other</option>
</select>
<label for="sel_manf">Manufacturer</label>
<select id="sel_manf">
	<option></option>
<?php
	foreach($manf as $m){
		print "\t<option value=\"$m->Name\">$m->Name</option>\n";
	}
?>
</select>
<label for="sel_numdrives">Number of drives</label>
<select id="sel_numdrives">
<?php
	for($n=0;$n<=$maxdrives;$n++){
		print "\t<option value=$n>$n</option>\n";
	}
?>
</select>
<!-- CONTENT GOES HERE -->

<div id="target"><form></form></div>


</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
