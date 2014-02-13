<?php

// Make sure that the logbook item appears first in the main menu
array_unshift($rmenu , '<a href="elogs/"><span>'.__("Log Book").'</span></a>');

Class VUser extends User {
    // LDAP variables
    var $basedn;
    var $ldaprdn;
    var $ldappass;
    var $ldaphost;

	function __construct(){
		global $config;

		$this->basedn=$config->ParameterArray["log_BaseDN"];
		$this->ldaprdn=$config->ParameterArray["log_LDAPRN"];
		$this->ldappass=$config->ParameterArray["log_LDAPPass"];
		$this->ldaphost=$config->ParameterArray["log_LDAPHost"];
	}

	function ValidateCredentials($ldappass){
		$ldaprdn="uid=$this->UserID,$this->basedn";

		if($ldapconn=ldap_connect($this->ldaphost)){
			if($ldapbind=ldap_bind($ldapconn,$ldaprdn,$ldappass)){
				$retVal=true;
			}else{
				$retVal=false;
			}
			ldap_close($ldapconn);
		}

		return $retVal;
	}

	function SearchLDAP($filter,$limit,$return,$debug=false){
		if($ldapconn=ldap_connect($this->ldaphost,636)){
			if($debug){
				ldap_set_option($ldapconn,LDAP_OPT_PROTOCOL_VERSION,3);
				ldap_set_option($ldapconn,LDAP_OPT_DEBUG_LEVEL,7);
			}
			if($ldapbind=ldap_bind($ldapconn,$this->ldaprdn,$this->ldappass)){
				$searchBCA=ldap_search($ldapconn,$this->basedn,$filter,$limit);
				$entry=ldap_first_entry($ldapconn,$searchBCA);
				$attrs=ldap_get_attributes($ldapconn,$entry);
				$retVal=$attrs[$return][0];
			}else{
				$retVal="Not Found";
			}
			ldap_close($ldapconn);
		}else{
			$retVal="LDAP Connection Error";
		}
		return $retVal;
	}

	function GetBCA(){
		return $this->SearchLDAP("(uid=$this->UserID)",array("cn","mail"),"mail");
	}

	function GetName(){
		return $this->SearchLDAP("(uid=$this->UserID)",array("cn","mail"),"cn");
	}

	function GetUID(){
		return $this->SearchLDAP("(mail=$this->Email)",array("cn","uid"),"uid");
    }

    function GetPhoto(){
		global $config;
		
		$requestURL="{$config->ParameterArray["log_PhotoURL"]}$this->UserID";
		$options=array(
			CURLOPT_URL => $requestURL,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_FAILONERROR => true
			);

		$ch=curl_init();
		curl_setopt_array($ch,$options);

		$photoURL=trim(curl_exec($ch));

		if(curl_errno($ch)){
			return "";
		}else{
			return $photoURL;
		}
    }

	function CheckScanUser(){
		global $dbh;

		$this->MakeSafe();

		// Attempt to find a user we know about already the return their ID number
		if($this->UserID==""){
			$sql="SELECT `index` FROM fac_ScanningUsers WHERE email = '$this->Email';";
		}else{
			$sql="SELECT `index` FROM fac_ScanningUsers WHERE vunetid = '$this->UserID';";
		}
		if($index=$this->query($sql)->fetchColumn()){
			return $index;
		}else{ // If we don't find them, create them and return the new ID number
			if($this->UserID==""){
				$this->UserID=$this->GetUID();
			}else{
				$this->Email=$this->GetBCA();
			}
			if($this->UserID!=""){
				$sql="INSERT INTO fac_ScanningUsers VALUES (NULL, '$this->UserID', '$this->Email');";
				if($this->query($sql)){
					return $dbh->lastInsertId();
				}else{
					return "";
				}
			}else{
				return "";
			}
		}
	}
}

class Resource {
	var $ResourceID;
	var $CategoryID;
	var $Description;
	var $UniqueID;
	var $Active;
	var $Status;

	function MakeSafe(){
		$validStatuses=array('Available','Out','Reserved');

		$this->ResourceID=intval($this->ResourceID);
		$this->CategoryID=intval($this->CategoryID);
		$this->Description=addslashes(trim($this->Description));
		$this->UniqueID=addslashes(trim($this->UniqueID));
		$this->Active=intval($this->Active); // 1 or 0 only
		$this->Status=(in_array($this->Status,$validStatuses))?$this->Status:'Available';
	}

	function MakeDisplay(){
		$this->Description=stripslashes($this->Description);
		$this->UniqueID=stripslashes($this->UniqueID);
	}

	static function RowToObject($row){
		$res=new Resource();
		$res->ResourceID=$row["ResourceID"];
		$res->CategoryID=$row["CategoryID"];
		$res->Description=$row["Description"];
		$res->UniqueID=$row["UniqueID"];
		$res->Active=$row["Active"];
		$res->Status=$row["Status"];
		$res->MakeDisplay();

		return $res;
	}

	function search($sql){
		$resourceList=array();
		foreach($this->query($sql) as $row){
			$resourceList[]=Resource::RowToObject($row);
		}

		return $resourceList;
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function GetResource(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Resource WHERE ResourceID=$this->ResourceID;";
		
		if($row=$this->query($sql)->fetch()){
			foreach(Resource::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			$this->Description="Invalid";
			$this->Active="Invalid";
			$this->Status="Invalid";
		}
		
		return;
	}
	
	function GetResources(){
		$sql="SELECT * FROM fac_Resource ORDER BY CategoryID ASC, Description ASC;";

		return $this->search($sql);
	}

	function GetActiveResources(){
		$sql="SELECT * FROM fac_Resource WHERE Active=true ORDER BY CategoryID ASC, Description ASC;";
		
		return $this->search($sql);
	}
	
	function GetActiveResourcesByCategory(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Resource WHERE Active=true AND CategoryID=$this->CategoryID ORDER BY Description ASC;";
		
		return $this->search($sql);
	}
	
	function CreateResource(){
		global $dbh;

		$this->MakeSafe();

		// basic sanity check
		if($this->CategoryID <1 || sizeof($this->Description)==0){
			return false;
		}

		$sql="INSERT INTO fac_Resource SET CategoryID=$this->CategoryID, 
			Description=\"$this->Description\", UniqueID=\"$this->UniqueID\",
			Active=$this->Active, Status=\"Available\";";
	
		if($this->query($sql)){	
			$this->ResourceID=$dbh->lastInsertId();
			return true;
		}else{
			return false;
		}
	}
  
	function UpdateResource(){
		$this->MakeSafe();

		$sql="UPDATE fac_Resource SET CategoryID=$this->CategoryID, 
			Description=\"$this->Description\", UniqueID=\"$this->UniqueID\", 
			Active=$this->Active, Status=\"$this->Status\" WHERE
			ResourceID=$this->ResourceID;";
		
		return $this->query($sql);
	}

	function ClearReservation(){
		$this->MakeSafe();

		$sql="UPDATE fac_Resource SET Status='Available' WHERE `ResourceID`=$this->ResourceID;";
		
		return $this->query($sql);
	}

	function ExpireReservations(){
		// removes reservations that are older than 15 mintues
		$sql="SELECT ResourceID FROM fac_Resource WHERE Status=\"Reserved\";";
		foreach($this->query($sql) as $row){  // can contain multiple values to expire
			$subsql="SELECT DATE_ADD(`RequestedTime`, INTERVAL +15 MINUTE) AS FutureTime 
				FROM `fac_ResourceLog` WHERE ResourceID={$row["ResourceID"]} ORDER BY 
				RequestedTime DESC LIMIT 1;";
			if($futuretime=$this->query($subsql)->fetchColumn()){
				if(strtotime($futuretime)<strtotime("now")){
					$die="UPDATE fac_Resource SET Status = 'Available' WHERE ResourceID={$row["ResourceID"]};";
					$this->query($die);
				}
			}
		}
	}

	function ReservationTTL(){
		$this->MakeSafe();

		// return how much time a reservation has left before it expires
		$sql="SELECT DATE_ADD(RequestedTime, INTERVAL +15 MINUTE) AS FutureTime FROM 
			fac_ResourceLog WHERE ResourceID=$this->ResourceID ORDER BY 
			RequestedTime DESC LIMIT 1;";

		$reqtime=$this->query($sql)->fetchColumn();
		return strtotime($reqtime)-strtotime("now");
	}


}

class ResourceCategory {
	var $CategoryID;
	var $Description;

	function MakeSafe(){
		$this->CategoryID=intval($this->CategoryID);
		$this->Description=addslashes(trim($this->Description));
	}

	function MakeDisplay(){
		$this->Description=stripslashes($this->Description);
	}

	static function RowToObject($row){
		$cat=new ResourceCategory();
		$cat->CategoryID=$row["CategoryID"];
		$cat->Description=$row["Description"];
		$cat->MakeDisplay();

		return $cat;
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function GetCategory(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_ResourceCategory WHERE CategoryID=$this->CategoryID;";

		if($row=$this->query($sql)->fetch()){
			foreach(ResourceCategory::RowToObject($row)	as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}		
	}
	
	function GetCategoryList(){
		$sql="SELECT * FROM fac_ResourceCategory ORDER BY Description ASC;";

		$catList=array();
		foreach($this->query($sql) as $row){
			$catList[]=ResourceCategory::RowToObject($row);
		}
		
		return $catList;
	}
}

class ResourceLog {
	var $ResourceID;
	var $VUNetID;
	var $Note;
	var $RequestedTime;
	var $TimeOut;
	var $EstimatedReturn;
	var $ActualReturn;

	function MakeSafe(){
		$this->ResourceID=intval($this->ResourceID);
		$this->VUNetID=addslashes(trim($this->VUNetID));
		$this->Note=addslashes(trim($this->Note));
		$this->RequestedTime=addslashes(trim($this->RequestedTime));
		$this->TimeOut=addslashes(trim($this->TimeOut));
		$this->EstimatedReturn=addslashes(trim($this->EstimatedReturn));
		$this->ActualReturn=addslashes(trim($this->ActualReturn));
	}

	function MakeDisplay(){
		$this->VUNetID=stripslashes($this->VUNetID);
		$this->Note=stripslashes($this->Note);
		$this->RequestedTime=stripslashes($this->RequestedTime);
		$this->TimeOut=stripslashes($this->TimeOut);
		$this->EstimatedReturn=stripslashes($this->EstimatedReturn);
		$this->ActualReturn=stripslashes($this->ActualReturn);
	}

	static function RowToObject($row){
		$log=new ResourceLog();
		$log->ResourceID=$row["ResourceID"];
		$log->VUNetID=$row["VUNetID"];
		$log->Note=$row["Note"];
		$log->RequestedTime=$row["RequestedTime"];
		$log->TimeOut=$row["TimeOut"];
		$log->EstimatedReturn=$row["EstimatedReturn"];
		$log->ActualReturn=$row["ActualReturn"];
		$log->MakeDisplay();

		return $log;
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function GetCurrentStatus(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_ResourceLog WHERE ResourceID=$this->ResourceID ORDER 
			BY RequestedTime DESC LIMIT 1;";

		if($row=$this->query($sql)->fetch()){
			foreach(ResourceLog::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
 
	function RequestResource(){
		$this->MakeSafe();

		$res=new Resource();
		$res->ResourceID=$this->ResourceID;
		$res->GetResource();

		if($res->Status=="Available" && $res->Active==true){
			$res->Status="Reserved";
			$res->UpdateResource();
			
			$sql="INSERT INTO fac_ResourceLog SET ResourceID=$this->ResourceID, 
				VUNetID=\"$this->VUNetID\", Note=\"$this->Note\", RequestedTime=now(), 
				EstimatedReturn=\"$this->EstimatedReturn\";";

			if($this->query($sql)){
				return true;
			}
		}
		return false;
	}
	
	function CheckoutResource(){
		$this->MakeSafe();

		$res=new Resource();
		$res->ResourceID=$this->ResourceID;
		$res->GetResource();

		if($res->Status=="Reserved" && $res->Active==true){
			$res->Status="Out";
			$res->UpdateResource($db);

			$sql="UPDATE fac_ResourceLog SET TimeOut=now() WHERE 
				ResourceID=$this->ResourceID AND TimeOut='0000-00-00 00:00:00';";

			if($this->query($sql)){
				return true;
			}
		}
		return false;
	}

	function CheckinResource(){
		$this->MakeSafe();

		$res=new Resource();
		$res->ResourceID=$this->ResourceID;
		$res->GetResource();
		
		if($res->Status=="Out" && $res->Active==true){
			$res->Status="Available";
			$res->UpdateResource();

			$sql="UPDATE fac_ResourceLog SET ActualReturn=now() WHERE 
				ResourceID=$this->ResourceID AND ActualReturn=\"0000-00-00 00:00:00\";";

			if($this->query($sql)){
				return true;
			}
		}
		return false;
	}
}


class DataCenterLog {
	var $EntryID;
	var $DataCenterID;
	var $VUNetID;
	var $EscortRequired;
	var $RequestTime;
	var $Reason;
	var $AuthorizedBy;
	var $TimeIn;
	var $TimeOut;
	var $GuestList;
	var $EventType;

	function MakeSafe(){
		$validEventTypes=array('dcaccess','safe');

		$this->EntryID=intval($this->EntryID);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->VUNetID=addslashes(trim($this->VUNetID));
		$this->EscortRequired=intval($this->EscortRequired);
		$this->RequestTime=addslashes(trim($this->RequestTime));
		$this->Reason=addslashes(trim($this->Reason));
		$this->AuthorizedBy=addslashes(trim($this->AuthorizedBy));
		$this->TimeIn=addslashes(trim($this->TimeIn));
		$this->TimeOut=addslashes(trim($this->TimeOut));
		$this->GuestList=addslashes(trim($this->GuestList));
		$this->EventType=(in_array($this->EventType,$validEventTypes))?$this->EventType:'dcaccess';
	}

	function MakeDisplay(){
		$this->VUNetID=stripslashes($this->VUNetID);
		$this->RequestTime=stripslashes($this->RequestTime);
		$this->Reason=stripslashes($this->Reason);
		$this->AuthorizedBy=stripslashes($this->AuthorizedBy);
		$this->TimeIn=stripslashes($this->TimeIn);
		$this->TimeOut=stripslashes($this->TimeOut);
		$this->GuestList=stripslashes($this->GuestList);
	}

	static function RowToObject($row){
		$dclog=new DataCenterLog();
		$dclog->EntryID=$row["EntryID"];
		$dclog->DataCenterID=$row["DataCenterID"];
		$dclog->VUNetID=$row["VUNetID"];
		$dclog->EscortRequired=$row["EscortRequired"];
		$dclog->RequestTime=$row["RequestTime"];
		$dclog->Reason=$row["Reason"];
		$dclog->AuthorizedBy=$row["AuthorizedBy"];
		$dclog->TimeIn=$row["TimeIn"];
		$dclog->TimeOut=$row["TimeOut"];
		$dclog->GuestList=$row["GuestList"];
		$dclog->EventType=$row["EventType"];
		$dclog->MakeDisplay();

		return $dclog;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function AddRequest(){
		global $dbh;

		$sql="INSERT INTO fac_DataCenterLog SET DataCenterID=$this->DataCenterID, 
			VUNetID=\"$this->VUNetID\", EscortRequired=$this->EscortRequired, 
			RequestTime=now(), Reason=\"$this->Reason\", GuestList=\"$this->GuestList\", 
			EventType=\"$this->EventType\";";

		if($this->query($sql)){
			$this->EntryID=$dbh->lastInsertId();
			$this->MakeDisplay();
			return true;
		}else{
			return false;
		}
	}

	function GetRequest(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DataCenterLog WHERE EntryID=$this->EntryID;";

		if($row=$this->query($sql)->fetch()){
			foreach(DataCenterLog::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
		return false;
	}

	function GetOpenRequests(){
		$sql="SELECT * FROM fac_DataCenterLog WHERE TimeIn='0000-00-00 00:00:00' AND 
			TimeOut='0000-00-00 00:00:00' ORDER BY RequestTime ASC;";
	
		$reqList=array();
		foreach($this->query($sql) as $row){
			$reqList[]=DataCenterLog::RowToObject($row);
		}
		
		return $reqList;
	}
	
	function GetOpenRequestsByDC(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DataCenterLog WHERE DataCenterID=$this->DataCenterID
			AND TimeIn='0000-00-00 00:00:00' AND TimeOut='0000-00-00 00:00:00' ORDER BY 
			RequestTime ASC;";
	
		$reqList=array();
		foreach($this->query($sql) as $row){
			$reqList[]=DataCenterLog::RowToObject($row);
		}
		
		return $reqList;
	}
	
	function RemoveRequest(){
		$sql="UPDATE fac_DataCenterLog SET TimeIn=now(), TimeOut=now(), 
			AuthorizedBy=\"DENIED\" WHERE EntryID=$this->EntryID;";

		return $this->query($sql);
	}

	function ApproveEntry(){
		$this->MakeSafe();

		$sql="UPDATE fac_DataCenterLog SET AuthorizedBy=\"$this->AuthorizedBy\", 
			TimeIn=now() WHERE EntryID=$this->EntryID;";
		
		return $this->query($sql);
	}
	
	function GetDCOccupants(){
		$sql="SELECT * FROM fac_DataCenterLog WHERE TimeIn>'0000-00-00 00:00:00' AND
			TimeOut='0000-00-00 00:00:00';";
		
		$reqList=array();
		foreach($this->query($sql) as $row){
			$reqList[]=DataCenterLog::RowToObject($row);
		}
		
		return$reqList;
	}

	function CloseEntry(){
		$sql="UPDATE fac_DataCenterLog SET TimeOut=now() WHERE EntryID=$this->EntryID 
			AND TimeOut='0000-00-00 00:00:00';";
	    
	 	return $this->query($sql);
	}
}

function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = false) {
    $distanceInSeconds = round(abs($toTime - $fromTime));
    $distanceInMinutes = round($distanceInSeconds / 60);

        if ( $distanceInMinutes <= 1 ) {
            if ( !$showLessThanAMinute ) {
                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 minute';
            } else {
                if ( $distanceInSeconds < 5 ) {
                    return 'less than 5 seconds';
                }
                if ( $distanceInSeconds < 10 ) {
                    return 'less than 10 seconds';
                }
                if ( $distanceInSeconds < 20 ) {
                    return 'less than 20 seconds';
                }
                if ( $distanceInSeconds < 40 ) {
                    return 'about half a minute';
                }
                if ( $distanceInSeconds < 60 ) {
                    return 'less than a minute';
                }

                return '1 minute';
            }
        }
        if ( $distanceInMinutes < 45 ) {
            return $distanceInMinutes . ' minutes';
        }
        if ( $distanceInMinutes < 90 ) {
            return 'about 1 hour';
        }
        if ( $distanceInMinutes < 1440 ) {
            return 'about ' . round(floatval($distanceInMinutes) / 60.0) . ' hours';
        }
        if ( $distanceInMinutes < 2880 ) {
            return '1 day';
        }
        if ( $distanceInMinutes < 43200 ) {
            return 'about ' . round(floatval($distanceInMinutes) / 1440) . ' days';
        }
        if ( $distanceInMinutes < 86400 ) {
            return 'about 1 month';
        }
        if ( $distanceInMinutes < 525600 ) {
            return round(floatval($distanceInMinutes) / 43200) . ' months';
        }
        if ( $distanceInMinutes < 1051199 ) {
            return 'about 1 year';
        }

        return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' years';
}


class ScanJob {
	var $ScanID;
	var $DateSubmitted;
	var $DateScanned;
	var $DatePickedUp;
	var $CourseNumber;
	var $Section;
	var $Authorized;
	var $NumForms;
	var $NOCAnalyst;
	var $Notes;
	var $Dropoff;
	var $Pickup;

	function MakeSafe(){
		$this->ScanID=intval($this->ScanID);
		$this->DateSubmitted=addslashes(trim($this->DateSubmitted));
		$this->DateScanned=addslashes(trim($this->DateScanned));
		$this->DatePickedUp=addslashes(trim($this->DatePickedUp));
		$this->CourseNumber=addslashes(trim($this->CourseNumber));
		$this->Section=intval($this->Section);
		$this->Authorized=intval($this->Authorized);
		$this->NumForms=intval($this->NumForms);
		$this->NOCAnalyst=addslashes(trim($this->NOCAnalyst));
		$this->Dropoff=addslashes(trim($this->Dropoff));
		$this->Pickup=addslashes(trim($this->Pickup));
		$this->Notes=addslashes(trim($this->Notes));
	}

	function MakeDisplay(){
		$this->DateSubmitted=stripslashes($this->DateSubmitted);
		$this->DateScanned=stripslashes($this->DateScanned);
		$this->DatePickedUp=stripslashes($this->DatePickedUp);
		$this->CourseNumber=stripslashes($this->CourseNumber);
		$this->NOCAnalyst=stripslashes($this->NOCAnalyst);
		$this->Dropoff=stripslashes($this->Dropoff);
		$this->Pickup=stripslashes($this->Pickup);
		$this->Notes=stripslashes($this->Notes);
	}

	static function RowToObject($row){
		$scanjob=new ScanJob();
		$scanjob->ScanID=$row['ScanID'];
		$scanjob->DateSubmitted=$row['DateSubmitted'];
		$scanjob->DateScanned=$row['DateScanned'];
		$scanjob->DatePickedUp=$row['DatePickedUp'];
		$scanjob->CourseNumber=$row['CourseNumber'];
		$scanjob->Section=$row['Section'];
		$scanjob->Authorized=$row['Authorized'];
		$scanjob->NumForms=$row['NumForms'];
		$scanjob->NOCAnalyst=$row['NOCAnalyst'];
		$scanjob->Dropoff=$row['Dropoff'];
		$scanjob->Pickup=$row['Pickup'];
		$scanjob->Notes=$row['Notes'];
		$scanjob->MakeDisplay();

		return $scanjob;
	}

	function search($sql){
		$scanjobs=array();
		foreach($this->query($sql) as $row){
			$scanjobs[$row['ScanID']]=ScanJob::RowToObject($row);
		}

		return $scanjobs;
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function Create(){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_ScanningLog SET DateSubmitted=\"$this->DateSubmitted\", 
			CourseNumber=\"$this->CourseNumber\", Section=$this->Section, 
			Dropoff=\"$this->Dropoff\";";

		if($this->query($sql)){
			$this->ScanID=$dbh->lastInsertId();
			return true;
		}else{
			return false;
		}
	}

	function GetRecord(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_ScanningLog WHERE ScanID=$this->ScanID;";
		if($row=$this->query($sql)->fetch()){
			foreach(ScanJob::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
		return false;
	}

	function GetOpenJobs(){
		$sql="SELECT * FROM fac_ScanningLog WHERE DateScanned IS NULL;";
		return $this->search($sql);
	}

	function GetWaitingJobs(){
		$sql="SELECT * FROM fac_ScanningLog WHERE DateScanned IS NOT NULL AND 
			(DatePickedUp IS NULL OR (Authorized=1 AND Notes IS NULL));";
		return $this->search($sql);
	}

	function GetCompletedJobs($startdate=null,$enddate=null){
		(is_null($startdate))?$startdate=date('Y-m-d H:i',strtotime(' -1 day')):"";
		(is_null($enddate))?$enddate=date('Y-m-d H:i'):"";
		$sql="SELECT * FROM fac_ScanningLog WHERE DatePickedUp BETWEEN '$startdate' 
			AND '$enddate' AND (Authorized=0 OR (Authorized=1 AND Notes IS NOT NULL));";
		return $this->search($sql);
	}

	function ScanningJob($userarray){
		$this->MakeSafe();

		$sql="UPDATE fac_ScanningLog SET DateScanned='$this->DateScanned', 
			NOCAnalyst='$this->NOCAnalyst', NumForms=$this->NumForms WHERE 
			ScanID=$this->ScanID;";

		if($this->query($sql)){
			foreach($userarray as $id){
				$this->AddAuthorizedUsers($id);
			}
			return true;
		}else{
			return false;
		}
	}

	function PickupJob(){
		$this->MakeSafe();

		$sql="UPDATE fac_ScanningLog SET DatePickedUp='$this->DatePickedUp', 
			Authorized=$this->Authorized, Pickup='$this->Pickup' WHERE 
			ScanID=$this->ScanID;";
		return $this->query($sql);
	}

	function Override(){
		$this->MakeSafe();

		$sql="UPDATE fac_ScanningLog SET Notes='$this->Notes' WHERE 
			ScanID=$this->ScanID;";
		return $this->query($sql);
	}

	function AddAuthorizedUsers($id){
		$this->MakeSafe();

		$sql="INSERT INTO fac_ScanningUsersAuth VALUES ($this->ScanID,".intval($id).");";
		return $this->query($sql);
	}

	function GetAuthorizedUsers(){
		$this->MakeSafe();

		$sql="SELECT fac_ScanningUsersAuth.index, fac_ScanningUsers.email FROM 
			`fac_ScanningUsersAuth` LEFT JOIN fac_ScanningUsers ON 
			fac_ScanningUsersAuth.index = fac_ScanningUsers.index WHERE 
			ScanID=$this->ScanID;";

		$users=array();
		foreach($this->query($sql) as $row){
			$users[$row['index']]=stripslashes($row['email']);
		}
		return $users;
	}
}

?>
