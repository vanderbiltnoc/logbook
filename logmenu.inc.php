<div id="sidebar" class="menu">
	<hr>
	<ul class="nav">
		<a href="concierge.php"><li>Request Resources</li></a>
		<a href="dcentryreq.php"><li>Request Data Center Entry</li></a>
		<a href="requestscanning.php"><li>Scanning Request</li></a>
<?php
	if($person->RackAdmin){
?>
		<a href="addresource.php"><li>Add/Update Resources</li></a>
		<a href="resourceadmin.php"><li>Check In/Out Resources</li></a>
		<a href="dcentryadmin.php"><li>Data Center Entry Admin</li></a>
		<a href="scanningadmin.php"><li>Scanning Admin</li></a>
		<a href="resourcelog.php"><li>Resource Log Report</li></a>
		<a href="entryreport.php"><li>Data Center Entry Log Report</li></a>
		<a href="scanningreport.php"><li>Scanning Report</li></a>
		<a href="vusurplusentry.php"><li>Surplus Entry</li></a>
		<a href="vusurplussearch.php"><li>Surplus Search</li></a>
		<a href="../"><li>Data Center Inventory</li></a>
<?php
	}
	if($person->SiteAdmin){
?>
		<a href="configuration.php"><li>Logbook Configuration</li></a>
		<a href="vusurplusconfig.php"><li>Surplus Configuration</li></a>
<?php
	}
?>
	</ul>
	<hr>
</div>
<script type="text/javascript">
$('#sidebar .nav a').each(function(){
	if($(this).attr('href')=="<?php echo basename($_SERVER['PHP_SELF']);?>"){
		$(this).children().addClass('active');
	}
});
</script>
