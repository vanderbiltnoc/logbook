<?php
	require_once('../db.inc.php');
	require_once('../facilities.inc.php');
	require_once('vusurplus.inc.php');

	if(isset($_POST['search'])){
		$validsearchkeys=array('SurplusID','Serial','AssetTag','DevType','Model','Manufacturer','UserID');
		$validsubsearchkeys=array('Serial','UserID','DestructionCertificationID');
		$search='';
		$subsearch='';
		foreach($_POST as $prop => $val){
			// Primary search routine
			if(in_array($prop,$validsearchkeys) && $val){
				if($search){
					$search.=" AND $prop LIKE '%".addslashes($val)."%'";
				}else{
					$search.="WHERE $prop LIKE '%".addslashes($val)."%'";
				}
			}
			if($prop=="SurplusStartDate" && $val){
				$end=str_replace('Start','End',$prop);
				if($search){
					$search.=" AND Created BETWEEN '".date('Y-m-d H:i', strtotime($val))."' AND '".date('Y-m-d H:i', strtotime($_POST[$end].'+23 hours 59 minutes'))."'";
				}else{
					$search.="WHERE Created BETWEEN '".date('Y-m-d H:i', strtotime($val))."' AND '".date('Y-m-d H:i', strtotime($_POST[$end].'+23 hours 59 minutes'))."'";
				}
			}
			// Extend the search to look at the destroyed hard drives
			if(in_array($prop,$validsubsearchkeys) && $val){
				if($subsearch){
					$subsearch.=" AND $prop LIKE '%".addslashes($val)."%'";
				}else{
					$subsearch.="WHERE $prop LIKE '%".addslashes($val)."%'";
				}
			}
			if($prop=="DestroyedStartDate" && $val){
				$end=str_replace('Start','End',$prop);
				if($subsearch){
					$subsearch.=" AND CertificationDate BETWEEN '".date('Y-m-d H:i', strtotime($val))."' AND '".date('Y-m-d H:i', strtotime($_POST[$end].'+23 hours 59 minutes'))."'";
				}else{
					$subsearch.="WHERE CertificationDate BETWEEN '".date('Y-m-d H:i', strtotime($val))."' AND '".date('Y-m-d H:i', strtotime($_POST[$end].'+23 hours 59 minutes'))."'";
				}
			}
		}

		if($search && $subsearch){
			$search.=" OR SurplusID IN (SELECT DISTINCT SurplusID FROM vu_SurplusHD $subsearch)";
		}elseif(!$search && $subsearch){
			$search.="WHERE SurplusID IN (SELECT DISTINCT SurplusID FROM vu_SurplusHD $subsearch)";
		}


		$searchresults=array();
		$sql="SELECT *, (SELECT COUNT(DiskID) FROM vu_SurplusHD WHERE SurplusID=a.SurplusID) AS Disks FROM vu_Surplus a $search;";
		$result=mysql_query($sql,$facDB);

		while($row=mysql_fetch_array($result)){
			$s=Surplus::RowToObject($row);
			$s->Disks=$row['Disks'];
			$searchresults[]=$s;
		}
		$result=$searchresults;
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
$objPHPExcel->getProperties()->setCreator("Maarten Balliauw");
$objPHPExcel->getProperties()->setLastModifiedBy("Maarten Balliauw");
$objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Test Document");
$objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
$objPHPExcel->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");

$worksheet=$objPHPExcel->createSheet(0);
$objPHPExcel->setActiveSheetIndex(0);
$worksheet->freezePane('A2');

// Add some data
$worksheet->SetCellValue("A1", "SurplusID");
$worksheet->SetCellValue("B1", "UserID");
$worksheet->SetCellValue("C1", "Date\nSurplused");
$worksheet->SetCellValue("D1", "Device Type");
$worksheet->SetCellValue("E1", "Manufacturer");
$worksheet->SetCellValue("F1", "Model");
$worksheet->SetCellValue("G1", "Serial");
$worksheet->SetCellValue("H1", "Asset Tag");
if($result){
	$row=2;
	foreach($result as $index => $sr){
		$worksheet->SetCellValue("A$row", "$sr->SurplusID");
		$worksheet->SetCellValue("B$row", "$sr->UserID");
		$worksheet->SetCellValue("C$row", "$sr->Created");
		$worksheet->SetCellValue("D$row", "$sr->DevType");
		$worksheet->SetCellValue("E$row", "$sr->Manufacturer");
		$worksheet->SetCellValue("F$row", "$sr->Model");
		$worksheet->SetCellValue("G$row", "$sr->Serial");
		$worksheet->SetCellValue("H$row", "$sr->AssetTag");
		$row++;
		if($sr->Disks>0){
			$worksheet->SetCellValue("I1", "Certificate\nof Destruction");
			$hd=new SurplusHD();
			$hd->SurplusID=$sr->SurplusID;
			foreach($hd->GetDrives() as $i => $d){
				$worksheet->SetCellValue("A$row", "$d->SurplusID");
				$worksheet->SetCellValue("B$row", "$d->UserID");
				$worksheet->SetCellValue("C$row", "$d->CertificationDate");
				$worksheet->SetCellValue("D$row", "Hard Drive");
				$worksheet->SetCellValue("G$row", "$d->Serial");
				$worksheet->SetCellValue("I$row", "$d->DestructionCertificationID");
				$row++;
			}
		}
	}
}

$worksheet->getColumnDimension('C')->setWidth(17);
$worksheet->getColumnDimension('D')->setWidth(10);
$worksheet->getColumnDimension('E')->setWidth(12);
$worksheet->getColumnDimension('F')->setWidth(15);
$worksheet->getColumnDimension('H')->setWidth(10);
$worksheet->getColumnDimension('I')->setWidth(15);

$worksheet->getStyle('A1:I1')->getFont()->setBold(true);
$worksheet->getStyle('A1:I1')->getAlignment()->setWrapText(true);
$worksheet->getStyle('A1:I1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// Rename sheet
$worksheet->setTitle('Simple');

$thisDate = date('Y-m-d');

// send out document, save Excel 2007 file

$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
if (PHP_SAPI != 'cli') {
    header('Content-type: application/application/vnd.openxmlformats-officedocument.'
        . 'spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=Device_Surplus_Report_" . $thisDate
     . ".xlsx");
    header('Cache-Control: max-age=0');
    // write file to the browser
    $objWriter->save('php://output');
}

$objPHPExcel->disconnectWorksheets();
unset($objPHPExcel);
?>
