<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

 	$user = new VUser();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if ( ! $user->RackAdmin ) {
		printf( "<meta http-equiv='refresh' content='0; url=concierge.php'>" );
		end;
	}

	if(isset($_POST['st'])){
		$startClause=($_POST['StartDate']!='')?" AND RequestedTime>=\"".date("Y-m-d 00:00:00",strtotime($_POST['StartDate']))."\"":"";
		$endClause=($_POST['EndDate']!='')?" AND RequestedTime<=\"".date("Y-m-d 23:59:59",strtotime($_POST['EndDate']))."\"":"";

		$nocheckout=($_POST['nocheckout']=='include')?'':' AND Timeout != "0000-00-00 00:00:00" ';
		$searchresults=array();
		$sql="
SELECT VUNetID, fac_Resource.Description AS Resource, fac_ResourceCategory.Description AS Category, Note, RequestedTime, TimeOut, EstimatedReturn, ActualReturn FROM fac_ResourceLog
LEFT JOIN fac_Resource ON fac_ResourceLog.ResourceID = fac_Resource.ResourceID
LEFT JOIN fac_ResourceCategory ON fac_Resource.CategoryID = fac_ResourceCategory.CategoryID
WHERE fac_Resource.Description LIKE '%".addslashes($_POST['Resource'])."%'
AND VUNetID LIKE '%".addslashes($_POST['VUNetID'])."%'$nocheckout$startClause$endClause
AND fac_ResourceCategory.CategoryID LIKE '%".addslashes($_POST['Category'])."%'
ORDER BY fac_ResourceLog.TimeOut ASC;";

		$result=mysql_query($sql,$facDB);
	}

error_reporting(E_ALL);
ini_set('memory_limit', '840M');
ini_set('max_execution_time', '0');

set_include_path(get_include_path().PATH_SEPARATOR.__DIR__.'/PHPExcel');
require 'PHPExcel.php';
require 'PHPExcel/Writer/Excel2007.php';

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set properties
$objPHPExcel->getProperties()->setCreator("openDCIM");
$objPHPExcel->getProperties()->setLastModifiedBy("openDCIM");
$objPHPExcel->getProperties()->setTitle("Resource checkout report");
$objPHPExcel->getProperties()->setSubject("Resource checkout report");
$objPHPExcel->getProperties()->setDescription("Report of resources checked out from the NOC");

$worksheet=$objPHPExcel->createSheet(0);
$objPHPExcel->setActiveSheetIndex(0);
$worksheet->freezePane('A2');

// Add some data
$worksheet->SetCellValue("A1", "VUNetID");
$worksheet->SetCellValue("B1", "Resource");
$worksheet->SetCellValue("C1", "Category");
$worksheet->SetCellValue("D1", "Reason");
$worksheet->SetCellValue("E1", "Time Requested");
$worksheet->SetCellValue("F1", "Estimated Return");
$worksheet->SetCellValue("G1", "Time Checkedout");
$worksheet->SetCellValue("H1", "Time Returned");

$r=2;
while($row=mysql_fetch_array($result)){
	$worksheet->SetCellValue("A$r", "{$row['VUNetID']}");
	$worksheet->SetCellValue("B$r", "{$row['Resource']}");
	$worksheet->SetCellValue("C$r", "{$row['Category']}");
	$worksheet->SetCellValue("D$r", "{$row['Note']}");
	$worksheet->SetCellValue("E$r", "{$row['RequestedTime']}");
	$worksheet->SetCellValue("F$r", "{$row['EstimatedReturn']}");
	$worksheet->SetCellValue("G$r", "{$row['TimeOut']}");
	$worksheet->SetCellValue("H$r", "{$row['ActualReturn']}");
	$r++;
}

$worksheet->getColumnDimension('C')->setWidth(17);
$worksheet->getColumnDimension('D')->setWidth(30);
$worksheet->getColumnDimension('E')->setWidth(18);
$worksheet->getColumnDimension('F')->setWidth(18);
$worksheet->getColumnDimension('G')->setWidth(18);
$worksheet->getColumnDimension('H')->setWidth(18);
$worksheet->getColumnDimension('I')->setWidth(15);

$worksheet->getStyle('A1:I1')->getFont()->setBold(true);
$worksheet->getStyle('A1:I1')->getAlignment()->setWrapText(true);
$worksheet->getStyle('A1:I1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// Rename sheet
$worksheet->setTitle('Simple');

$thisDate = date('Y-m-d');

// send out document, save Excel 2007 file

$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
header('Content-type: application/application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=Device_Surplus_Report_".$thisDate.".xlsx");
header('Cache-Control: max-age=0');
// write file to the browser
$objWriter->save('php://output');

$objPHPExcel->disconnectWorksheets();
unset($objPHPExcel);
?>
