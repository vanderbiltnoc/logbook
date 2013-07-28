<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

 	$user=new VUser();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights(); 

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	if(isset($_POST['action']) && $_POST['action']=='Update'){
		foreach($_POST as $param => $value){
			if(strpos($param, 'log_')!==false){
				Config::UpdateParameter($param,$value);
			}
		}
	}


?>
<!DOCTYPE HTML>
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
		$("#configtabs").tabs();
		$('#configtabs input[data-default],#configtabs select[data-default]').each(function(){
			$(this).parent().after('<div><button type="button">&lt;--</button></div><div><span>'+$(this).data('default')+'</span></div>');
		});
		$("#configtabs input").each(function(){
			$(this).attr('id', $(this).attr('name'));
			$(this).removeAttr('data-default');
		});
		$("#configtabs button").each(function(){
			var a = $(this).parent().prev().find('input,select');
			$(this).click(function(){
				a.val($(this).parent().next().children('span').text());
				a.triggerHandler("paste");
				a.focus();
				$('input[name="OrgName"]').focus();
			});
		});
	});
  </script>


</head>
<body>
<div id="header"></div>
<div class="page scanningadmin">
<?php
	include( "logmenu.inc.php" );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Configuration"),'</h3>
<h3>',__("Database Version"),': ',$config->ParameterArray["Version"],'</h3>

<div class="center"><div>
<form enctype="multipart/form-data" action="',$_SERVER["PHP_SELF"],'" method="POST">
	<div id="configtabs">
		<ul>
			<li><a href="#general">General</a></li>
		</ul>
		<div id="general">
			<h3>LDAP Credentials</h3>
			<div class="table">
				<div>
					<div><label for="log_BaseDN">Base DN</label></div>
					<div><input type="text" data-default="',$config->defaults["log_BaseDN"],'" name="log_BaseDN" value="',$config->ParameterArray["log_BaseDN"],'"></div>
				</div>
				<div>
					<div><label for="log_LDAPRN">User</label></div>
					<div><input type="text" data-default="',$config->defaults["log_LDAPRN"],'" name="log_LDAPRN" value="',$config->ParameterArray["log_LDAPRN"],'"></div>
				</div>
				<div>
					<div><label for="log_LDAPPass">Password</label></div>
					<div><input type="text" data-default="',$config->defaults["log_LDAPPass"],'" name="log_LDAPPass" value="',$config->ParameterArray["log_LDAPPass"],'"></div>
				</div>
				<div>
					<div><label for="log_LDAPHost">Host</label></div>
					<div><input type="text" data-default="',$config->defaults["log_LDAPHost"],'" name="log_LDAPHost" value="',$config->ParameterArray["log_LDAPHost"],'"></div>
				</div>
			</div>
			<h3>Photo Lookup</h3>
			<div class="table">
				<div>
					<div><label for="log_PhotoURL">Photo URL</label></div>
					<div><input type="text" data-default="',$config->defaults["log_PhotoURL"],'" name="log_PhotoURL" value="',$config->ParameterArray["log_PhotoURL"],'"></div>
				</div>
			</div>
		</div>
	</div>';

?>
<div class="table centermargin">
<div>
	<div>&nbsp;</div>
</div>
<div>
   <div><input type="submit" name="action" value="Update"></div>
</div>
</div> <!-- END div.table -->
</form>

</div>
</div><!-- /center -->
</div><!-- /main -->
</div><!-- /page -->
</body>
</html>

