<?php

$user=new VUser();
$user->UserID=$_SERVER["REMOTE_USER"];
$user->GetUserRights(); 

/* Generic html sanitization routine */
if(!function_exists("sanitize")){
	function sanitize($string,$stripall=true){
		// Trim any leading or trailing whitespace
		$clean=trim($string);

		// Convert any special characters to their normal parts
		$clean=html_entity_decode($clean,ENT_COMPAT,"UTF-8");

		// By default strip all html
		$allowedtags=($stripall)?'':'<a><b><i><img><u><br>';

		// Strip out the shit we don't allow
		$clean=strip_tags($clean, $allowedtags);
		// If we decide to strip double quotes instead of encoding them uncomment the 
		//	next line
	//	$clean=($stripall)?str_replace('"','',$clean):$clean;
		// What is this gonna do ?
		$clean=filter_var($clean, FILTER_SANITIZE_SPECIAL_CHARS);

		// There shoudln't be anything left to escape but wtf do it anyway
		$clean=addslashes($clean);

		return $clean;
	}
}

class Surplus {
	var $SurplusID;
	var $UserID;
	var $Created;
	var $DevType;
	var $Manufacturer;
	var $Model;
	var $Serial;
	var $AssetTag;

	function MakeSafe(){
		$this->SurplusID=intval($this->SurplusID);
		$this->UserID=sanitize($this->UserID);
		$this->Created=(date('Y',strtotime($this->Created))==1969)?date('Y-m-d H:i:s'):date('Y-m-d H:i:s', strtotime($this->Created));
		$this->DevType=sanitize($this->DevType);
		$this->Manufacturer=sanitize($this->Manufacturer);
		$this->Model=sanitize($this->Model);
		$this->Serial=sanitize($this->Serial);
		$this->AssetTag=sanitize($this->AssetTag);
	}

	function MakeDisplay(){
		$this->UserID=stripslashes($this->UserID);
		$this->Created=stripslashes($this->Created);
		$this->DevType=stripslashes($this->DevType);
		$this->Manufacturer=stripslashes($this->Manufacturer);
		$this->Model=stripslashes($this->Model);
		$this->Serial=stripslashes($this->Serial);
		$this->AssetTag=stripslashes($this->AssetTag);
	}

	static function RowToObject($row){
		$s=new Surplus();
		$s->SurplusID=$row["SurplusID"];
		$s->UserID=$row["UserID"];
		$s->Created=$row["Created"];
		$s->DevType=$row["DevType"];
		$s->Manufacturer=$row["Manufacturer"];
		$s->Model=$row["Model"];
		$s->Serial=$row["Serial"];
		$s->AssetTag=$row["AssetTag"];
		$s->MakeDisplay();

		return $s;
	}

	function CreateSurplus(){
		global $facDB;
		$this->UserID=$_SERVER["REMOTE_USER"];
		$this->MakeSafe();

		$sql="INSERT INTO vu_Surplus SET UserID=\"$this->UserID\", 
			DevType=\"$this->DevType\", Manufacturer=\"$this->Manufacturer\", 
			Model=\"$this->Model\", Serial=\"$this->Serial\", 
			AssetTag=\"$this->AssetTag\", Created=\"$this->Created\";";

		if(!$result=mysql_query($sql,$facDB)){
			return 0;
		}

		$this->SurplusID=mysql_insert_id($facDB);
		return $this->SurplusID;
	}

	function GetSurplus(){
		global $facDB;
		$this->MakeSafe();

		$sql="SELECT * FROM vu_Surplus WHERE SurplusID=$this->SurplusID;";
		$result=mysql_query($sql,$facDB);

		while($row=mysql_fetch_array($result)){
			foreach(Surplus::RowToObject($row) as $prop => $val){
				$this->$prop=$val;
			}
		}

		return true;
	}

	static function GetSurpluses(){
		global $facDB;
		$sql="SELECT * FROM vu_Surplus;";
		$result=mysql_query($sql,$facDB);

		$records=array();
		while($row=mysql_fetch_array($result)){
			$records[]=Surplus::RowToObject($row);
		}

		return $records;
	}

	function RemoveSurplus(){
		// this should only be used for error controls
		global $facDB;
		$this->MakeSafe();

		$sql="DELETE FROM vu_Surplus WHERE SurplusID=$this->SurplusID;";

		$hd=new SurplusHD();
		$hd->SurplusHD=$this->SurplusHD;
		$hd->RemoveDrives();

		if(!$result=mysql_query($sql,$facDB)){
			return false;
		}else{
			return true;
		}
	}
}

// This is a class to deal with hard drives only.  They will be stored with just a reference to the primary device
class SurplusHD {
	var $DiskID;
	var $SurplusID;
	var $UserID;
	var $Serial;
	var $DestructionCertificationID;
	var $CertificationDate;

	function MakeSafe(){
		$this->DiskID=intval($this->DiskID);
		$this->SurplusID=intval($this->SurplusID);
		$this->UserID=sanitize($this->UserID);
		$this->Location=sanitize($this->Location);
		$this->Serial=sanitize($this->Serial);
		$this->DestructionCertificationID=sanitize($this->DestructionCertificationID);
		$this->CertificationDate=(date('Y',strtotime($this->CertificationDate))==1969)?date('Y-m-d H:i:s'):date('Y-m-d H:i:s', strtotime($this->CertificationDate));
	}

	function MakeDisplay(){
		$this->UserID=stripslashes($this->UserID);
		$this->Location=stripslashes($this->Location);
		$this->Serial=stripslashes($this->Serial);
		$this->DestructionCertificationID=stripslashes($this->DestructionCertificationID);
	}

	static function RowToObject($row){
		$hd=new SurplusHD();
		$hd->DiskID=$row["DiskID"];
		$hd->SurplusID=$row["SurplusID"];
		$hd->UserID=$row["UserID"];
		$hd->Location=$row["Location"];
		$hd->Serial=$row["Serial"];
		$hd->DestructionCertificationID=$row["DestructionCertificationID"];
		$hd->CertificationDate=$row["CertificationDate"];
		$hd->MakeDisplay();

		return $hd;
	}

	function CreateDrive(){
		global $facDB;
		$this->MakeSafe();

		$sql="INSERT INTO vu_SurplusHD SET SurplusID=$this->SurplusID, 
			Serial=\"$this->Serial\", Location=\"$this->Location\";";

		if(!$result=mysql_query($sql,$facDB)){
			return false;
		}else{
			$this->DiskID=mysql_insert_id($facDB);
			return $this->DiskID;
		}
	}

	function GetDrives(){
		global $facDB;
		$this->MakeSafe();

		$sql="SELECT * FROM vu_SurplusHD WHERE SurplusID=$this->SurplusID;";
		$result=mysql_query($sql,$facDB);

		$records=array();
		while($row=mysql_fetch_array($result)){
			$records[]=SurplusHD::RowToObject($row);
		}

		return $records;
	}

	function RemoveDrives(){
		// this function should only be used for error controls
		global $facDB;
		$this->MakeSafe();

		$sql="DELETE FROM vu_SurplusHD WHERE SurplusID=$this->SurplusID;";

		if(!$result=mysql_query($sql,$facDB)){
			return false;
		}else{
			return true;
		}
	}

	static function GetDestructionStats(){
		global $facDB;
		$sql="SELECT DISTINCT DestructionCertificationID, UserID, Location, 
			(SELECT COUNT(DiskID) FROM vu_SurplusHD WHERE a.DestructionCertificationID = 
			DestructionCertificationID AND a.Location = Location) AS Disks, 
			CertificationDate FROM vu_SurplusHD a GROUP BY Location, 
			DestructionCertificationID ORDER BY CertificationDate DESC;";
		$result=mysql_query($sql,$facDB);
		$records=array();
		while($row=mysql_fetch_array($result)){
			$hd=new SurplusHD();
			$hd->UserID=$row["UserID"];
			$hd->Location=$row["Location"];
			$hd->DestructionCertificationID=$row["DestructionCertificationID"];
			$hd->CertificationDate=$row["CertificationDate"];
			$hd->Disks=$row["Disks"];
			$records[]=$hd;
		}

		return $records;
	}

	function GetUncertifiedDrives($count=null){
		global $facDB;
		$sql="SELECT * FROM vu_SurplusHD WHERE UserID=\"\";";
		$result=mysql_query($sql,$facDB);

		$records=array();
		if(is_null($count)){
			while($row=mysql_fetch_array($result)){
				$records[]=SurplusHD::RowToObject($row);
			}
		}else{
			$records=mysql_num_rows($result);
		}

		return $records;
	}

	function CertifyDrives(){
		global $facDB;
		$this->UserID=$_SERVER["REMOTE_USER"];
		$this->MakeSafe();

		$sql="UPDATE vu_SurplusHD SET 
			DestructionCertificationID=\"$this->DestructionCertificationID\", 
			CertificationDate=NOW(), UserID=\"$this->UserID\"
			WHERE UserID='' AND CertificationDate=\"0000-00-00 00:00:00\" AND 
			Location=\"$this->Location\";";

		$result=mysql_query($sql,$facDB);
		if(!mysql_affected_rows($facDB)){
			return false;
		}else{
			return true;
		}
	}

	function CertifyDestruction(){
		global $facDB;
		$this->UserID=$_SERVER["REMOTE_USER"];
		$this->MakeSafe();

		$sql="UPDATE vu_SurplusHD SET 
			DestructionCertificationID=\"$this->DestructionCertificationID\", 
			CertificationDate=\"$this->CertificationDate\", UserID=\"$this->UserID\"
			WHERE DiskID=$this->DiskID AND SurplusID=$this->SurplusID;";

		$result=mysql_query($sql,$facDB);
		if(!mysql_affected_rows($facDB)){
			return false;
		}else{
			return true;
		}
	}
}

class SurplusConfig {
	var $UserIndex;
	var $UserID;

	function MakeSafe(){
		$this->UserIndex=intval($this->UserIndex);
		$this->UserID=sanitize($this->UserID);
	}

	function MakeDisplay(){
		$this->UserID=stripslashes($this->UserID);
	}

	static function RowToObject($row){
		$sc=new SurplusConfig();
		$sc->UserIndex=$row["UserIndex"];
		$sc->UserID=$row["UserID"];
		$sc->MakeDisplay();

		return $sc;
	}

	function CreateUser(){
		global $facDB;
		$this->MakeSafe();

		$sql="INSERT INTO vu_SurplusConfig SET UserID=\"$this->UserID\";";

		if(!$result=mysql_query($sql,$facDB)){
			return false;
		}else{
			$this->UserIndex=mysql_insert_id($facDB);
			return $this->UserIndex;
		}
	}

	function RemoveUser(){
		global $facDB;
		$this->MakeSafe();

		$sql="DELETE FROM vu_SurplusConfig WHERE UserIndex=$this->UserIndex;";

		$result=mysql_query($sql,$facDB);
		if(!mysql_affected_rows($facDB)){
			return false;
		}else{
			return true;
		}
	}

	static function GetUsers(){
		global $facDB;

		$sql="SELECT * FROM vu_SurplusConfig;";
		$result=mysql_query($sql,$facDB);

		$records=array();
		while($row=mysql_fetch_array($result)){
			$records[]=SurplusConfig::RowToObject($row);
		}

		return $records;
	}

	static function CheckUser($UserID){
		global $facDB;

		$sql="SELECT * FROM vu_SurplusConfig WHERE UserID=\"".addslashes($UserID)."\";";

		$result=mysql_query($sql,$facDB);
		if(mysql_num_rows($result)>0){
			return SurplusConfig::RowToObject(mysql_fetch_row($result));
		}else{
			return false;
		}
	}
}

// this is total overkill for this info but maybe it'll be something else later
class SurplusLocation {
	var $Location;

	function MakeSafe(){
		$this->Location=sanitize($this->Location);
	}

	function MakeDisplay(){
		$this->Location=stripslashes($this->Location);
	}

	static function RowToObject($row){
		$sl=new SurplusLocation();
		$sl->Location=$row["Location"];
		$sl->MakeDisplay();

		return $sl;
	}

	static function GetLocations(){
		global $facDB;
		$sql="SELECT * FROM vu_SurplusLocations ORDER BY Location ASC;";

		$result=mysql_query($sql,$facDB);

		$records=array();
		while($row=mysql_fetch_array($result)){
			$records[]=SurplusLocation::RowToObject($row);
		}

		return $records;
	}
}

// Surplus AJAX functions
if(isset($_REQUEST['vusurplus']) && $user->RackAdmin){
	header('Content-Type: application/json');
	$result=array();

	// Create new record
	if(isset($_POST['create'])){
		$s=new Surplus();
		$s->DevType=$_POST["devtype"];
		$s->Manufacturer=$_POST["manf"];
		$s->Model=$_POST["model"];
		$s->Serial=$_POST["serial"];
		$s->AssetTag=$_POST["assettag"];

		// If a new record is succesful, process the hard drives
		if($s->CreateSurplus()){
			// In case a drive fails to create we'll back the whole damn shebang back out
			// so this will track the new IDs being created.
			$drives=array();
			foreach($_POST["hd"] as $i => $serial){
				$hd=new SurplusHD();
				$hd->SurplusID=$s->SurplusID;
				$hd->Serial=$serial;
				$hd->Location=$_POST["location"];
				if($hd->CreateDrive()){
					$drives[]=$hd->DiskID;
				}else{
					// Fail and roll back
					$s->RemoveSurplus();
				}
			}
			$result=$s;
		}
	}

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

	if(isset($_POST['updatedrive']) && SurplusConfig::CheckUser($user->UserID)){
		foreach($_POST as $prop => $val){
			if(preg_match('/^hd[0-9]*/',$prop) && $val){
				$hd=new SurplusHD();
				$hd->SurplusID=$_POST['SurplusID'];
				$hd->DiskID=substr($prop,2);
				$hd->DestructionCertificationID=$val;
				if($hd->CertifyDestruction()){
					$result[]=$hd;
				}
			}
		}
	}

	if(isset($_POST['certifydrives']) && SurplusConfig::CheckUser($user->UserID)){
		$hd=new SurplusHD();
		$hd->UserID=$user->UserID;
		$hd->DestructionCertificationID=$_POST['DestructionCertificationID'];
		if($hd->CertifyDrives()){
			$result=true;
		}else{
			$result=false;
		}
	}

	if(isset($_POST['authorizeuser']) && $user->SiteAdmin){
		$newuser=new SurplusConfig();
		$newuser->UserID=$_POST['UserID'];

		if($newuser->CreateUser()){
			$result=$newuser;
		}
	}

	if(isset($_POST['removeuser']) && $user->SiteAdmin){
		$newuser=new SurplusConfig();
		$newuser->UserIndex=$_POST['UserIndex'];

		if($newuser->RemoveUser()){
			$result=true;
		}else{
			$result=false;
		}
	}

	if(isset($_GET['authorizedusers'])){
		$result=SurplusConfig::GetUsers();
	}

	if(isset($_GET['getlocations'])){
		$result=SurplusLocation::GetLocations();
	}

	if(isset($_GET['getdrives'])){
		$hd=new SurplusHD();
		$hd->SurplusID=$_GET['SurplusID'];
		$result=$hd->GetDrives();
	}

	if(isset($_GET['autocomplete'])){
		$validsearchkeys=array('SurplusID','Serial','AssetTag','DevType','Model','Manufacturer','UserID');
		$st='';
		foreach($_GET as $prop => $val){
			if(in_array($prop,$validsearchkeys)){
				$st=$prop;
			}else{
				$st=($st=='')?'SurplusID':$st;
			}
		}

		$searchresults=array();
		$sql="SELECT DISTINCT $st FROM vu_Surplus WHERE $st LIKE '%".addslashes($_GET[$st])."%';";
		$result=mysql_query($sql,$facDB);

		while($row=mysql_fetch_array($result)){
			$searchresults[]=$row[0];
		}
		$result=$searchresults;
	}

	echo json_encode($result);
	exit;
}
