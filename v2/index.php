<?php
session_cache_limiter(false);
session_start();
require 'functions.php';

$app = new Slim();

$app->post('/login', 'login');  //00
$app->get('/register/:imei', 'register');  //01
$app->get('/registergcm/:empid/:imei/:gcmid', 'registerGcm');  //02
$app->get('/staticdata/:empid', 'getStaticData');  //03
$app->get('/contacts/:lastid', 'getContacts');  //04
$app->get('/deals/:empid/:page', 'getDeals');  //05
$app->get('/dashboards/:empid', 'getDashboards');  //06
$app->get('/notdepositedreceipts/:empid', 'getNotDepositedReceipts');  //07
$app->get('/deposithistory/:empid', 'getDepositHistory');  //08
$app->get('/daywisecollection/:empid', 'getDaywiseCollection');  //09
$app->get('/searchdeals/:empid/:query/:page', 'searchDeals');  //10
$app->get('/deal/:dealid', 'getDeal');  //11
$app->post('/postlogs', 'postLogs');  //12
$app->get('/dues/:dealid/:foreclosure', 'getDues');  //13
$app->post('/postdues', 'postDues');  //14 (Pending)
$app->get('/deallogs/:dealid', 'getDealLogs');  //15
$app->get('/dealledgers/:dealid', 'getDealLedger');  //16
$app->post('/postmobileno', 'postMobileNo');  //17 (Pending)
$app->post('/postaddress', 'postAddress');  //18 (Pending)
$app->post('/postrtoregno', 'postRTORegNo');  //19
$app->post('/postbankdeposit', 'postBankDeposit');  //20
$app->post('/updateappinfo', 'updateAppInfo');  //21
$app->post('/updatelastlogin', 'updateLastLogin');  //22
$app->get('/notifications/:empid/:lastid', 'getNotifications');  //23
$app->get('/updateddeals/:empid/:lasttimestamp', 'getUpdatedDeals');  //24
$app->post('/postdiagnosticlogs', 'PostDiagnosticLogs');  //25 (Pending)
$app->post('/postescalation', 'PostEscalation');  //26 (Pending)

$app->get('/smsresponse/:id', 'smsresponse');
$app->get('/sendsms/:mobileno/:msg/:dealno/:msgtag/:sentto', 'sendsms');

$app->post('/customerregister', 'customerregister');  //Consumer App 01
$app->post('/customerlogin', 'customerlogin');  //Consumer App 02
$app->get('/customerdealdetails/:dealid', 'getCustomerDealDetails');  //Consumer App 03

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
	//$sql = "select empid from ".$dbPrefix.".tbmdevices where apppin = '$pin' and imei = '$imei' and active=2";
	//Remove following query & uncomment above query.
	$sql = "select e.oldid as empid from ".$dbPrefix.".tbmdevices d join ".$dbPrefix.".tbmemployee e on e.id = d.empid where d.apppin = '$pin' and d.imei = '$imei' and d.active=2";
	$empid = executeSingleSelect($sql);

	$response = array();
		if($empid>0){
		    //$_SESSION['userid'] = $empid;
		 	$response["success"] = 1;
		 	$response["message"] = 'Successfully Logged In';
		 	$response["empid"] = $empid;
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
	//Replace e.oldid with e.id in following query.
	$sql = "select e.oldid as empid,tcase(e.name) as name,d.apppin as pin,e.mobile,e.email,e.photourl as photo_url,e.department,e.designation,e.role,tcase(e.centre) as centre,e.walletlimit as wallet_limit,d.printerid as printer_id,d.appversion as app_version,d.admindsn as admin_dsn,d.serviceurl as service_url from ".$dbPrefix.".tbmemployee e join ".$dbPrefix.".tbmdevices d on e.id = d.empid and d.active=2 where d.imei = '$imei' and e.active=2";

	$emp = executeSelect($sql);

	$response = array();
	if($emp['row_count']>0){
	    $response["success"] = 1;
	    //$_SESSION['userid'] = $emp['result'][0]['id'];
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
function registerGcm($empid,$imei,$gcmid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	//check_session();
	//$empid = $_SESSION['userid'];

	if (!ctype_digit($imei)){
		$response = error_code(1003);
		echo json_encode($response);
		return;
	}
    if(!ctype_alnum($gcmid)){
    	$response = error_code(1004);
		echo json_encode($response);
		return;
	}

    //To-Do : Remove following query(sql_empid) & Replace $empid1 with $empid in sql_update query.
	$sql_empid = "select id from ".$dbPrefix.".tbmemployee where oldid=$empid and active=2";
	$empid1 = executeSingleSelect($sql_empid);

	$sql_update = "update ".$dbPrefix.".tbmdevices set gcmid = '$gcmid' where empid=$empid1 and imei = '$imei'";
	$affectedrows = executeUpdate($sql_update);

	$response = array();
	if($affectedrows>0){
		$response["success"] = 1;
		$response["message"] = 'GCM Id Successfully Registered';
	}
	else{
		$response = error_code(1006);
	}
	echo json_encode($response);
}


//03
function getStaticData($empid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	//check_session();
	//$empid = $_SESSION['userid'];

    $sql = "select sql_calc_found_rows distinct(sb.bankid),sb.banknm,sb.bankshnm,sba.AcId as bankacid from ".$dbPrefix.".tbmsourcebank sb join ".$dbPrefix.".tbaposbankbranch pb on sb.bankid = pb.bankid left join ".$dbPrefix.".tbasrcbnkaccnt sba on sb.bankid = sba.bankid and sba.actyp = '3002' where pb.empid = $empid";
	$bank = executeSelect($sql);

	foreach($bank['result'] as $i=> $static){
		$bankid = $static['bankid'];
		$sql_branchnm = "select sql_calc_found_rows bb.BankBrnchId as branchid,bb.BankBrnchCd as branchcode,bb.BankBrnchNm as branch,bb.city from ".$dbPrefix.".tbmsourcebankbrnch bb join ".$dbPrefix.".tbaposbankbranch pb on bb.BankBrnchId = pb.branchid where bb.bankid=$bankid and pb.empid = $empid";
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

	$sql = "select id,tcase(name) as name,mobile,designation,tcase(centre) as centre, null as photo_url from ".$dbPrefix.".tbmemployee where active=2 and id > '$lastid' ORDER BY id ASC";

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
function getDeals($empid,$page) {
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	$limit = $_SESSION['API_ROW_LIMIT'];
	$start = ($page-1) * $limit;
	//check_session();
	//$sraid = $_SESSION['userid'];
	$sraid = $empid;

    $sql = "select sql_calc_found_rows fr.dealid, fr.dealid, fr.dealno, tcase(fr.dealnm) as name,tcase(d.centre) as centre, tcase(fr.area) as area, tcase(fr.city) as city, tcase(fr.address) as address, fr.mobile, DATE_FORMAT(fr.hpdt, '%d-%m-%Y') as hpdt, round(fr.dueamt) as total_due,round(fr.OdDueAmt) as overdue, concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on,fr.rec_flg as recovered_flg, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(case when fr.SRAFollowupDt is null then fr.CallerFollowupDt else fr.SRAFollowupDt end,'%d-%m-%Y') as sra_followup_dt, fr.rgid as bucket,round(d.financeamt) as finance_amt,round(fr.emi) as emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d') as emi_day, (case when d.paytype=1 then 'PDC' when d.paytype=2 then 'ECS' when d.paytype=3 then 'Direct Debit' end) as type, tcase(concat(dv.make, ' ', dv.model)) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no, tcase(b.BrkrNm) as dealer, tcase(trim(concat(ifnull(b.city,''), ' ', case when b.centre != b.city then b.centre else '' end))) as dealer_loc, fr.SalesmanId as salesman_id,s.SalesmanNm as salesman_name,s.Mobile as salesman_mobile
	FROM ".$dbPrefix_curr.".tbxfieldrcvry fr
	join ".$dbPrefix.".tbmdeal d
	join ".$dbPrefix.".tbmdealvehicle dv
	join ".$dbPrefix.".tbmbroker b
	join ".$dbPrefix.".tbmsalesman s
	on fr.dealid = d.dealid and fr.dealid=dv.dealid and d.brkrid = b.brkrid and fr.SalesmanId = s.SalesmanId where fr.mm = ".date('n')." and fr.sraid = $sraid
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
function getDashboards($empid){
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	//check_session();
	//$sraid = $_SESSION['userid'];
	$sraid = $empid;

	$sql = "SELECT f.yy, f.mm, f.sraid AS empid, NULL AS empname, SUM(d.total) AS collection, SUM(d.OD) AS od, SUM(d.Penalty) AS penalty, SUM(d.CB) AS bouncing, SUM(d.Other) AS others,
	SUM(CASE WHEN f.dd = 1 THEN 1 ELSE 0 END) AS assigned_fd, SUM(CASE WHEN f.dd = 1 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_fd,
	SUM(CASE WHEN f.dd != 1 THEN 1 ELSE 0 END) AS assigned_dm, SUM(CASE WHEN f.dd != 1 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_dm, COUNT(f.dealid) AS assinged, COUNT(d.dealid) AS recovered,
	SUM(CASE WHEN f.rgid = 1 THEN 1 ELSE 0 END) AS assigned_b1, SUM(CASE WHEN f.rgid =1 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_b1,
	SUM(CASE WHEN f.rgid = 2 THEN 1 ELSE 0 END) AS assigned_b2, SUM(CASE WHEN f.rgid =2 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_b2,
	SUM(CASE WHEN f.rgid = 3 THEN 1 ELSE 0 END) AS assigned_b3, SUM(CASE WHEN f.rgid =3 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_b3,
	SUM(CASE WHEN f.rgid = 4 THEN 1 ELSE 0 END) AS assigned_b4, SUM(CASE WHEN f.rgid =4 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_b4,
	SUM(CASE WHEN f.rgid = 5 THEN 1 ELSE 0 END) AS assigned_b5, SUM(CASE WHEN f.rgid =5 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_b5,
	SUM(CASE WHEN f.rgid > 5 THEN 1 ELSE 0 END) AS assigned_b6, SUM(CASE WHEN f.rgid >5 AND d.dealid IS NOT NULL THEN 1 ELSE 0 END) AS recovered_b6, 0 AS target_fd
	FROM ".$dbPrefix_curr.".tbxfieldrcvry f LEFT JOIN
	(SELECT MONTH(rcptdt) AS mm, r.dealid, SUM(rd.rcptamt) AS total,
	SUM(CASE WHEN rd.dctyp IN (101,102,111) THEN rd.rcptamt ELSE 0 END) AS OD,
	SUM(CASE WHEN rd.dctyp = 103 THEN rd.rcptamt ELSE 0 END) AS CB,
	SUM(CASE WHEN rd.dctyp = 104 THEN rd.rcptamt ELSE 0 END) AS Penalty,
	SUM(CASE WHEN rd.dctyp > 104 AND rd.dctyp < 111 THEN rd.rcptamt ELSE 0 END) AS Other
	FROM ".$dbPrefix_curr.".tbxdealrcpt r JOIN ".$dbPrefix_curr.".tbxdealrcptdtl rd ON r.rcptid = rd.rcptid AND r.cclflg = 0 AND r.cbflg = 0 AND r.rcptpaymode = 1
	GROUP BY MONTH(rcptdt), dealid) d ON f.dealid = d.dealid AND f.mm = d.mm
 	WHERE f.sraid = $sraid GROUP BY f.mm ORDER BY yy DESC, mm DESC LIMIT 0,6";

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
function getNotDepositedReceipts($empid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	$sraid = $empid;

	$sql_posid = "select POSId from ".$dbPrefix.".tbasrapos where BrkrId = '$sraid' ORDER BY wefdt DESC LIMIT 1";
	$posid = executeSingleSelect($sql_posid);
	$pos = substr($posid, 0, 2);
	if($pos === 'M-'){

		$sql = "SELECT pj.RcptNo,rj.RcptNo,rj.JrnlNo,rj.TranNo,rj.RcptDate,rj.TranTime,rj.RcptMode FROM ".$dbPrefix_curr.".tbxrcptjrnl rj LEFT JOIN (SELECT pj.JrnlNo, pjd.RcptNo, pj.POSId  FROM ".$dbPrefix_curr.".tbxdealpmntjrnl pj
		JOIN ".$dbPrefix_curr.".tbxdealpmntjrnldtl AS pjd ON pj.JrnlNo = pjd.JrnlNo WHERE POSID = '$posid') AS pj ON rj.tranNo=pj.RcptNo WHERE rj.POSID = '$posid' HAVING pj.RcptNo IS NULL";
		$notdepositreceipt = executeSelect($sql);

		foreach($notdepositreceipt['result'] as $i=> $jrnl){
	   		$jrnlno = $jrnl['JrnlNo'];

			$sql_recdamt = "SELECT DcTyp,RecdAmt FROM ".$dbPrefix_curr.".tbxrcptjrnldtl WHERE JrnlNo = '$jrnlno'";
		   	$recdamount = executeSelect($sql_recdamt);

			$notdepositreceipt['result'][$i]['RecdAmt'] = $recdamount;
		}

		$response = array();
			if($notdepositreceipt['row_count']>0){
				$response["success"] = 1;
			}
			else{
			    $response = error_code(1045);
				echo json_encode($response);
				return;
			}
		$response["notdepositreceipt"] = $notdepositreceipt;
	}
	else{
		$response = error_code(1045);
		echo json_encode($response);
		return;
	}
	echo json_encode($response);
}


//08
function getDepositHistory($empid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	$sraid = $empid;

	$sql_posid = "select POSId from ".$dbPrefix.".tbasrapos where BrkrId = '$sraid'";
	$posid = executeSingleSelect($sql_posid);

	$sql = "SELECT * FROM (
	select TranDate,BankId,BranchId,Amount from ".$dbPrefix_curr.".tbxdealpmntjrnl where POSId = '$posid '
	UNION
	select TranDate,BankId,BranchId,Amount from ".$dbPrefix_last.".tbxdealpmntjrnl where POSId = '$posid ')t1 ORDER BY TranDate DESC LIMIT 31";

	$deposithistory = executeSelect($sql);

	$response = array();
		if($deposithistory['row_count']>0){
			$response["success"] = 1;
		}
		else{
		    $response = error_code(1044);
			echo json_encode($response);
			return;
		}
	$response["deposithistory"] = $deposithistory;
	echo json_encode($response);

}


//09
function getDaywiseCollection($empid){
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	//check_session();
	//$sraid = $_SESSION['userid'];
	$sraid = $empid;

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
function searchDeals($empid,$query,$page){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$limit = $_SESSION['API_ROW_LIMIT'];
	$start = ($page-1) * $limit;
	//check_session();
	//$sraid = $_SESSION['userid'];
	$sraid = $empid;

	if (!ctype_alnum($query)){
		$response = error_code(1010);
		echo json_encode($response);
		return;
	}
	else{
	 	$q = "SELECT sql_calc_found_rows d.dealid, d.dealno, tcase(d.dealnm) as name, tcase(d.city) as city, tcase(d.area) as area, trim(concat(d.add1, ' ', d.add2, ' ', d.area, ' ', d.tahasil)) as address, round(fr.OdDueAmt) as overdue, round(fr.DueAmt) as total_due, fr.rgid as bucket, fr.Mobile as mobile, fr.GuarantorMobile as guarantor_mobile, tcase(fr.model) as vehicle_model, concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt from ".$dbPrefix.".tbmdeal d Left JOIN ".$dbPrefix_curr.".tbxfieldrcvry fr ON d.dealid = fr.dealid and fr.mm = ".date('n')."";

		if(is_numeric($query)){
			if(strlen($query) < 6)
				$query = str_pad($query, 6, "0", STR_PAD_LEFT);
			$q .= " WHERE (d.dealno = '$query')";
		}
		else{
			$q .= " WHERE (d.dealnm like '%$query%' or d.city like '%$query%' or d.area like '%$query%' or d.tahasil like '%$query%' or d.add1 like '%$query%' or d.add2 like '%$query%')";
		}
		$q .= " order by d.dealno desc limit  $start, $limit";
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

	$sql = "select d.dealid, d.dealno, tcase(d.dealnm) as name,tcase(d.centre) as centre, tcase(d.area) as area, tcase(d.city) as city, concat(tcase(d.add1),' ',tcase(d.add2)) as address, d.mobile, DATE_FORMAT(d.hpdt, '%d-%m-%Y') as hpdt, round(fr.dueamt) as total_due,round(fr.OdDueAmt) as overdue, concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt, fr.rgid as bucket,round(d.financeamt) as finance_amt,round(ps.MthlyAmt+ps.CollectionChrgs) as emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d') as emi_day, (case when d.paytype=1 then 'PDC' when d.paytype=2 then 'ECS' when d.paytype=3 then 'Direct Debit' end) as type, tcase(concat(dv.make, ' ', dv.model)) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no, tcase(b.BrkrNm) as dealer, tcase(trim(concat(ifnull(b.city,''), ' ', case when b.centre != b.city then b.centre else '' end))) as dealer_loc, fr.SalesmanId as salesman_id,s.SalesmanNm as salesman_name,s.Mobile as salesman_mobile
	FROM ".$dbPrefix.".tbmdeal d
	join ".$dbPrefix.".tbmdealvehicle dv on d.dealid=dv.dealid and d.dealid = $dealid
	join ".$dbPrefix.".tbmbroker b on d.brkrid = b.brkrid
	join ".$dbPrefix.".tbmpmntschd ps on d.dealid = ps.dealid
	join ".$dbPrefix.".tbmsalesman s
	left join ".$dbPrefix_curr.".tbxfieldrcvry fr on fr.dealid = d.dealid and fr.SalesmanId = s.SalesmanId where fr.mm = ".date('n');

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
	//check_session();
	//$sraid = $_SESSION['userid'];

	$followup_dt = date('Y-m-d');
	$sraid = $request->params('empid');
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
				$response["message"] = 'Log posted & updated successfully';
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
	$current_date = date('Y-m-d');

	if(!($foreclosure == 0 || $foreclosure == 1)){
		$response = error_code(1030);
		echo json_encode($response);
		return;
	}

	$sql_dues = "SELECT dctyp as type,round(ChrgsApplied-ChrgsRcvd) as amount FROM ".$dbPrefix.".tbmdealchrgs WHERE DealId=$dealid AND DcTyp NOT IN (101,102,111) AND ChrgsApplied > ChrgsRcvd GROUP BY Dctyp";
	$dues = executeSelect($sql_dues);

	$sql_od1 = "select sum(dueamt+collectionchrgs) as charges_applied from ".$dbPrefix.".tbmduelist where duedt <= '$current_date' and dealid = $dealid";
	$sql_od2 = "select sum(chrgsrcvd) from ".$dbPrefix.".tbmdealchrgs where dctyp IN (101,102,111) and dealid=$dealid";
	$od = executeSingleSelect($sql_od1)-executeSingleSelect($sql_od2);

	if($foreclosure == 1){
		$foreclosure_amt = foreclosure($dealid);
		$index=count($dues['result']);
		$dues['result'][$index]= array();
		$dues['result'][$index]["type"]='101';
		$dues['result'][$index]["amount"]=strval($od+$foreclosure_amt);
	}
	else{
		$index=count($dues['result']);
		$dues['result'][$index]= array();
		$dues['result'][$index]["type"]='101';
		$dues['result'][$index]["amount"]=strval($od);
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
	$response["dues"]["row_count"]=count($dues);
	$response["dues"]["found_rows"]=count($dues);
	echo json_encode($response);
}

//14
function postDues(){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$request = Slim::getInstance()->request();

	$jrnlno = 'J-0000';
	$empid = $request->params('empid');
	$tranno = $request->params('tranno');
	$dealno = $request->params('dealno');
	$rcptdt = $request->params('rcptdt');
	$paymode = $request->params('paymode');
	$chqno = $request->params('chqno');
	$chqdt = $request->params('chqdt');
	$banknm = $request->params('banknm');
	$place = $request->params('place');
	$trantime = $request->params('trantime');
	$rcptmode = $request->params('rcptmode');
	$totamt = $request->params('totamt');

	$dctyp_amt = $request->params('dctyp_amt');

	$sql = "INSERT INTO ".$dbPrefix_curr.".tbxrcptjrnl (JrnlNo,TranNo,EmpId,DealNo,RcptDate,TotAmt,PayMode,ChqNo,ChqDate,BankName,Place,TranTime,RcptMode) VALUES ('$jrnlno', '$tranno', '$empid', '$dealno', '$rcptdt', '$totamt', '$paymode', '$chqno', '$chqdt', '$banknm', '$place', '$trantime', '$rcptmode')";
	$lastid = executeInsert($sql);

	$response = array();
	if($lastid > 0){
		$arr_dctyp_amt=explode(" ",$dctyp_amt);
		for($i = 0; $i < sizeof($arr_dctyp_amt); $i++){
			$dctyp_dctypamt = explode(",",$arr_dctyp_amt[$i]);
			$dctyp = $dctyp_dctypamt[0];
			$dctypamt = $dctyp_dctypamt[1];

			$sql1= "INSERT INTO ".$dbPrefix_curr.".tbxrcptjrnldtl (JrnlNo,DCTyp,RecdAmt) VALUES ('$jrnlno', '$dctyp', '$dctypamt')";
			$lastinsertid = executeInsert($sql1);
		}
		if ($lastinsertid > 0){
			$response["success"] = 1;
			$response["message"] = 'Dues successfully posted';
		}
		else{
			$response = error_code(1042);
		}
	}
	else{
		$response = error_code(1042);
	}
	echo json_encode($response);
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
function postRTORegNo() {
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $request = Slim::getInstance()->request();

    $trandt = date('Y-m-d');
    $dealid = $request->params('dealid');
    $regno = $request->params('regno');
    $nocflag = $request->params('nocflag');

    // NOC Flag - 1 = requiest for NOC & update Rto Reg No,
    //            0 = Only update Rto Reg No.

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
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$request = Slim::getInstance()->request();

	$jrnlno = 'J-0000';
	$empid = $request->params('empid');
	$tranno = $request->params('tranno');
	$trandate = $request->params('trandate');
	$trantime = $request->params('trantime');

	$bankid = $request->params('bankid');
	$bankacid = $request->params('bankacid');
	$branchcode = $request->params('branchcode');
	$branchid = $request->params('branchid');
	$amount = $request->params('amount');

	$dealno_rcptno_amt = $request->params('dealno_rcptno_amt');

	$sql = "INSERT INTO ".$dbPrefix_curr.".tbxdealpmntjrnl (JrnlNo,TranNo,EmpId,TranDate,BankId,BankAcId,BranchId,BranchCode,Amount,TranTime,InsertUserId) VALUES ('$jrnlno', '$tranno', '$empid', '$trandate', '$bankid', '$bankacid', '$branchid', '$branchcode', '$amount', '$trantime', '$empid')";
	$lastid = executeInsert($sql);

	$response = array();
	if($lastid > 0){

		$arr_dealno_rcptno_amt=explode(" ",$dealno_rcptno_amt);

		for($i = 0; $i < sizeof($arr_dealno_rcptno_amt); $i++){
			$dealno_rcptno_rcptamt = explode(",",$arr_dealno_rcptno_amt[$i]);
			$dealno = $dealno_rcptno_rcptamt[0];
			$rcptno = $dealno_rcptno_rcptamt[1];
			$rcptamt = $dealno_rcptno_rcptamt[2];

	 		$sql1= "INSERT INTO ".$dbPrefix_curr.".tbxdealpmntjrnldtl (JrnlNo,DealNo,RcptNo,RcptAmt) VALUES ('$jrnlno', '$dealno', '$rcptno', '$rcptamt')";
			$lastinsertid = executeInsert($sql1);
		}

		if ($lastinsertid > 0){
			$response["success"] = 1;
			$response["message"] = 'Bank Deposit successfully posted';
		}
		else{
			$response = error_code(1043);
		}
	}
	else{
		$response = error_code(1043);
	}
	echo json_encode($response);
}



//To-Do : Replace $empid1 with $empid.
//21
function updateAppInfo(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();
	//check_session();
	//$empid = $_SESSION['userid'];

	$empid = $request->params('empid');
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

		 //To-Do : Remove following query(sql_empid) & Replace $empid1 with $empid in sql_update query.
		$sql_empid = "select id from ".$dbPrefix.".tbmemployee where oldid=$empid and active=2";
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
	//check_session();
	//$empid = $_SESSION['userid'];

	$empid = $request->params('empid');
	$imei = $request->params('imei');
	$lastlogindt = $request->params('lastlogindt');
	$usagetime = $request->params('usagetime');

	if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$lastlogindt)){
		$response["success"] = 0;
		$response["message"] = 'Last login date is not correct';
	}
	else{

		//To-Do : Remove following query(sql_empid) & Replace $empid1 with $empid in sql_update query.
		$sql_empid = "select id from ".$dbPrefix.".tbmemployee where oldid=$empid and active=2";
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
function getNotifications($empid,$lastid){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	//check_session();
	//$sraid = $_SESSION['userid'];
	$sraid = $empid;

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
function getUpdatedDeals($empid,$lasttimestamp){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	//check_session();
	//$sraid = $_SESSION['userid'];
	$sraid = $empid;

	if(!DateTime::createFromFormat('Y-m-d H:i:s', $lasttimestamp) !== FALSE){
	 	$response["success"] = 0;
	  	$response["message"] = 'Last timestamp is not in proper format';
	}
	else{
		$sql = "SELECT sql_calc_found_rows fr.dealid, fr.dealno, tcase(fr.dealnm) as name, tcase(fr.city) as city, tcase(fr.area) as area,tcase(fr.address) as address, round(fr.OdDueAmt) as overdue, round(fr.DueAmt) as total_due, fr.rgid as bucket, fr.Mobile as mobile, fr.GuarantorMobile as guarantor_mobile, tcase(fr.model) as vehicle_model, concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on,fr.rec_flg as recovered_flg,DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(case when fr.SRAFollowupDt is null then fr.CallerFollowupDt else fr.SRAFollowupDt end,'%d-%m-%Y') as sra_followup_dt,dt.UpdateTimeStamp as update_timestamp from ".$dbPrefix_curr.".tbxfieldrcvry fr join ".$dbPrefix.".tbmdealtimestamp dt on fr.dealid=dt.dealid where fr.mm = ".date('n')." and fr.sraid = $sraid and dt.UpdateTimeStamp > '$lasttimestamp'";

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


function smsresponse($id){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $req = (array) Slim::getInstance()->request();
  //print_a($req);
    $str =  json_encode($req);
  //echo $str;
  //$file = "sms-$id.txt";
  //file_put_contents($file, $str, FILE_APPEND);

        if(isset($req["\0*\0".'get']['unique_id'])){
	    	$unique_id = $req["\0*\0".'get']['unique_id'];
	    	$reason = $req["\0*\0".'get']['reason'];
	    	$to = $req["\0*\0".'get']['to'];
	    	$from = $req["\0*\0".'get']['from'];
	    	$time = $req["\0*\0".'get']['time'];
        	$status = $req["\0*\0".'get']['status'];

        	$sql_update = "update ".$dbPrefix_curr.".tbxsms set status = (case when '$reason' = '000' then '3' else '4' end),reason = (case when '$reason' = '000' then 'Sent' when '$reason' = '001' then 'Invalid Number' when '$reason' = '002' then 'Absent Subscriber' when '$reason' = '003' then 'Memory Capacity Exceeded' when '$reason' = '004' then 'Mobile Equipment Error' when '$reason' = '005' then 'Network Error' when '$reason' = '006' then 'Barring' when '$reason' = '007' then 'Invalid Sender ID' when '$reason' = '008' then 'Dropped' when '$reason' = '009' then 'NDNC Failed' when '$reason' = 100 then 'Misc. Error' else '$reason' end),ReceivedDtTm = '$time' where pkid='$id'";
		    $affectedrows = executeUpdate($sql_update);

		    $response = array();
				if($affectedrows>0){
					$response["success"] = 1;
					$response["message"] = 'Successfully Updated';
				}
				else{
					$response["success"] = 0;
					$response["message"] = 'Failed to Update';
				}
				echo json_encode($response);
		}
	    else{
	   		 echo json_encode("Data Not Found");
    	}
}


function sendsms($mobileno,$msg,$dealno,$msgtag,$sentto){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];

	$sql_insert = "insert into ".$dbPrefix_curr.".tbxsms(CmpnyCd,MsgDtTm,SentDtTm,Mobile,Message,MsgTag,DealNo,MsgPriority,status,SentTo)
	values ('LKSA',now(),now(),'$mobileno','$msg','$msgtag','$dealno',0,1,'$sentto')";

	$lastid = executeInsert($sql_insert);
		if($lastid>0){
			$response["success"] = 1;
			$response["message"] = 'Insert Successful';
		}
		else{
			$response["success"] = 0;
			$response["message"] = 'Failed To Insert';
		}
	echo json_encode($response);
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
			$ledger[++$i] = array('serial_no' => NULL, 'dt' => NULL, 'due_amt' => NULL, 'recovered_emi' => NULL, 'balance' => NULL, 'status'=> NULL, 'remarks' => NULL, 'sra_name' => NULL, 'mode'=>NULL, 'cbdt' =>NULL, 'ccldt' =>NULL, 'cbrsn' =>NULL);
			$ledger[$i]['serial_no'] = ++$p; $ledger[$i]['dt'] = $row['dDate']; $ledger[$i]['due_amt'] = $row['DueAmt'];
			$last_dDate = $row['dDate'];
		}
		else { // This is a row for Receipt side
			foreach($removeWords as $w){ //Trim the remarks line and remove irrelevant text.
				if(startsWith($row['Remarks'], $w)) {
					 $row['Remarks'] = trim(substr($row['Remarks'], strlen($w)));
				}
			}
			if($rowStarted == 1 && $last_dDate == $row['rDate']){
			}
			else{
				$ledger[++$i] = array('serial_no' => NULL, 'dt' => NULL, 'due_amt' => NULL, 'recovered_emi' => NULL, 'balance' => NULL, 'status'=> NULL, 'remarks' => NULL, 'sra_name' => NULL, 'mode'=>NULL, 'cbdt' =>NULL, 'ccldt' =>NULL, 'cbrsn' =>NULL);
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
			$ledger[$i]['cbdt'] = $row['cbdt'];
			$ledger[$i]['ccldt'] = $row['ccldt'];
			$ledger[$i]['cbrsn'] = $row['cbrsn'];
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
    $userdbPrefix = $_SESSION['USER_DB_PREFIX'];


    $sql_logs = "SELECT t.dt, t.type,u.realname AS caller_name, b.brkrnm AS sra_name, date_format(t.followupdt,'%d-%b') as followup_dt, t.remark FROM(
	        	SELECT dealid, followupdate AS dt,'FIRSTCALL' AS `type`,  NULL AS callerid, Remark AS remark, NULL AS followupdt, NULL AS sraid FROM ".$dbPrefix_curr.".tbxdealduedatefollowuplog WHERE dealid = $dealid
	       		UNION
	        	SELECT dealid, followupdate AS dt, 'CALLER' AS `type`, webuserid AS callerid, FollowupRemark AS remark, NxtFollowupDate AS followupdt, NULL AS sraid FROM ".$dbPrefix_curr.".tbxdealfollowuplog WHERE dealid = $dealid
				UNION
				SELECT dealid, followupdate AS dt, 'INTERNAL' AS `type`,  webuserid AS callerid, FollowupRemark AS remark, NULL AS followupdt, sraid FROM ".$dbPrefix_curr.".tbxsrafollowuplog WHERE dealid = $dealid
				) t
				LEFT JOIN ".$userdbPrefix.".tbmuser u ON t.callerid = u.userid
				LEFT JOIN ".$dbPrefix.".tbmbroker b ON t.sraid = b.brkrid AND b.brkrtyp = 2
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
		$db = "$dbPrefix".$d."".str_pad($d+1-2000, 2, '0', STR_PAD_LEFT);
		$q2 .="
			SELECT t1.sraid, b.brkrnm as sranm, t1.rcptdt as Date, round(sum(t2.rcptamt)) as Received, t1.rcptid, t1.rcptpaymode as mode, t1.CBFlg, t1.CBCCLFlg, t1.CCLflg, DATE_FORMAT(t1.cbdt, '%d-%b-%y') as cbdt, DATE_FORMAT(t1.ccldt, '%d-%b-%y') as  ccldt, t1.rmrk as Remarks, t1.cbrsn,
			sum(case when dctyp = 101 then round(t2.rcptamt) ELSE 0 END) as EMI,
			sum(case when dctyp = 102 then round(t2.rcptamt) ELSE 0 END) as Clearing, sum(case when dctyp = 103 then round(t2.rcptamt) ELSE 0 END) as CB,
			sum(case when dctyp = 104 then round(t2.rcptamt) ELSE 0 END) as Penalty, sum(case when dctyp = 105 then round(t2.rcptamt) ELSE 0 END) as Seizing,
			sum(case when dctyp = 107 then round(t2.rcptamt) ELSE 0 END) as Other, sum(case when dctyp = 111 then round(t2.rcptamt) ELSE 0 END) as CC
			, v.reconind
			FROM ".$db.".tbxdealrcpt t1 join ".$db.".tbxdealrcptdtl t2 on t1.rcptid = t2.rcptid and t1.dealid = $dealid
			LEFT JOIN ".$db.".tbxacvoucher v on v.xrefid = t1.rcptid and v.rcptno = t1.rcptno and xreftyp = 1100 and acvchtyp = 4 and acxnsrno = 0
			left join ".$dbPrefix.".tbmbroker b on t1.sraid = b.brkrid group by t1.rcptid
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

  /*
	$sql1 = "select sum(dueamt+collectionchrgs) as charges_applied from ".$dbPrefix.".tbmduelist where duedt <= '$current_date' and dealid = $dealid";
	$sql2 = "select sum(chrgsrcvd) from ".$dbPrefix.".tbmdealchrgs where dctyp IN (101,102,111) and dealid=$dealid";
	$od = executeSingleSelect($sql1)-executeSingleSelect($sql2);

	$sql3 = "select sum(chrgsapplied)-sum(chrgsrcvd) from ".$dbPrefix.".tbmdealchrgs where dctyp NOT IN (101,102,111) and dealid=$dealid";
	$other = executeSingleSelect($sql3);
  */


	$sql_foreclosure = "select sum(dueamt+collectionchrgs) as outstanding_amt,sum(collectionchrgs) as cc,sum(finchrg) as remaining_interest from ".$dbPrefix.".tbmduelist where duedt > '$current_date' and dealid = $dealid group by dealid";
	$foreclosure = executeSelect($sql_foreclosure);

	$outstanding_amt = $foreclosure['result'][0]['outstanding_amt'];
	$cc = $foreclosure['result'][0]['cc'];
	$remaining_interest = $foreclosure['result'][0]['remaining_interest'];
	$foreclosure_amt = (((($outstanding_amt*4)/100)+$outstanding_amt)-$cc)-$remaining_interest;

	$sql_hpdtid = "select pkid from ".$dbPrefix.".tbmdeal where hpdt > DATE_SUB('$current_date', INTERVAL 6 MONTH) and dealid=$dealid";
	$hpdtid = executeSingleSelect($sql_hpdtid);

 	if($hpdtid > 0){
 		//HpDt is within 6 months.
 		$sql_pfamt = "select pfamt from ".$dbPrefix.".tbadealfnncdtls where dealid=$dealid";
		$pfamt=executeSingleSelect($sql_pfamt);
		return round($pfamt+$foreclosure_amt);
 	}
	return round($foreclosure_amt);



  /*
	if($od == 0 && $other == 0){
		return round($foreclosure_amt);
	}
	if($od == 0 && $other != 0){
		return round(($foreclosure_amt)+($other));
	}
	if($od != 0 && $other == 0){
		return round(($foreclosure_amt)+($od));
	}
	if($od != 0 && $other != 0){
		return round(($foreclosure_amt)+($od)+($other));
	}
  */
}




function customerregister(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();

	$mobile = $request->params('mobile');
	$dealno = $request->params('dealno');
	$password = $request->params('password');

	if (!ctype_digit($mobile)){
		$response = error_code(1031);
		echo json_encode($response);
		return;
	}
	if (!ctype_digit($dealno)){
		$response = error_code(1032);
		echo json_encode($response);
		return;
	}
	if (!ctype_digit($password)){
		$response = error_code(1033);
		echo json_encode($response);
		return;
	}
	if(is_numeric($dealno)){
		if(strlen($dealno) < 6){
      		$dealno = str_pad($dealno, 6, "0", STR_PAD_LEFT);
        }
    }
    $sql_login = "select custid from ".$dbPrefix.".tbmcustomerlogin where custmobile='$mobile' and custpassword = '$password'";
	$loginid = executeSingleSelect($sql_login);
		$response = array();
		if($loginid>0){
			$response = error_code(1036);
			echo json_encode($response);
			return;
		}
		else{
		    $sql_register = "select pkid from ".$dbPrefix.".tbmdeal where dealno='$dealno' and mobile = '$mobile'";
			$pkid_register = executeSingleSelect($sql_register);
			if($pkid_register>0){
			   	$sql = "INSERT INTO ".$dbPrefix.".tbmcustomerlogin (custmobile,custpassword) VALUES ('$mobile','$password')";
	           	$lastid = executeInsert($sql);
	           	if($lastid>0){
	          		$response["success"] = 1;
					$response["message"] = 'Successfully Register';
	           	}
	           	else{
	            	$response = error_code(1034);
				    echo json_encode($response);
				    return;
				}
	      	}
			else{
				$response = error_code(1034);
				echo json_encode($response);
				return;
			}
		}
	echo json_encode($response);
}


function customerlogin(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();

	$mobile = $request->params('mobile');
	$password = $request->params('password');

	if (!ctype_digit($mobile)){
		$response = error_code(1031);
		echo json_encode($response);
		return;
	}
	if (!ctype_digit($password)){
		$response = error_code(1033);
		echo json_encode($response);
		return;
	}

	$sql_login = "select custid from ".$dbPrefix.".tbmcustomerlogin where custmobile='$mobile' and custpassword = '$password'";
	$loginid = executeSingleSelect($sql_login);

	$sql_profile = "select d.dealid,d.dealno,case when d.dealsts = 1 then 'Active' when d.dealsts = 2 then 'Draft' when d.dealsts = 3 then 'Closed' end as dealstatus,concat(v.make, ' ', v.model, ' ',v.modelyy) as dealvehicle,d.dealnm as name,null as dob,d.mobile,d.mobile2,d.tel1,d.tel2,d.email,trim(concat(d.add1, ' ', d.add2, ' ',d.area, ' ',d.tahasil)) as address from ".$dbPrefix.".tbmdeal d join ".$dbPrefix.".tbmdealvehicle v on d.dealid = v.dealid where d.mobile = '$mobile' order by d.dealsts asc,d.hpdt desc";

	$sql_centreaddress = "select distinct ca.centreid,d.centre,tcase(concat(ca.line1, ' ', ca.line2, ' ', ca.street, ' ', ca.area, ' ', ca.city, ' ', ca.pincode)) as address,ca.phno,ca.email from ".$dbPrefix.".tbmdeal d join ".$dbPrefix.".tbmcentre c on d.centre = c.centrenm join ".$dbPrefix.".tbmcentreaddrss ca on c.centreid = ca.centreid where d.mobile = '$mobile' or c.centreid=1 order by c.centreid asc";
	$centreaddress = executeSelect($sql_centreaddress);

	$response = array();
		if($loginid>0){
			$profile = executeSelect($sql_profile);
			$response = array();
				if($profile['row_count']>0){
					$response["success"] = 1;
				    $response["message"] = 'Successfully Logged In';
				}
				else{
					$response = error_code(1000);
					echo json_encode($response);
					return;
				}

				$profile['result'][0]['centreaddress'] = $centreaddress;

				$response["Profile"] = $profile;
				echo json_encode($response);
				return;
		}
		else{
			$response = error_code(1000);
		}
	echo json_encode($response);
}


function getCustomerDealDetails($dealid) {
	$dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];

	$sql = "select d.dealid as deal_id, d.dealno as deal_no,case when d.dealsts = 1 then 'Active' when d.dealsts = 2 then 'Draft' when d.dealsts = 3 then 'Closed' end as deal_status,round(d.financeamt) as loan_amt,(case when d.paytype=1 then 'PDC' when d.paytype=2 then 'ECS' when d.paytype=3 then 'Direct Debit' end) as payment_mode,tcase(concat(dv.make, ' ', dv.model, ' ',dv.modelyy)) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no,
	DATE_FORMAT(d.hpdt, '%d-%m-%Y') as hpdt,DATE_FORMAT(d.startduedt, '%d-%b-%y') as emi_startdt,DATE_FORMAT(d.hpexpdt, '%d-%b-%y') as emi_enddt,d.period as emi_tenure,round(ps.MthlyAmt+ps.CollectionChrgs) as emi_amt,tcase(b.BrkrNm) as dealer_name
	FROM ".$dbPrefix.".tbmdeal d
	join ".$dbPrefix.".tbmdealvehicle dv on d.dealid=dv.dealid and d.dealid = $dealid
	join ".$dbPrefix.".tbmbroker b on d.brkrid = b.brkrid
	join ".$dbPrefix.".tbmpmntschd ps on d.dealid = ps.dealid";
	$dealdetails = executeSelect($sql);

	$sql_salesman = "select s.salesmannm as salesman_name,(case when s.active=2 then s.mobile when s.active=1 then null end) as salesman_mobile from tbmsalesman s join tbadealsalesman sa on s.salesmanid=sa.salesmanid where sa.dealid=$dealid";
	$salesman = executeSelect($sql_salesman);
	$dealdetails['result'][0]['salesman_name']=$salesman['result'][0]['salesman_name'];
	$dealdetails['result'][0]['salesman_mobile']=$salesman['result'][0]['salesman_mobile'];

	$sql_guarantor = "select tcase(GrtrNm) as name, tcase(trim(concat(ifnull(add1,''), ' ', ifnull(add2,''), ' ', ifnull(area,''), ' ', ifnull(tahasil,''), ' ', ifnull(city,'')))) as address, mobile as mobile from ".$dbPrefix.".tbmdealguarantors where DealId=$dealid";
	$guarantor = executeSelect($sql_guarantor);

	$sql_noc = "SELECT n2.nocno, DATE_FORMAT(n3.nocdate,'%d-%b-%y') AS senttocustomerdt, DATE_FORMAT(n3.rtndate,'%d-%b-%y') AS returndt,DATE_FORMAT(n3.senddate,'%d-%b-%y') as senttosradt
	FROM ".$dbPrefix.".tbadealnocpmnt n1 LEFT JOIN ".$dbPrefix.".tbadealnoc n2  ON n1.dealid = n2.dealid LEFT JOIN ".$dbPrefix.".tbadealcustnoc AS n3 ON n1.dealid = n3.dealid WHERE n1.dealid =$dealid";
	$noc = executeSelect($sql_noc);

	$sql_charges = "SELECT
	round(SUM(CASE WHEN dctyp = 102 THEN Chrgsapplied - chrgsrcvd ELSE 0 END)) AS Clearing,
	round(SUM(CASE WHEN dctyp = 103 THEN Chrgsapplied - chrgsrcvd ELSE 0 END)) AS Bouncing,
	round(SUM(CASE WHEN dctyp = 104 THEN Chrgsapplied - chrgsrcvd ELSE 0 END)) AS Penalty,
	round(SUM(CASE WHEN dctyp = 105 THEN Chrgsapplied - chrgsrcvd ELSE 0 END)) AS Seizing,
	round(SUM(CASE WHEN dctyp = 106 THEN Chrgsapplied - chrgsrcvd ELSE 0 END)) AS Legal,
	round(SUM(CASE WHEN dctyp = 107 THEN Chrgsapplied - chrgsrcvd ELSE 0 END)) AS Other,
	round(SUM(CASE WHEN dctyp IN (101,111) THEN chrgsrcvd ELSE 0 END)) AS rEMI
	FROM ".$dbPrefix.".tbmdealchrgs WHERE dealid = $dealid";
	$charges = executeSelect($sql_charges);
	$sql_due = "select sum(round(DueAmt+CollectionChrgs)) as due from ".$dbPrefix.".tbmduelist where dealid = $dealid and Duedt <= curdate()";
	$due = executeSingleSelect($sql_due);

	$charges['result'][0]['due']=round($due);
	$charges['result'][0]['currentOd'] = round($due-$charges['result'][0]['rEMI']);
	$charges['result'][0]['totaldue'] =($charges['result'][0]['currentOd'])+($charges['result'][0]['Clearing'])+($charges['result'][0]['Bouncing'])+($charges['result'][0]['Penalty'])+($charges['result'][0]['Seizing'])+($charges['result'][0]['Legal'])+($charges['result'][0]['Other']);

	$sql_seize = "select DATE_FORMAT(s.vhclszdt,'%d-%b-%y') as seizedt,DATE_FORMAT(r.vhclrldt,'%d-%b-%y') as releasedt,(case when r.vhclrldt is null then 'Seized' when r.vhclrldt is not null then 'Released' end) as status from ".$dbPrefix_curr.".tbxvhclsz s left join ".$dbPrefix_curr.".tbxvhclrl r on s.VhclSzRlId = r.VhclSzRlId where s.dealid = $dealid and s.cclflg = 0";
	$seize = executeSelect($sql_seize);

	$ledger = deal_ledger($dealid,$dealdetails['result'][0]['hpdt']);
	$ledger1 = format_ledger($ledger);
	$ledgers["row_count"]=count($ledger1);
	$ledgers["found_rows"]=count($ledger1);
	$ledgers['result']=$ledger1;

	$dealdetails['result'][0]['guarantor'] = $guarantor;
	$dealdetails['result'][0]['noc'] = $noc;
	$dealdetails['result'][0]['charges'] = $charges;
	$dealdetails['result'][0]['seize'] = $seize;
	$dealdetails['result'][0]['ledgers'] = $ledgers;

	$response = array();
	if($dealdetails['row_count']>0){
		$response["success"] = 1;
	}
	else{
		$response = error_code(1035);
		echo json_encode($response);
		return;
	}
	$response["dealdetails"] = $dealdetails;
	echo json_encode($response);
}
?>