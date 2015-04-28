<?php
	require_once('../db.inc.php');
	require_once('../facilities.inc.php');
	require_once('vusurplus.inc.php');

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect("concierge.php"));
		exit;
	}

	$cods=SurplusHD::GetDestructionStats();

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
				return false;
			}
		});

		// No form submissions from hitting enter
		$('form').submit(function(e){
			e.preventDefault();
		});

		$('#btnsearch').click(function(e){
			var st={};
			st['vusurplus'] = '';
			st['authorizeuser'] = '';
			st['UserID'] = $('input[name="UserID"]').val();
			if(st['UserID']){
				$.post('',st,function(data){
					$('input[name="UserID"]').val('');
				});
			}
			GetAuthorizedUsers();
		});

		GetAuthorizedUsers();
	});

	var DestroyButton=$('<button>').text('Destroy Drives');
	function AddDestroyButton(){
		var lastcell=$('#target ~ table tr:last-child > td:last-child');

		if(typeof $('#codid')[0] != "undefined"){

			DestroyButton.css({
				'position':'absolute',
				'top': lastcell.position().top + 'px',
				'left': lastcell.position().left + lastcell.outerWidth() + 10 + 'px',
				'height': lastcell.outerHeight() + 'px'
			}).appendTo($('.center > div'));

			DestroyButton.click(function(e){
				var st={};
				st['vusurplus'] = '';
				st['certifydrives'] = '';
				st['DestructionCertificationID'] = $('#codid').val();
				if(st['DestructionCertificationID']){
					$.post('',st,function(data){
						if(data){
							location.reload(true);
						}else{
							alert('error');
						}
					});
				}
			});
		}
	}

	function GetAuthorizedUsers(){
		var st={};
		st['vusurplus'] = '';
		st['authorizedusers'] = '';
		$.get('', st, function(data){
			$('#target').html(BuildTable(data));
			AddDestroyButton();
		});
	}

	function BuildTable(users){
		var table=$('<div>').addClass('table');

		for(var x in users){
			table.append(BuildRow(users[x]));
		}

		return table;
	}

	function BuildRow(user){
		var urow=$('<div>');
		var removebutton=$('<button>').attr('type','button').text('Remove User').data('UserIndex',user.UserIndex);

		var st={};
		st['vusurplus'] = '';
		st['removeuser'] = '';
		st['UserIndex'] = removebutton.data('UserIndex'); 
		removebutton.click(function(e){$.post('',st,function(data){GetAuthorizedUsers()})});

		urow.append($('<div>').text(user.UserID)).append($('<div>').append(removebutton));

		return urow;
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
<h2>Users Authorized to Certify Destruction</h2>
<div class="center"><div>

<!-- CONTENT GOES HERE -->
<form>
	<div class="table search">
		<div>
			<div><label for="UserID">UserID</label><input name="UserID"></div>
			<div><button type="button" id="btnsearch">Authorize User</button></div>
		</div>
	</div>
</form>

<div id="target"></div>

<table>
<tr><th>Certificate of Destruction</th><th>Storage Location</th><th>Created By</th><th># Drives</th></tr>
<?php
	foreach($cods as $i => $hd){
		$disabled=($hd->DestructionCertificationID=="")?'id="codid"':'disabled';
		$user=($hd->DestructionCertificationID=="")?$user->UserID:$hd->UserID;
		print "<tr><td><input $disabled value=\"$hd->DestructionCertificationID\"></td><td><input disabled value=\"$hd->Location\"></td><td><input disabled value=\"$user\"></td><td>$hd->Disks</td></tr>\n";
	}
?>
</table>

</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
