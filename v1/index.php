<?php
session_cache_limiter(false);
session_start();
require 'functions.php';

$app = new Slim();

$app->post('/login', 'login');  //00
$app->get('/register/:imei', 'register');  //01
$app->get('/registergcm/:imei/:gcmid', 'registerGcm');  //02
$app->get('/staticdata', 'getStaticData');  //03
$app->get('/contacts/:lastid', 'getContacts');  //04
$app->get('/deals/:page', 'getDeals');  //05
$app->get('/dashboards', 'getDashboards');  //06
$app->get('/notdepositedreceipts/:page', 'getNotDepositedReceipts');  //07 (Pending)
$app->get('/deposithistory/:page', 'getDepositHistory');  //08 (Pending)
$app->get('/daywisecollection', 'getDaywiseCollection');  //09
$app->get('/searchdeals/:query/:page', 'searchDeals');  //10
$app->get('/deal/:dealid', 'getDeal');  //11
$app->post('/postlogs', 'postLogs');  //12
$app->get('/dues/:dealid/:foreclosure', 'getDues');  //13 (Pending)
$app->post('/postdues', 'postDues');  //14 (Pending)
$app->get('/deallogs/:dealid', 'getDealLogs');  //15
$app->get('/dealledgers/:dealid', 'getDealLedger');  //16
$app->post('/postmobileno', 'postMobileNo');  //17 (Pending)
$app->post('/postaddress', 'postAddress');  //18 (Pending)
$app->get('/rtoregno/:dealid/:regno/:nocflag', 'postRTORegNo');  //19
$app->post('/postbankdeposit', 'postBankDeposit');  //20 (Pending)
$app->post('/updateappinfo', 'updateAppInfo');  //21
$app->post('/updatelastlogin', 'updateLastLogin');  //22
$app->get('/notifications/:lastid', 'getNotifications');  //23
$app->get('/updateddeals/:lasttimestamp', 'getUpdatedDeals');  //24
$app->post('/postdiagnosticlogs', 'PostDiagnosticLogs');  //25 (Pending)
$app->post('/postescalation', 'PostEscalation');  //26 (Pending)
$app->get('/sendsms', 'sendsms');

$app->run();

//TO-DO : Replace e.oldid with empid.
//00
function login(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();

	$pin = $request->params('pin');
	$imei = $request->params('imei');

	if (!ctype_digit($pin)){
		$response = error_code(1002);
		echo json_encode($response);
		return;
	}
	if (!ctype_digit($imei)){
		$response = error_code(1003);
		echo json_encode($response);
		return;
	}
	//$sql = "select empid from ".$dbPrefix.".tbmdevices where apppin = '$pin' and imei = '$imei'";

	$sql = "select e.oldid as empid from ".$dbPrefix.".tbmdevices d join ".$dbPrefix.".tbmemployee e on e.id = d.empid where d.apppin = '$pin' and d.imei = '$imei'";
	$empid = executeSingleSelect($sql);

	$response = array();
		if($empid>0){
		    $_SESSION['userid'] = $empid;
		 	$response["success"] = 1;
		 	$response["message"] = 'Successfully Logged In';
		}
		else{
			$response = error_code(1000);
		}
	echo json_encode($response);
}

//TO-DO : Replace e.oldid with e.id.
//01
function register($imei){
	$dbPrefix = $_SESSION['DB_PREFIX'];
    if (!ctype_digit($imei)){
    	$response = error_code(1003);
		echo json_encode($response);
		return;
	}
	$sql = "select e.oldid as id,tcase(e.name) as name,d.apppin as pin,e.mobile,e.email,e.photourl as photo_url,e.department,e.designation,e.role,tcase(e.centre) as centre,e.walletlimit as wallet_limit,d.printerid as printer_id,d.appversion as app_version,d.admindsn as admin_dsn,d.serviceurl as service_url from ".$dbPrefix.".tbmemployee e join ".$dbPrefix.".tbmdevices d on e.id = d.empid and d.active=1 where d.imei = '$imei' and e.active=1";

	$emp = executeSelect($sql);

	$response = array();
	if($emp['row_count']>0){
	    $response["success"] = 1;
	    $_SESSION['userid'] = $emp['result'][0]['id'];
	}
	else{
		$response = error_code(1005);
		echo json_encode($response);
		return;
	}
	$response["employee"] = $emp;
	echo json_encode($response);
}

//TO-Do : Replace $empid1 with $empid.
//02
function registerGcm($imei,$gcmid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	check_session();
	$empid = $_SESSION['userid'];

	if (!ctype_digit($imei)){
		$response = error_code(1003);
		echo json_encode($response);
		return;
	}
    if(!ctype_alnum($gcmid)){
    	$response = error_code(1005);
		echo json_encode($response);
		return;
	}

    //To-Do : Remove this query & Replace $empid1 with $empid.
	$sql_empid = "select id from ".$dbPrefix.".tbmemployee where oldid=$empid and active=1";
	$empid1 = executeSingleSelect($sql_empid);

	$sql_update = "update ".$dbPrefix.".tbmdevices set gcmid = '$gcmid' where empid=$empid1 and imei = '$imei'";
	$affectedrows = executeUpdate($sql_update);

	$response = array();
	if($affectedrows>0){
		$response["success"] = 1;
		$response["message"] = 'GCM Id Successfully Registered';
	}
	else{
		$response = error_code(1005);
	}
	echo json_encode($response);
}


//03
function getStaticData(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	check_session();
	$empid = $_SESSION['userid'];

    $sql = "select sql_calc_found_rows distinct(sb.bankid),sb.banknm from ".$dbPrefix.".tbmsourcebank sb join ".$dbPrefix.".tbaposbankbranch pb on sb.bankid = pb.bankid where pb.empid = $empid";
	$bank = executeSelect($sql);

	foreach($bank['result'] as $i=> $static){
		$bankid = $static['bankid'];
		$sql_branchnm = "select sql_calc_found_rows bb.BankBrnchId as branchid,bb.BankBrnchNm as branch,bb.city from ".$dbPrefix.".tbmsourcebankbrnch bb join ".$dbPrefix.".tbaposbankbranch pb on bb.BankBrnchId = pb.branchid where bb.bankid=$bankid and pb.empid = $empid";
		$branchnm = executeSelect($sql_branchnm);
		$bank['result'][$i]['branchname'] = $branchnm;
 	}
    $staticdata['bank']=$bank;

	$states = array(array("state"=> "Maharashtra"), array("state"=> "Madhya Pradesh"));
	$state["row_count"] = count($states);
	$state["found_rows"] = count($states);
	$state['result'] = $states;
	$staticdata['states'] = $state;

   	$sql_logs = "select Description as tag from ".$dbPrefix.".tbmrecoverytags where allowtagto = 0";
	$logs = executeSelect($sql_logs);
	$staticdata['logreason']=$logs;

   	$relationship = array();
	$relationship[0]["tag"]='Mother';
	$relationship[1]["tag"]='Father';
	$relationship[2]["tag"]='Wife';
	$relationship[3]["tag"]='Husband';
	$relationship[4]["tag"]='Brother';
	$relationship[5]["tag"]='Son/Daughter';
	$relationship[6]["tag"]='Neighbour';
	$relation["row_count"]=count($relationship);
	$relation["found_rows"]=count($relationship);
	$relation['result']=$relationship;
	$staticdata['relationship']=$relation;

   	$dctype = array();
   	$dctype[0]["type"]='101';
   	$dctype[0]["name"]='EMI';
   	$dctype[1]["type"]='102';
   	$dctype[1]["name"]='Clearing';
   	$dctype[2]["type"]='103';
   	$dctype[2]["name"]='CB';
   	$dctype[3]["type"]='104';
   	$dctype[3]["name"]='Penalty';
   	$dctype[4]["type"]='105';
   	$dctype[4]["name"]='Seizing';
   	$dctype[5]["type"]='107';
   	$dctype[5]["name"]='Other';
   	$dctype[6]["type"]='111';
   	$dctype[6]["name"]='CC';
	$dc["row_count"]=count($dctype);
	$dc["found_rows"]=count($dctype);
	$dc['result']=$dctype;
	$staticdata['dctype']=$dc;

 	$response["staticdata"] = $staticdata;
 	echo json_encode($response);
}


//04
function getContacts($lastid){
	$dbPrefix = $_SESSION['DB_PREFIX'];

	$sql = "select id,tcase(name) as name,mobile,designation,tcase(centre) as centre, null as photo_url from ".$dbPrefix.".tbmemployee where active=1 and id > '$lastid' ORDER BY id ASC";

	$contacts = executeSelect($sql);

	$response = array();
	if($contacts['row_count']>0){
		$response["success"] = 1;
	}
	else{
		$response = error_code(1007);
		echo json_encode($response);
		return;
	}
	$response["contacts"] = $contacts;
	echo json_encode($response);
}


//05
function getDeals($page) {
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	$limit = $_SESSION['API_ROW_LIMIT'];
	$start = ($page-1) * $limit;
	check_session();
	$sraid = $_SESSION['userid'];

    $sql = "select sql_calc_found_rows fr.dealid, fr.dealid, fr.dealno, tcase(fr.dealnm) as name,tcase(d.centre) as centre, tcase(fr.area) as area, tcase(fr.city) as city, tcase(fr.address) as address, fr.mobile, DATE_FORMAT(fr.hpdt, '%d-%m-%Y') as hpdt, round(fr.dueamt) as total_due,round(fr.OdDueAmt) as overdue, fr.dd as assigned_on, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt, fr.rgid as bucket,round(d.financeamt) as finance_amt,round(fr.emi) as emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d') as emi_day, (case when d.paytype=1 then 'PDC' when d.paytype=2 then 'ECS' when d.paytype=3 then 'Direct Debit' end) as type, tcase(concat(dv.make, ' ', dv.model)) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no, tcase(b.BrkrNm) as dealer, tcase(trim(concat(ifnull(b.city,''), ' ', case when b.centre != b.city then b.centre else '' end))) as dealer_loc, fr.SalesmanId as salesman_id,null as salesman_name,null as salesman_mobile
	FROM ".$dbPrefix_curr.".tbxfieldrcvry fr
	join ".$dbPrefix.".tbmdeal d
	join ".$dbPrefix.".tbmdealvehicle dv
	join ".$dbPrefix.".tbmbroker b
	on fr.dealid = d.dealid and fr.dealid=dv.dealid and d.brkrid = b.brkrid where fr.mm = ".date('n')." and fr.sraid = $sraid
	ORDER BY fr.dd desc limit $start, $limit";

	$deals = executeSelect($sql);
	$deals1 = deal_details($deals);

	$response = array();
		if($deals1['row_count']>0){
			$response["success"] = 1;
		}
		else{
			$response = error_code(1008);
			echo json_encode($response);
			return;
		}
	$response["deals"] = $deals1;
	echo json_encode($response);
}


//06
function getDashboards(){
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	check_session();
	$sraid = $_SESSION['userid'];

	//TO-DO: Optimize for 6 months in case of current financial year has already crossed 6 months.
	$sql = "SELECT * FROM (
	SELECT yy, mm, sraid as empid, sranm as empname, collection, od, penalty, bouncing, others, assigned_fd, recovered_fd, assigned_dm, recovered_dm, assigned, recovered, assigned_b1, recovered_b1, assigned_b2, recovered_b2, assigned_b3, recovered_b3, assigned_b4, recovered_b4, assigned_b5, recovered_b5, assigned_b6, recovered_b6, target_fd FROM ".$dbPrefix_curr.".tbxdashboard WHERE sraid = $sraid
	UNION
	SELECT yy, mm, sraid as empid, sranm as empname, collection, od, penalty, bouncing, others, assigned_fd, recovered_fd, assigned_dm, recovered_dm, assigned, recovered, assigned_b1, recovered_b1, assigned_b2, recovered_b2, assigned_b3, recovered_b3, assigned_b4, recovered_b4, assigned_b5, recovered_b5, assigned_b6, recovered_b6, target_fd FROM ".$dbPrefix_last.".tbxdashboard WHERE sraid = $sraid
	)t1 ORDER BY yy DESC, mm DESC LIMIT 0, 5";

	$dashboard = executeSelect($sql);

	$response = array();
		if($dashboard['row_count']>0){
			$response["success"] = 1;
		}
		else{
			$response = error_code(1029);
			echo json_encode($response);
			return;
		}
	$response["dashboard"] = $dashboard;
	echo json_encode($response);
	//echo '{"dashboards": ' . json_encode($dashboard) . '}';
}


//07
function getNotDepositedReceipts($page){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	$limit = $_SESSION['API_ROW_LIMIT'];
	$start = ($page-1) * $limit;
}


//08
function getDepositHistory($page){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	$limit = $_SESSION['API_ROW_LIMIT'];
	$start = ($page-1) * $limit;
}


//09
function getDaywiseCollection(){
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	check_session();
	$sraid = $_SESSION['userid'];

	$sql = "SELECT * FROM (
	SELECT count(distinct dealid) as recovered_cases,round(sum(TotRcptAmt)) as amount,rcptdt from ".$dbPrefix_curr.".tbxdealrcpt where RcptPayMode=1 and CclFlg = 0 and sraid = $sraid group by rcptdt
	UNION
	SELECT count(distinct dealid) as recovered_cases,round(sum(TotRcptAmt)) as amount,rcptdt from ".$dbPrefix_last.".tbxdealrcpt where RcptPayMode=1 and CclFlg = 0 and sraid = $sraid group by rcptdt)t1 ORDER BY rcptdt DESC LIMIT 31";

    $collection = executeSelect($sql);

    $response = array();
		if($collection['row_count']>0){
			$response["success"] = 1;
		}
		else{
		    $response = error_code(1009);
			echo json_encode($response);
			return;
		}
	$response["daywisecollection"] = $collection;
	echo json_encode($response);
}


//10
function searchDeals($query,$page){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$limit = $_SESSION['API_ROW_LIMIT'];
	$start = ($page-1) * $limit;
	check_session();
	$sraid = $_SESSION['userid'];

	if (!ctype_alnum($query)){
		$response = error_code(1010);
		echo json_encode($response);
		return;
	}
	else{
	 	$q = "SELECT sql_calc_found_rows d.dealid, d.dealno, tcase(d.dealnm) as name, tcase(d.city) as city, tcase(d.area) as area, trim(concat(d.add1, ' ', d.add2, ' ', d.area, ' ', d.tahasil)) as address, round(fr.OdDueAmt) as overdue, round(fr.DueAmt) as total_due, fr.rgid as bucket, fr.Mobile as mobile, fr.GuarantorMobile as guarantor_mobile, tcase(fr.model) as vehicle_model, fr.dd as assigned_on, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt from ".$dbPrefix.".tbmdeal d LEFT JOIN ".$dbPrefix_curr.".tbxfieldrcvry fr ON d.dealid = fr.dealid and fr.mm = ".date('n')." WHERE 1 ";

		if(is_numeric($query)){
			if(strlen($query) < 6)
				$query = str_pad($query, 6, "0", STR_PAD_LEFT);
			$q .= " AND (d.dealno = '$query')";
		}
		else{
			$q .= " AND (d.dealnm like '%$query%' or d.city like '%$query%' or d.area like '%$query%' or d.tahasil like '%$query%' or d.add1 like '%$query%' or d.add2 like '%$query%')";
		}
		$q .= " limit  $start, $limit";
		$search = executeSelect($q);
	}
	$response = array();
	if($search['row_count']>0){
		$response["success"] = 1;
	}
	else{
		$response = error_code(1008);
		echo json_encode($response);
		return;
	}
	$response["deals"] = $search;
	echo json_encode($response);
}


//11
function getDeal($dealid) {
	$dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];

	$sql = "select d.dealid, d.dealno, tcase(d.dealnm) as name,tcase(d.centre) as centre, tcase(d.area) as area, tcase(d.city) as city, concat(tcase(d.add1),' ',tcase(d.add2)) as address, d.mobile, DATE_FORMAT(d.hpdt, '%d-%m-%Y') as hpdt, round(fr.dueamt) as total_due,round(fr.OdDueAmt) as overdue, fr.dd as assigned_on, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt, fr.rgid as bucket,round(d.financeamt) as finance_amt,round(ps.MthlyAmt+ps.CollectionChrgs) as emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d') as emi_day, (case when d.paytype=1 then 'PDC' when d.paytype=2 then 'ECS' when d.paytype=3 then 'Direct Debit' end) as type, tcase(concat(dv.make, ' ', dv.model)) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no, tcase(b.BrkrNm) as dealer, tcase(trim(concat(ifnull(b.city,''), ' ', case when b.centre != b.city then b.centre else '' end))) as dealer_loc, fr.SalesmanId as salesman_id,null as salesman_name,null as salesman_mobile
	FROM ".$dbPrefix.".tbmdeal d
	join ".$dbPrefix.".tbmdealvehicle dv on d.dealid=dv.dealid and d.dealid = $dealid
	join ".$dbPrefix.".tbmbroker b on d.brkrid = b.brkrid
	join lksa.tbmpmntschd ps on d.dealid = ps.dealid
	left join ".$dbPrefix_curr.".tbxfieldrcvry fr on fr.dealid = d.dealid and fr.mm = ".date('n');

	$deal = executeSelect($sql);
	$deal1 = deal_details($deal);

	$response = array();
	if($deal1['row_count']>0){
		$response["success"] = 1;
	}
	else{
		$response = error_code(1011);
		echo json_encode($response);
		return;
	}
	$response["deal"] = $deal1;
	echo json_encode($response);
}

//12
function postLogs(){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$request = Slim::getInstance()->request();
	check_session();
	$sraid = $_SESSION['userid'];

	$followup_dt = date('Y-m-d');
	$dealid = $request->params('dealid');
	$nextfollowup_dt = $request->params('nextfollowup_dt');
	$remark = $request->params('remark');
	$logtype = $request->params('logtype');
	$tagid = $request->params('tagid');

	if (!ctype_digit($dealid)){
		$response = error_code(1012);
	}
	else if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$nextfollowup_dt)){
		$response = error_code(1013);
	}
	else if (!($logtype == 1 || $logtype == 2)){
		$response = error_code(1014);
	}
	else if (!ctype_digit($tagid)){
		$response = error_code(1015);
	}
	else{
	    $sql = "INSERT INTO ".$dbPrefix_curr.".tbxdealfollowuplog (DealId,FollowupDate,NxtFollowupDate,FollowupRemark,WebUserId,LogType,TagId) VALUES ($dealid, '$followup_dt','$nextfollowup_dt', '$remark',$sraid, $logtype,$tagid)";
	    $lastid = executeInsert($sql);
		$response = array();
		if($lastid > 0){
            $affectedrows=0;
			if($logtype == 1){
				$sql = "update ".$dbPrefix_curr.".tbxfieldrcvry set SRAFollowupDt = '$nextfollowup_dt' where dealid=$dealid and mm = ".date('n')."";
			}
			else if($logtype == 2){
				$sql = "update ".$dbPrefix_curr.".tbxfieldrcvry set CallerFollowupDt = '$nextfollowup_dt' where dealid=$dealid and mm = ".date('n')." ";
			}
			$affectedrows = executeUpdate($sql);
			if($affectedrows>0){
				$response["success"] = 1;
				$response["message"] = 'Log updated successfully';
			}
			else{
				$response = error_code(1016);
			}
		}
		else{
			$response = error_code(1017);
		}
	}
	echo json_encode($response);
}

//13
//To-Do : Foreclosure & OD pending
function getDues($dealid, $foreclosure){
	$dbPrefix = $_SESSION['DB_PREFIX'];

	$sql_dues = "SELECT dctyp as type,round(ChrgsApplied-ChrgsRcvd) as amount FROM ".$dbPrefix.".tbmdealchrgs WHERE DealId=$dealid AND DcTyp NOT IN (101,102,111) AND ChrgsApplied > ChrgsRcvd GROUP BY Dctyp";

	$dues = executeSelect($sql_dues);

	foreach($dues['result'] as $i=> $amt){
		$amount = $amt['amount'];
		$foreclosure_amt = foreclosure($dealid) + $amount;
		if($foreclosure == 1){
			$dues['result'][$i]['foreclosure_amt'] = $foreclosure_amt;
		}
	}
	$response = array();
	if($dues['row_count']>0){
		$response["success"] = 1;
	}
	else{
		$response = error_code(1018);
		echo json_encode($response);
		return;
	}
	$response["dues"] = $dues;
	echo json_encode($response);
}

//14
function postDues(){

}

//15
function getDealLogs($dealid) {
	$logs = deal_logs($dealid);

	$response = array();
	if($logs['row_count']>0){
	    $response["success"] = 1;
	}
	else{
		$response = error_code(1019);
		echo json_encode($response);
		return;
	}
	$response["logs"] = $logs;
	echo json_encode($response);
}


//16
function getDealLedger($dealid) {
	$dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];

    $sql_hpdt = "select DATE_FORMAT(hpdt, '%d-%m-%Y') as hpdt FROM ".$dbPrefix.".tbmdeal where dealid = $dealid";
	$hpdt = executeSingleSelect($sql_hpdt);

 	$response = array();
	if (isset($hpdt)) {
		$ledger = deal_ledger($dealid,$hpdt);
		$ledger1 = format_ledger($ledger);
		$ledgers["row_count"]=count($ledger1);
	    $ledgers["found_rows"]=count($ledger1);
		$ledgers['result']=$ledger1;

	    $response["success"] = 1;
	}
	else{
		$response = error_code(1020);
		echo json_encode($response);
		return;
	}
	$response["ledgers"] = $ledgers;
	echo json_encode($response);
}


//17
function postMobileNo(){

}


//18
function postAddress(){

}


//19
function postRTORegNo($dealid,$regno,$nocflag) {
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $trandt = date('Y-m-d');

    // NOC Flag - 1 = requiest for NOC & update Rto Reg No,
    //            0 = Only update Rto Reg No.

    if(!ctype_alnum($regno)){
    	$response = error_code(1021);
	    echo json_encode($response);
		return;
	}
	if(!($nocflag == 0 || $nocflag == 1)){
		$response = error_code(1028);
	 	echo json_encode($response);
		return;
	}
	$sql_update = "update ".$dbPrefix.".tbmdealvehicle set RTORegNo = '$regno' where dealid=$dealid";
	$affectedrows = executeUpdate($sql_update);

	$response = array();

	if($nocflag == 0){
		if($affectedrows>0){
			$response["success"] = 1;
			$response["message"] = 'RTO Reg. no. updated Successfully';
		}
		else{
			$response = error_code(1022);
		}
	}
	if($nocflag == 1){
		$sql_select = "select dealno,dealnm from ".$dbPrefix.".tbmdeal where dealid = $dealid";
		$res = executeSelect($sql_select);
		$dealno =  $res['result'][0]['dealno'];
		$dealnm =  $res['result'][0]['dealnm'];

	    $sql_insert = "INSERT INTO ".$dbPrefix.".tbmdealnocjrnl (DealId,DealNo,DealNm,TranDt) VALUES ($dealid,$dealno,'$dealnm','$trandt')";

		$lastid = executeInsert($sql_insert);
		if($lastid>0){
			$response["success"] = 1;
			$response["message"] = 'NOC request has been successfully sent';
		}
		else{
			$response = error_code(1023);
		}
	}
	echo json_encode($response);
}


//20
function postBankDeposit(){

}

//To-Do : Replace $empid1 with $empid.
//21
function updateAppInfo(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();
	check_session();
	$empid = $_SESSION['userid'];

	$imei = $request->params('imei');
	$appversion = $request->params('appversion');
	$appinstalldt = $request->params('appinstalldt');
	$applastupdatedt = $request->params('applastupdatedt');

	if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$appinstalldt)){
			$response["success"] = 0;
			$response["message"] = 'App Install date is not correct';
	}
	else if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$applastupdatedt)){
			$response["success"] = 0;
			$response["message"] = 'App last update date is not correct';
	}
	else{

		 //To-Do : Remove this query & Replace $empid1 with $empid.
		$sql_empid = "select id from ".$dbPrefix.".tbmemployee where oldid=$empid and active=1";
		$empid1 = executeSingleSelect($sql_empid);


		$sql_update = "update ".$dbPrefix.".tbmdevices set appversion = '$appversion',appinstalldt = '$appinstalldt',applastupdatedt = '$applastupdatedt' where empid=$empid1 and imei=$imei";
		$affectedrows = executeUpdate($sql_update);

		$response = array();
		if($affectedrows>0){
			$response["success"] = 1;
			$response["message"] = 'Successfully Updated';
		}
		else{
			$response = error_code(1024);
		}
	}
	echo json_encode($response);
}

//To-Do : Replace $empid1 with $empid.
//22
function updateLastLogin(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();
	check_session();
	$empid = $_SESSION['userid'];

	$imei = $request->params('imei');
	$lastlogindt = $request->params('lastlogindt');
	$usagetime = $request->params('usagetime');

	if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$lastlogindt)){
		$response["success"] = 0;
		$response["message"] = 'Last login date is not correct';
	}
	else{

		//To-Do : Remove this query & Replace $empid1 with $empid.
		$sql_empid = "select id from ".$dbPrefix.".tbmemployee where oldid=$empid and active=1";
		$empid1 = executeSingleSelect($sql_empid);

		$sql_update = "update ".$dbPrefix.".tbmdevices set lastlogindt = '$lastlogindt',usagetime = '$usagetime' where EmpId=$empid1 and imei = $imei";
		$affectedrows = executeUpdate($sql_update);

		$response = array();
		if($affectedrows>0){
			$response["success"] = 1;
			$response["message"] = 'Successfully Updated';
		}
		else{
			$response = error_code(1025);
		}
	}
	echo json_encode($response);
}


//23
function getNotifications($lastid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	check_session();
	$sraid = $_SESSION['userid'];

	$sql = "select PkId as id,message,case when messagetype = 1 then 'R' when messagetype = 2 then 'S' when messagetype = 3 then 'O' end as message_type,DATE_FORMAT(inserttimestamp,'%d-%M %H:%i') as dt from ".$dbPrefix.".tbmnotification where empid = $sraid and pkid>$lastid ORDER BY inserttimestamp DESC";
	$notifications = executeSelect($sql);

		$response = array();
		if($notifications['row_count']>0){
		    $response["success"] = 1;
		}
		else{
			$response = error_code(1026);
			echo json_encode($response);
			return;
		}
	$response["notifications"] = $notifications;
	echo json_encode($response);
}


//24
function getUpdatedDeals($lasttimestamp){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	check_session();
	$sraid = $_SESSION['userid'];

	if(!DateTime::createFromFormat('Y-m-d H:i:s', $lasttimestamp) !== FALSE){
	 	$response["success"] = 0;
	  	$response["message"] = 'Last timestamp is not in proper format';
	}
	else{
		$sql = "SELECT sql_calc_found_rows fr.dealid, fr.dealno, tcase(fr.dealnm) as name, tcase(fr.city) as city, tcase(fr.area) as area,tcase(fr.address) as address, round(fr.OdDueAmt) as overdue, round(fr.DueAmt) as total_due, fr.rgid as bucket, fr.Mobile as mobile, fr.GuarantorMobile as guarantor_mobile, tcase(fr.model) as vehicle_model, fr.dd as assigned_on,DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt,dt.UpdateTimeStamp as update_timestamp from ".$dbPrefix_curr.".tbxfieldrcvry fr join ".$dbPrefix.".tbmdealtimestamp dt on fr.dealid=dt.dealid where fr.mm = ".date('n')." and fr.sraid = $sraid and dt.UpdateTimeStamp > '$lasttimestamp'";

		$updateddeals = executeSelect($sql);

		$response = array();
		if($updateddeals['row_count']>0){
			$response["success"] = 1;
			$response["updateddeals"] = $updateddeals;
		}
		else{
			$response = error_code(1027);
			echo json_encode($response);
			return;
		}
	}
	echo json_encode($response);
}


//25
function PostDiagnosticLogs(){

}


//26
function PostEscalation(){

}

function sendsms(){
	$str = implode(",",$_REQUEST);
	$file = 'sms.txt';
	file_put_contents($file, $str, FILE_APPEND);
	echo "Done";
}

function check_session(){
	if(!isset($_SESSION['userid'])){
		$response = error_code(1001);
		echo json_encode($response);
		die();
	}
}

function error_code($errorcode){
	$response["success"] = 0;
	$response["errorcode"] = $errorcode;
	$response["message"] = $GLOBALS['error_codes'][$errorcode];
	return $response;
}

function format_ledger($l){
	$removeWords = array("BEING INSTALLMENT AMOUNT RECEIVED BY","BEING CASH DEPOSITED", "BEING CHQ. CLEARED IN", "BEING AMT RCVD BY ECS", "BEING AMT RCVD", "BY ECS", "BEING AMT RECD BY");

	$ledger = array();
	$i =-1;	$p = 0; $balance = 0; $rowStarted = 0;
	$EMI_CANCELLED = -1; $EMI_PENDING = 0; $EMI_CLEARED = 1; $EMI_BOUNCED = 2;

	foreach ($l['result'] as $row){
		if($row['source'] == 1){ // This is a row for DUE EMI from Duelist so start a new row for this
			$balance += $row['DueAmt'];
			$rowStarted = 1;
			$ledger[++$i] = array('serial_no' => NULL, 'dt' => NULL, 'due_amt' => NULL, 'recovered_emi' => NULL, 'balance' => NULL, 'status'=> NULL, 'remarks' => NULL, 'sra_name' => NULL, 'mode'=>NULL);
			$ledger[$i]['serial_no'] = ++$p; $ledger[$i]['dt'] = $row['dDate']; $ledger[$i]['due_amt'] = $row['DueAmt'];
			$last_dDate = $row['dDate'];
		}
		else { // This is a row for Receipt side
			foreach($removeWords as $w){ //Trim the remarks line and remove irrelevant text.
				if(startsWith($row['Remarks'], $w)) {
					 $row['Remarks'] = trim(substr($row['Remarks'], strlen($w)));
				}
			}
			if($rowStarted == 1 & $last_dDate == $row['rDate']){
			}
			else{
				$ledger[++$i] = array('serial_no' => NULL, 'dt' => NULL, 'due_amt' => NULL, 'recovered_emi' => NULL, 'balance' => NULL, 'status'=> NULL, 'remarks' => NULL, 'sra_name' => NULL, 'mode'=>NULL);
				$rowStarted = 1;
			}
			if($row['CBFlg']== 0 && $row['CCLflg']==0){
				$balance -= $row['rEMI'];
			}
			$ledger[$i]['dt'] = $row['rDate'];
			$ledger[$i]['recovered_emi'] = $row['rEMI'];
			$ledger[$i]['status'] = ($row['CBFlg']==-1 ? $EMI_BOUNCED : ($row['CCLflg']== -1 ? $EMI_CANCELLED : (isset($row['reconind']) && $row['reconind'] == $EMI_PENDING ? 0 : $EMI_CLEARED)));
			$ledger[$i]['remarks'] = $row['Remarks'];
			$ledger[$i]['sra_name'] = $row['sranm'];
			$ledger[$i]['mode'] = $row['mode'];
			$rowStarted = 0;
		}
		$ledger[$i]['balance'] = nf($balance, true);
	}
	return $ledger;
}

function deal_logs($dealid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];

    $sql_logs = "SELECT t.dt, t.type,u.realname AS caller_name, b.brkrnm AS sra_name, date_format(t.followupdt,'%d-%b') as followup_dt, t.remark FROM(
	        	SELECT dealid, followupdate AS dt,'FIRSTCALL' AS `type`,  NULL AS callerid, Remark AS remark, NULL AS followupdt, NULL AS sraid FROM $dbPrefix_curr.tbxdealduedatefollowuplog WHERE dealid = $dealid
	       		UNION
	        	SELECT dealid, followupdate AS dt, 'CALLER' AS `type`, webuserid AS callerid, FollowupRemark AS remark, NxtFollowupDate AS followupdt, NULL AS sraid FROM $dbPrefix_curr.tbxdealfollowuplog WHERE dealid = $dealid
				UNION
				SELECT dealid, followupdate AS dt, 'INTERNAL' AS `type`,  webuserid AS callerid, FollowupRemark AS remark, NULL AS followupdt, sraid FROM $dbPrefix_curr.tbxsrafollowuplog WHERE dealid = $dealid
				) t
				LEFT JOIN ob_sa.tbmuser u ON t.callerid = u.userid
				LEFT JOIN $dbPrefix.tbmbroker b ON t.sraid = b.brkrid AND b.brkrtyp = 2
				ORDER BY dt DESC";

  	$logs = executeSelect($sql_logs);
  	return $logs;
}


function deal_ledger($dealid,$hpdt){
	$dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];

	$q1 = "SELECT DueDt as Date, round(DueAmt) as Due, round(CollectionChrgs) as CC, round(DueAmt+CollectionChrgs) as Total, (case WHEN Duedt <= curdate() THEN 1 ELSE 0 END) as eligible  FROM ".$dbPrefix.".tbmduelist where dealid = $dealid order by Year(DueDt), Month(DueDt)";

	$q2 = "";
	$hp_mm = date("n", strtotime($hpdt));
	$hp_yy= date("Y", strtotime($hpdt));
	$startyy = ($hp_mm < 4 ? ($hp_yy-1) : $hp_yy);


	//TO-DO: Loop exist criteria is wrong for months of JAN, FEB, MARCH. Check it.

	for ($d = $startyy; $d <= date('Y'); $d++){
		$db = "lksa".$d."".str_pad($d+1-2000, 2, '0', STR_PAD_LEFT);
		$q2 .="
			SELECT t1.sraid, b.brkrnm as sranm, t1.rcptdt as Date, round(sum(t2.rcptamt)) as Received, t1.rcptid, t1.rcptpaymode as mode, t1.CBFlg, t1.CBCCLFlg, t1.CCLflg, DATE_FORMAT(t1.cbdt, '%d-%b-%y') as cbdt, DATE_FORMAT(t1.ccldt, '%d-%b-%y') as  ccldt, t1.rmrk as Remarks, t1.cbrsn,
			sum(case when dctyp = 101 then round(t2.rcptamt) ELSE 0 END) as EMI,
			sum(case when dctyp = 102 then round(t2.rcptamt) ELSE 0 END) as Clearing, sum(case when dctyp = 103 then round(t2.rcptamt) ELSE 0 END) as CB,
			sum(case when dctyp = 104 then round(t2.rcptamt) ELSE 0 END) as Penalty, sum(case when dctyp = 105 then round(t2.rcptamt) ELSE 0 END) as Seizing,
			sum(case when dctyp = 107 then round(t2.rcptamt) ELSE 0 END) as Other, sum(case when dctyp = 111 then round(t2.rcptamt) ELSE 0 END) as CC
			, v.reconind
			FROM ".$db.".tbxdealrcpt t1 join ".$db.".tbxdealrcptdtl t2 on t1.rcptid = t2.rcptid and t1.dealid = $dealid
			LEFT JOIN ".$db.".tbxacvoucher v on v.xrefid = t1.rcptid and v.rcptno = t1.rcptno and xreftyp = 1100 and acvchtyp = 4 and acxnsrno = 0
			left join lksa.tbmbroker b on t1.sraid = b.brkrid group by t1.rcptid
			UNION";
	}

	$q2 = rtrim($q2, "UNION");

	$sql = "select * from (
			SELECT 1 AS source, Date, DATE_FORMAT(Date, '%d-%b-%Y') as dDate, Due, Total as DueAmt, eligible, NULL as rDate, NULL AS Received, NULL AS rcptid, NULL as mode, NULL AS CBFlg, NULL AS CBCCLFlg, NULL AS CCLflg, NULL AS cbdt, NULL AS ccldt,  NULL as cbrsn, NULL as sranm, NULL AS Remarks, NULL AS rEMI, NULL AS Penalty, NULL AS Others, NULL as reconind FROM ($q1) as t1
		UNION
			SELECT 2 AS source, DATE, NULL as dDate, NULL AS Due, NULL AS DueAmt, NULL AS eligible, DATE_FORMAT(Date, '%d-%b-%Y') as rDate, Received, rcptid, mode, CBFlg, CBCCLFlg, CCLflg, cbdt, ccldt, cbrsn, sranm, Remarks, (EMI+CC) as rEMI, Penalty, (Clearing + CB + Seizing + Other) as Others, reconind FROM ($q2) as t2
		) t order by Date, source";

	$ledger = executeSelect($sql);
	return $ledger;
}


function deal_details($deals){
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];

	foreach($deals['result'] as $i=> $deal){
		$dealid = $deal['dealid'];

		$sql_guarantor = "select tcase(GrtrNm) as name, tcase(concat(add1, ' ', add2, ' ', area, ' ', tahasil, ' ', city)) as address, mobile as mobile from ".$dbPrefix.".tbmdealguarantors where DealId=$dealid";
		$guarantor = executeSelect($sql_guarantor);

		$sql_assignment = "select fr.mm as month,fr.yy as year,fr.CallerId as caller_empid,e1.name as caller_name,e1.mobile as caller_phone,fr.SRAId as sra_id,e.name as sra_name,e.Mobile as sra_phone from ".$dbPrefix_curr.".tbxfieldrcvry fr join ".$dbPrefix.".tbmemployee e join ".$dbPrefix.".tbmemployee e1 on fr.SRAId = e.id and fr.CallerId = e1.id where fr.DealId=$dealid";
		$assignment = executeSelect($sql_assignment);

		$sql_otherphone = "select mobile, mobile2 from ".$dbPrefix.".tbmdeal where DealId=$dealid";
		$otherphone = executeSelect($sql_otherphone);

		$rows=0;
		$ph = array();
		if($otherphone['row_count'] > 0){
			$index=0;
			if(isset($otherphone['result'][0]['mobile'])){
				$ph[$index]= array();
				$ph[$index]["name"]='Self';
				$ph[$index]["relation"]='Self';
				$ph[$index]["number"]=$otherphone['result'][0]['mobile'];
				$index++;
				$rows++;
			}
			if(isset($otherphone['result'][0]['mobile2'])){
				$ph[$index]= array();
				$ph[$index]["name"]='Self';
				$ph[$index]["relation"]='Self';
				$ph[$index]["number"]=$otherphone['result'][0]['mobile2'];
				$rows++;
			}
		}
		$phones["row_count"]=$rows;
		$phones["found_rows"]=$rows;
		$phones['result']=$ph;

		$ledger = deal_ledger($dealid,$deal['hpdt']);
		$ledger1 = format_ledger($ledger);
		$ledgers["row_count"]=count($ledger1);
		$ledgers["found_rows"]=count($ledger1);
		$ledgers['result']=$ledger1;

		$logs = deal_logs($dealid);

		$sql_dealcharges = "SELECT dctyp as type,round(ChrgsApplied-ChrgsRcvd) as amount FROM ".$dbPrefix.".tbmdealchrgs WHERE DealId=$dealid AND DcTyp NOT IN (101,102,111) AND ChrgsApplied > ChrgsRcvd GROUP BY Dctyp";
		$dealcharges = executeSelect($sql_dealcharges);

		$sql_bounce = "select concat(count(case when status=-1 then 1 end),'/',count(depositdt)) as bounced from ".$dbPrefix.".tbmpaytypedtl where active=2 and depositdt IS NOT NULL and dealid = $dealid";
		$bounce = executeSingleSelect($sql_bounce);

		$sql_seized = "select count(dealid)as seized from ".$dbPrefix_curr.".tbxvhclsz where dealid=$dealid";
		$seized = executeSingleSelect($sql_seized);

		$deals['result'][$i]['bounce'] = $bounce;
		$deals['result'][$i]['seized'] = $seized;
		$deals['result'][$i]['phonenumbers'] = $phones;
		$deals['result'][$i]['guarantor'] = $guarantor;
		$deals['result'][$i]['dealcharges'] = $dealcharges;
		$deals['result'][$i]['assignment'] = $assignment;
		$deals['result'][$i]['ledger'] = $ledgers;
		$deals['result'][$i]['logs'] = $logs;
	}
	return $deals;
}



function foreclosure($dealid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$current_date = date('Y-m-d');

	$q = "select sum(dueamt+collectionchrgs) as outstanding_amt,sum(collectionchrgs) as cc,sum(finchrg) as remaining_interest from ".$dbPrefix.".tbmduelist where duedt > '$current_date' and dealid = $dealid group by dealid";

	$foreclosure = executeSelect($q);

	$outstanding_amt=$foreclosure['result'][0]['outstanding_amt'];
	$cc=$foreclosure['result'][0]['cc'];
	$remaining_interest=$foreclosure['result'][0]['remaining_interest'];

	$foreclosure_amt=(((($outstanding_amt*4)/100)+$outstanding_amt)-$cc)-$remaining_interest;

	return round($foreclosure_amt);

	}
?>