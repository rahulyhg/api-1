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
$app->post('/postdues', 'postDues');  //14
$app->get('/deallogs/:dealid', 'getDealLogs');  //15
$app->get('/dealledgers/:dealid', 'getDealLedger');  //16
$app->post('/postmobileno', 'postMobileNo');  //17
$app->post('/postaddress', 'postAddress');  //18 (Pending)
$app->post('/postrtoregno', 'postRTORegNo');  //19
$app->post('/postbankdeposit', 'postBankDeposit');  //20
$app->post('/updateappinfo', 'updateAppInfo');  //21
$app->post('/updatelastlogin', 'updateLastLogin');  //22
$app->get('/notifications/:empid/:lastid', 'getNotifications');  //23
$app->get('/updateddeals/:empid/:lasttimestamp', 'getUpdatedDeals');  //24
$app->post('/postdiagnosticlogs', 'postDiagnosticLogs');  //25 (Pending)
$app->post('/postescalation', 'postEscalation');  //26 (Pending)
$app->post('/requestnoc', 'requestNOC');  //27

$app->get('/smsresponse/:id', 'smsresponse');
$app->get('/sendsms/:mobileno/:msg/:dealno/:msgtag/:sentto', 'sendsms');

$app->post('/customerregister', 'customerregister');  //Consumer App 01
$app->post('/customerlogin', 'customerlogin');  //Consumer App 02
$app->get('/customerdealdetails/:dealid', 'getCustomerDealDetails');  //Consumer App 03

$app->get('/accountbalance/:acid/:acxndt', 'getAcBalance');
$app->get('/unreconciledepositentry/:posid', 'getUnreconcileDepositEntry');
$app->get('/cashinhand/:sraid', 'getCashInHand');
$app->get('/postdepositentry/:tranno/:posid/:trandate/:bankid/:bankacid/:branchid/:branchcode/:amount/:trantime/:usedlimit', 'postDepositEntry');
$app->post('/postdepositentry', 'post_DepositEntry');

$app->get('/proposalCriteria/:proposalno', 'proposalCriteria');

$app->get('/proposaldata/:imei', 'getProposaldata');
$app->get('/newproposal/:salesmanid/:brkrid/:bankid/:prslname', 'postNewProposal');
$app->post('/updateproposal', 'updateProposal');
$app->get('/address/:pincode', 'getAddress');
$app->post('/uploaddocuments', 'uploadDocuments');
$app->get('/sendotp/:salesmanid/:mobile', 'sendOtp');
$app->get('/verifyotp/:mobile/:otp', 'verifyOtp');

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
	$sql = "select e.oldid as empid,p.posid,tcase(e.name) as name,d.apppin as pin,e.mobile,e.email,e.photourl as photo_url,e.department,e.designation,e.role,tcase(e.centre) as centre,e.walletlimit as wallet_limit,d.printerid as printer_id,d.appversion as app_version,d.admindsn as admin_dsn,d.serviceurl as service_url
	from ".$dbPrefix.".tbmemployee e
	join ".$dbPrefix.".tbmdevices d on e.id = d.empid and d.active=2
	LEFT JOIN ".$dbPrefix.".tbasrapos p ON e.oldid = p.brkrid AND p.active=2
	where d.imei = '$imei' and e.active=2";

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
		$sql_branchnm = "select sql_calc_found_rows distinct(bb.BankBrnchId) as branchid,bb.BankBrnchCd as branchcode,bb.BankBrnchNm as branch, trim(concat(IFNULL(bb.add1,''), ' ', IFNULL(bb.add2,''), ' ', IFNULL(bb.city,''))) as address, bb.city from ".$dbPrefix.".tbmsourcebankbrnch bb join ".$dbPrefix.".tbaposbankbranch pb on bb.BankBrnchId = pb.branchid where bb.bankid=$bankid and pb.empid = $empid";
		$branchnm = executeSelect($sql_branchnm);
		$bank['result'][$i]['branchname'] = $branchnm;
 	}
    $staticdata['bank']=$bank;

	$states = array(array("state"=> "Maharashtra"), array("state"=> "Madhya Pradesh"));
	$state["row_count"] = count($states);
	$state["found_rows"] = count($states);
	$state['result'] = $states;
	$staticdata['states'] = $state;

   	$sql_logs = "select Description as tag from ".$dbPrefix.".tbmrecoverytags WHERE allowtagto IN(0,2) AND Tagtyp = 1 AND active = 2";
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
   	$dctype[2]["name"]='Bouncing';
   	$dctype[3]["type"]='104';
   	$dctype[3]["name"]='Penalty';
   	$dctype[4]["type"]='105';
   	$dctype[4]["name"]='Seizing';
   	$dctype[5]["type"]='107';
   	$dctype[5]["name"]='Other';
   	$dctype[6]["type"]='111';
   	$dctype[6]["name"]='Collection Charges';
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

    $sql = "select sql_calc_found_rows fr.dealid, fr.dealid, fr.dealno, tcase(fr.dealnm) as name,tcase(d.centre) as centre, tcase(fr.area) as area, tcase(fr.city) as city, tcase(fr.address) as address, fr.mobile, DATE_FORMAT(fr.hpdt, '%d-%m-%Y') as hpdt, round(fr.dueamt) as total_due,round(fr.OdDueAmt) as overdue,round(fr.MinReqOD) as minreq_od, concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on, fr.rec_flg as recovered_flg, fr.expired as expired_flg, DATE_FORMAT(STR_TO_DATE(CONCAT(fr.dd,'-',fr.mm,'-',fr.yy),'%d-%m-%Y'),'%d-%m-%Y') AS assigned_dt, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(case when fr.SRAFollowupDt is null then fr.CallerFollowupDt else fr.SRAFollowupDt end,'%d-%m-%Y') as sra_followup_dt, fr.rgid as bucket, fr.bankid, round(d.financeamt) as finance_amt,round(fr.emi) as emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d') as emi_day,DATE_FORMAT(d.startduedt, '%d-%m-%Y') as startdue_dt, trim(concat(IFNULL(dv.Manufacture,''), ' ', IFNULL(dv.Make,''), ' ', IFNULL(dv.Model,''))) AS vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no, tcase(b.BrkrNm) as dealer, tcase(trim(concat(ifnull(b.city,''), ' ', case when b.centre != b.city then b.centre else '' end))) as dealer_loc, fr.SalesmanId as salesman_id,s.SalesmanNm as salesman_name,s.Mobile as salesman_mobile
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

	$sql = "SELECT * FROM (SELECT f.yy, f.mm, f.sraid AS empid, NULL AS empname, SUM(d.total) AS collection, SUM(d.OD) AS od, SUM(d.Penalty) AS penalty, SUM(d.CB) AS bouncing, SUM(d.Other) AS others,
	SUM(CASE WHEN f.dd = 1 THEN 1 ELSE 0 END) AS assigned_fd,
	SUM(CASE WHEN f.dd = 1 THEN f.rec_flg ELSE 0 END) AS recovered_fd,
	SUM(CASE WHEN f.dd > 1 THEN 1 ELSE 0 END) AS assigned_dm,
	SUM(CASE WHEN f.dd > 1 THEN f.rec_flg ELSE 0 END) AS recovered_dm,
	COUNT(f.dealid) AS assinged,
	SUM(f.rec_flg) AS recovered,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 0 THEN 1 ELSE 0 END) AS assigned_b1a,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 0 THEN f.rec_flg ELSE 0 END) AS recovered_b1a,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 1 THEN 1 ELSE 0 END) AS assigned_b1e,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 1 THEN f.rec_flg ELSE 0 END) AS recovered_b1e,
	SUM(CASE WHEN f.rgid = 1 THEN 1 ELSE 0 END) AS assigned_b1,
	SUM(CASE WHEN f.rgid = 1 THEN f.rec_flg ELSE 0 END) AS recovered_b1,
	SUM(CASE WHEN f.rgid = 2 THEN 1 ELSE 0 END) AS assigned_b2,
	SUM(CASE WHEN f.rgid = 2 THEN f.rec_flg ELSE 0 END) AS  recovered_b2,
	SUM(CASE WHEN f.rgid = 3 THEN 1 ELSE 0 END) AS assigned_b3,
	SUM(CASE WHEN f.rgid = 3 THEN f.rec_flg ELSE 0 END) AS recovered_b3,
	SUM(CASE WHEN f.rgid = 4 THEN 1 ELSE 0 END) AS assigned_b4,
	SUM(CASE WHEN f.rgid = 4 THEN f.rec_flg ELSE 0 END) AS recovered_b4,
	SUM(CASE WHEN f.rgid = 5 THEN 1 ELSE 0 END) AS assigned_b5,
	SUM(CASE WHEN f.rgid = 5 THEN f.rec_flg ELSE 0 END) AS recovered_b5,
	SUM(CASE WHEN f.rgid > 5 THEN 1 ELSE 0 END) AS assigned_b6,
	SUM(CASE WHEN f.rgid > 5 THEN f.rec_flg ELSE 0 END) AS recovered_b6,
	0 AS target_fd
	FROM ".$dbPrefix_curr.".tbxfieldrcvry f LEFT JOIN
	(SELECT MONTH(rcptdt) AS mm, r.dealid, SUM(rd.rcptamt) AS total,
	SUM(CASE WHEN rd.dctyp IN (101,102,111) THEN rd.rcptamt ELSE 0 END) AS OD,
	SUM(CASE WHEN rd.dctyp = 103 THEN rd.rcptamt ELSE 0 END) AS CB,
	SUM(CASE WHEN rd.dctyp = 104 THEN rd.rcptamt ELSE 0 END) AS Penalty,
	SUM(CASE WHEN rd.dctyp > 104 AND rd.dctyp < 111 THEN rd.rcptamt ELSE 0 END) AS Other
	FROM ".$dbPrefix_curr.".tbxdealrcpt r JOIN ".$dbPrefix_curr.".tbxdealrcptdtl rd ON r.rcptid = rd.rcptid AND r.cclflg = 0 AND r.cbflg = 0 AND r.rcptpaymode = 1
	GROUP BY MONTH(rcptdt), dealid
	) d ON f.dealid = d.dealid AND f.mm = d.mm
 	WHERE f.sraid = $sraid GROUP BY f.mm
 	UNION
 	SELECT f.yy, f.mm, f.sraid AS empid, NULL AS empname, SUM(d.total) AS collection, SUM(d.OD) AS od, SUM(d.Penalty) AS penalty, SUM(d.CB) AS bouncing, SUM(d.Other) AS others,
	SUM(CASE WHEN f.dd = 1 THEN 1 ELSE 0 END) AS assigned_fd,
	SUM(CASE WHEN f.dd = 1 THEN f.rec_flg ELSE 0 END) AS recovered_fd,
	SUM(CASE WHEN f.dd > 1 THEN 1 ELSE 0 END) AS assigned_dm,
	SUM(CASE WHEN f.dd > 1 THEN f.rec_flg ELSE 0 END) AS recovered_dm,
	COUNT(f.dealid) AS assinged,
	SUM(f.rec_flg) AS recovered,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 0 THEN 1 ELSE 0 END) AS assigned_b1a,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 0 THEN f.rec_flg ELSE 0 END) AS recovered_b1a,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 1 THEN 1 ELSE 0 END) AS assigned_b1e,
	SUM(CASE WHEN f.rgid = 1 AND f.expired = 1 THEN f.rec_flg ELSE 0 END) AS recovered_b1e,
	SUM(CASE WHEN f.rgid = 1 THEN 1 ELSE 0 END) AS assigned_b1,
	SUM(CASE WHEN f.rgid = 1 THEN f.rec_flg ELSE 0 END) AS recovered_b1,
	SUM(CASE WHEN f.rgid = 2 THEN 1 ELSE 0 END) AS assigned_b2,
	SUM(CASE WHEN f.rgid = 2 THEN f.rec_flg ELSE 0 END) AS  recovered_b2,
	SUM(CASE WHEN f.rgid = 3 THEN 1 ELSE 0 END) AS assigned_b3,
	SUM(CASE WHEN f.rgid = 3 THEN f.rec_flg ELSE 0 END) AS recovered_b3,
	SUM(CASE WHEN f.rgid = 4 THEN 1 ELSE 0 END) AS assigned_b4,
	SUM(CASE WHEN f.rgid = 4 THEN f.rec_flg ELSE 0 END) AS recovered_b4,
	SUM(CASE WHEN f.rgid = 5 THEN 1 ELSE 0 END) AS assigned_b5,
	SUM(CASE WHEN f.rgid = 5 THEN f.rec_flg ELSE 0 END) AS recovered_b5,
	SUM(CASE WHEN f.rgid > 5 THEN 1 ELSE 0 END) AS assigned_b6,
	SUM(CASE WHEN f.rgid > 5 THEN f.rec_flg ELSE 0 END) AS recovered_b6,
	0 AS target_fd
	FROM ".$dbPrefix_last.".tbxfieldrcvry f LEFT JOIN
	(SELECT MONTH(rcptdt) AS mm, r.dealid, SUM(rd.rcptamt) AS total,
	SUM(CASE WHEN rd.dctyp IN (101,102,111) THEN rd.rcptamt ELSE 0 END) AS OD,
	SUM(CASE WHEN rd.dctyp = 103 THEN rd.rcptamt ELSE 0 END) AS CB,
	SUM(CASE WHEN rd.dctyp = 104 THEN rd.rcptamt ELSE 0 END) AS Penalty,
	SUM(CASE WHEN rd.dctyp > 104 AND rd.dctyp < 111 THEN rd.rcptamt ELSE 0 END) AS Other
	FROM ".$dbPrefix_last.".tbxdealrcpt r JOIN ".$dbPrefix_last.".tbxdealrcptdtl rd ON r.rcptid = rd.rcptid AND r.cclflg = 0 AND r.cbflg = 0 AND r.rcptpaymode = 1
	GROUP BY MONTH(rcptdt), dealid) d ON f.dealid = d.dealid AND f.mm = d.mm
 	WHERE f.sraid = $sraid GROUP BY f.mm)t1
 	ORDER BY yy DESC, mm DESC LIMIT 0,6";

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

		$sql = "SELECT pj.RcptNo,rj.RcptNo,rj.JrnlNo,rj.TranNo,rj.RcptDate,rj.TranTime,rj.RcptMode,rj.DealNo,d.DealNm FROM ".$dbPrefix_curr.".tbxrcptjrnl rj LEFT JOIN (SELECT pj.JrnlNo, pjd.RcptNo, pj.POSId  FROM ".$dbPrefix_curr.".tbxdealpmntjrnl pj
		JOIN ".$dbPrefix_curr.".tbxdealpmntjrnldtl AS pjd ON pj.JrnlNo = pjd.JrnlNo WHERE POSID = '$posid') AS pj ON rj.tranNo=pj.RcptNo
		LEFT JOIN ".$dbPrefix.".tbmdeal d ON rj.dealno = d.dealno WHERE rj.POSID = '$posid' HAVING pj.RcptNo IS NULL";
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
	 	$q = "SELECT sql_calc_found_rows d.dealid, d.dealno, tcase(d.dealnm) as name, tcase(d.city) as city, tcase(d.area) as area, trim(concat(IFNULL(d.add1,''), ' ', IFNULL(d.add2,''), ' ', IFNULL(d.area,''), ' ', IFNULL(d.tahasil,''))) as address,tcase(d.centre) as centre, round(fr.OdDueAmt) as overdue, round(fr.DueAmt) as total_due, round(fr.MinReqOD) as minreq_od, fr.rgid as bucket, fr.bankid, d.Mobile as mobile, dg.mobile as guarantor_mobile, trim(concat(IFNULL(dv.Manufacture,''), ' ', IFNULL(dv.Make,''), ' ', IFNULL(dv.Model,''))) AS vehicle_model,concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on, fr.rec_flg as recovered_flg, fr.expired as expired_flg, DATE_FORMAT(STR_TO_DATE(CONCAT(fr.dd,'-',fr.mm,'-',fr.yy),'%d-%m-%Y'),'%d-%m-%Y') AS assigned_dt, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt
	 	FROM ".$dbPrefix.".tbmdeal d
	 	JOIN ".$dbPrefix.".tbmdealvehicle dv on d.dealid = dv.dealid
	 	LEFT JOIN ".$dbPrefix.".tbmdealguarantors dg on d.dealid = dg.dealid
	 	LEFT JOIN ".$dbPrefix_curr.".tbxfieldrcvry fr ON d.dealid = fr.dealid and fr.mm = ".date('n')."";


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

	$sql = "select d.dealid, d.dealno, tcase(d.dealnm) as name,tcase(d.centre) as centre, tcase(d.area) as area, tcase(d.city) as city, trim(concat(IFNULL(d.add1,''), ' ', IFNULL(d.add2,''), ' ', IFNULL(d.area,''), ' ', IFNULL(d.tahasil,''))) as address, d.mobile, DATE_FORMAT(d.hpdt, '%d-%m-%Y') as hpdt, round(fr.dueamt) as total_due,round(fr.OdDueAmt) as overdue, round(fr.MinReqOD) as minreq_od, concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on, fr.rec_flg as recovered_flg, fr.expired as expired_flg, DATE_FORMAT(STR_TO_DATE(CONCAT(fr.dd,'-',fr.mm,'-',fr.yy),'%d-%m-%Y'),'%d-%m-%Y') AS assigned_dt, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt, fr.rgid as bucket, fr.bankid, round(d.financeamt) as finance_amt,round(ps.MthlyAmt+ps.CollectionChrgs) as emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d') as emi_day,DATE_FORMAT(d.startduedt, '%d-%m-%Y') as startdue_dt,trim(concat(IFNULL(dv.Manufacture,''), ' ', IFNULL(dv.Make,''), ' ', IFNULL(dv.Model,''))) AS vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no, tcase(b.BrkrNm) as dealer, tcase(trim(concat(ifnull(b.city,''), ' ', case when b.centre != b.city then b.centre else '' end))) as dealer_loc, s.SalesmanId as salesman_id, s.SalesmanNm as salesman_name, s.Mobile as salesman_mobile
	FROM ".$dbPrefix.".tbmdeal d
	JOIN ".$dbPrefix.".tbmdealvehicle dv on d.dealid = dv.dealid and d.dealid = $dealid
	JOIN ".$dbPrefix.".tbmbroker b on d.brkrid = b.brkrid
	JOIN ".$dbPrefix.".tbmpmntschd ps on d.dealid = ps.dealid
	JOIN ".$dbPrefix.".tbadealsalesman ds ON d.dealid = ds.dealid
	JOIN ".$dbPrefix.".tbmsalesman s ON ds.salesmanid = s.salesmanid
	LEFT JOIN ".$dbPrefix_curr.".tbxfieldrcvry fr on fr.dealid = d.dealid AND fr.mm = ".date('n');

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

	$empid = $request->params('empid');
	$dealid = $request->params('dealid');
	$nextfollowup_dt = $request->params('nextfollowup_dt');
	$remark = $request->params('remark');
	$logtype = $request->params('logtype');


	if (!ctype_digit($dealid)){
		$response = error_code(1012);
	}

	else if (!($logtype == 1 || $logtype == 2)){
		$response = error_code(1014);
	}

	else{
	    $sql = "INSERT INTO ".$dbPrefix_curr.".tbxdealfollowuplog (DealId,FollowupDate,NxtFollowupDate,FollowupRemark,EmpId,LogType) VALUES ($dealid, NOW(),'$nextfollowup_dt', '$remark',$empid, $logtype)";
	    $lastid = executeInsert($sql);
		$response = array();
		if($lastid > 0){
            $affectedrows=0;
			if($logtype == 1){
				$sql = "update ".$dbPrefix_curr.".tbxfieldrcvry set SRAFollowupDt = '$nextfollowup_dt' where dealid=$dealid and mm = ".date('n')."";

			}
			else if($logtype == 2){
				$sql = "update ".$dbPrefix_curr.".tbxfieldrcvry set CallerFollowupDt = '$nextfollowup_dt' where dealid=$dealid and mm = ".date('n')."";
			}
			$affectedrows = executeUpdate($sql);
			//if($affectedrows>0){
				$response["success"] = 1;
				$response["message"] = 'Log updated successfully';
			//}
			//else{
			//	$response = error_code(1016);
			//}
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

	$sql_dealsts = "SELECT dealsts FROM ".$dbPrefix.".tbmdeal WHERE dealid = $dealid";
	$dealsts = executeSingleSelect($sql_dealsts);

	$sql_dues = "SELECT dctyp as type,round(ChrgsApplied-ChrgsRcvd) as amount,round(MinChrgs) as min_amount FROM ".$dbPrefix.".tbmdealchrgs WHERE DealId=$dealid AND DcTyp NOT IN (101,102,111) AND ChrgsApplied > ChrgsRcvd GROUP BY Dctyp";
	$dues = executeSelect($sql_dues);

	$sql_od1 = "select sum(dueamt+collectionchrgs) as charges_applied from ".$dbPrefix.".tbmduelist where duedt <= '$current_date' and dealid = $dealid";
	$sql_od2 = "select sum(chrgsrcvd) from ".$dbPrefix.".tbmdealchrgs where dctyp IN (101,102,111) and dealid=$dealid";
	$od = executeSingleSelect($sql_od1)-executeSingleSelect($sql_od2);

	$sql_od_minamt = "select round(sum(MinChrgs)) from ".$dbPrefix.".tbmdealchrgs where dctyp IN (101,102,111) and dealid=$dealid";
	$od_minamt = executeSingleSelect($sql_od_minamt);

	if($foreclosure == 1){
		$foreclosure_amt = foreclosure($dealid);
		$index=count($dues['result']);
		$dues['result'][$index]= array();
		$dues['result'][$index]["type"]='101';
		$dues['result'][$index]["amount"]=strval($od+$foreclosure_amt);
		$dues['result'][$index]["min_amount"]=$od_minamt;
	}
	else{
		$index=count($dues['result']);
		$dues['result'][$index]= array();
		$dues['result'][$index]["type"]='101';
		$dues['result'][$index]["amount"]=strval($od);
		$dues['result'][$index]["min_amount"]=$od_minamt;
	}

	$response = array();

	if($dealsts == 3){
		$response = error_code(1018);
		echo json_encode($response);
		return;
	}
	else{

		//if($dues['row_count']>0 || $od>0){
			$response["success"] = 1;
		//}
		//else{
		//	$response = error_code(1018);
		//	echo json_encode($response);
		//	return;
		//}
	}
	//Reversing order of dues
	$dues['result'] = array_reverse($dues['result']);

	$response["dues"] = $dues;
	$response["dues"]["row_count"]=count($dues);
	$response["dues"]["found_rows"]=count($dues);
	echo json_encode($response);
}

//14
function postDues(){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();

	//$jrnlno = 'J-0000';
	$empid = $request->params('empid');
	$tranno = $request->params('tranno');
	$posid = $request->params('posid');
	$deal_no = $request->params('dealno');
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
	$dealid = $request->params('dealid');
	$dealname = $request->params('dealname');
	$mobileno = $request->params('mobile');
	$foreclosure = $request->params('foreclosure');
	$emidue = 0;
	$otherdue = 0;
	$dealno = 0;
	if(strlen($deal_no) < 6){
		$dealno = str_pad($deal_no, 6, "0", STR_PAD_LEFT);
	}
	else{
		$dealno = $deal_no;
	}

	$sql_locktable = "LOCK TABLES ".$dbPrefix_curr.".`tbxcuryymmno` WRITE";
	$lockid = executeQuery($sql_locktable);

	$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),lpad(mm,2,0),lpad(curid,5,0)) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'CASHRCPT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
	$jrno = executeSingleSelect($sql_jrnlno);

	if (isset($jrno)){
		$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'CASHRCPT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
		$affectedrows_CurId = executeUpdate($sql_updateCurId);
	}
	else{
		$sql_insertcurno = "INSERT INTO ".$dbPrefix_curr.".`tbxcuryymmno`(`FieldNm`,`YY`,`MM`,`CurId`,`JrnlInd`) VALUES ('CASHRCPT',YEAR(NOW()),MONTH(NOW()),'1','J1')";
		$lastid_insertcurno = executeInsertQuery($sql_insertcurno);

		if($lastid_insertcurno>0){
			$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),lpad(mm,2,0),lpad(curid,5,0)) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'CASHRCPT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
			$jrno = executeSingleSelect($sql_jrnlno);

			if (isset($jrno)){
				$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'CASHRCPT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
				$affectedrows_CurId = executeUpdate($sql_updateCurId);
			}
		}
	}

	$sql_unlocktables = "UNLOCK TABLES";
	$unlockid = executeQuery($sql_unlocktables);
	if($unlockid >0){
		$jrnlno = $jrno;


	}


	$sql = "INSERT INTO ".$dbPrefix_curr.".tbxrcptjrnl (JrnlNo,TranNo,EmpId,POSId,DealNo,RcptDate,TotAmt,PayMode,TranTime,RcptMode) VALUES ('$jrnlno', '$tranno', '$empid', '$posid', '$dealno', NOW(), '$totamt', '$paymode', CURTIME(), '$rcptmode')";
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

			$sql_update_mincharges = "update ".$dbPrefix.".tbmdealchrgs set MinChrgs = '0' where DCTyp = $dctyp AND dealid = '$dealid'";
			$affectedrows_mincharges = executeUpdate($sql_update_mincharges);


			if($dctyp == 101){
				$emidue = $dctypamt;
			}
			else{
				$otherdue = $otherdue + $dctypamt;
			}

		}
		if ($lastinsertid > 0){

			if($foreclosure == 1){
				$sql_insert_foreclosure= "INSERT INTO ".$dbPrefix.".tbmdealnocjrnl(TranDt,JrnlNo,DealId,DealNo,DealNm,UpdSts) VALUES(NOW(),CONCAT('R-','$dealid'),'$dealid','$dealno','$dealname','0')";
				$lastinsertid_foreclosure = executeInsert($sql_insert_foreclosure);
			}

			$msg = 'Dear Customer, we have received payment of Rs '.$totamt.'.'.' EMI Due: Rs '.$emidue.'.'.' Penalty, Chq Bouncing and Other Charges: Rs '.$otherdue.'.'.' Thank you. -LokSuvidha 9209058000';
			$msgtag = 'DEAL RECEIPT';
			$sentto = '3';
			sendsms($mobileno,$msg,$dealno,$msgtag,$sentto);

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
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();

	$empid = $request->params('empid');
	$posid = $request->params('posid');
	$jrnlno = $request->params('jrnlno');
	$dealno = $request->params('dealno');
	$dealname = $request->params('dealname');
	$mobile = $request->params('mobile');

	$sql_insert_mobileno= "INSERT INTO ".$dbPrefix.".tbmmobilejrnl(`TranDt`,`POSId`,`JrnlNo`,`DealNo`,`DealNm`,`Mobile`,`Active`,`InsertUserId`,`InsertTimeStamp`) VALUES(NOW(),'$posid','$jrnlno','$dealno','$dealname','$mobile','2','$empid',NOW())";
	$lastinsertid_mobileno = executeInsert($sql_insert_mobileno);

	if($lastinsertid_mobileno>0){
		$response["success"] = 1;
		$response["message"] = 'Mobile number successfully updated';
	}
	else{
		$response = error_code(1053);
	}
	echo json_encode($response);
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

	//$jrnlno = 'J-0000';
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

	$sql_locktable = "LOCK TABLES ".$dbPrefix_curr.".`tbxcuryymmno` WRITE";
		$lockid = executeQuery($sql_locktable);

		$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),lpad(mm,2,0),lpad(curid,5,0)) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'DEALPMNT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
		$jrno = executeSingleSelect($sql_jrnlno);

		if (isset($jrno)){
			$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'DEALPMNT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
			$affectedrows_CurId = executeUpdate($sql_updateCurId);
		}

		else{
			$sql_insertcurno = "INSERT INTO ".$dbPrefix_curr.".`tbxcuryymmno`(`FieldNm`,`YY`,`MM`,`CurId`,`JrnlInd`) VALUES ('DEALPMNT',YEAR(NOW()),MONTH(NOW()),'1','P1')";
			$lastid_insertcurno = executeInsertQuery($sql_insertcurno);

			if($lastid_insertcurno>0){
				$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),lpad(mm,2,0),lpad(curid,5,0)) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'DEALPMNT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
				$jrno = executeSingleSelect($sql_jrnlno);

				if (isset($jrno)){
					$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'DEALPMNT' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
					$affectedrows_CurId = executeUpdate($sql_updateCurId);
				}
			}
		}


		$sql_unlocktables = "UNLOCK TABLES";
		$unlockid = executeQuery($sql_unlocktables);
		if($unlockid >0){
			$jrnlno = $jrno;
		}


	$sql = "INSERT INTO ".$dbPrefix_curr.".tbxdealpmntjrnl (JrnlNo,TranNo,EmpId,TranDate,BankId,BankAcId,BranchId,BranchCode,Amount,TranTime,InsertUserId) VALUES ('$jrnlno', '$tranno', '$empid', NOW(), '$bankid', '$bankacid', '$branchid', '$branchcode', '$amount', CURTIME(), '$empid')";
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
		$sql = "SELECT sql_calc_found_rows fr.dealid, fr.dealno, tcase(fr.dealnm) as name, tcase(fr.city) as city, tcase(fr.area) as area,tcase(fr.address) as address, round(fr.OdDueAmt) as overdue, round(fr.DueAmt) as total_due, round(fr.MinReqOD) as minreq_od, fr.rgid as bucket, fr.bankid, fr.Mobile as mobile, fr.GuarantorMobile as guarantor_mobile, tcase(fr.model) as vehicle_model, concat(fr.dd,'-',fr.mm,'-',fr.yy) as assigned_on, fr.rec_flg as recovered_flg, fr.expired as expired_flg, DATE_FORMAT(STR_TO_DATE(CONCAT(fr.dd,'-',fr.mm,'-',fr.yy),'%d-%m-%Y'),'%d-%m-%Y') AS assigned_dt, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt, DATE_FORMAT(case when fr.SRAFollowupDt is null then fr.CallerFollowupDt else fr.SRAFollowupDt end,'%d-%m-%Y') as sra_followup_dt,dt.UpdateTimeStamp as update_timestamp from ".$dbPrefix_curr.".tbxfieldrcvry fr join ".$dbPrefix.".tbmdealtimestamp dt on fr.dealid=dt.dealid where fr.mm = ".date('n')." and fr.sraid = $sraid and dt.UpdateTimeStamp > '$lasttimestamp'";

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
function postDiagnosticLogs(){

}


//26
function postEscalation(){

}


//27
function requestNOC(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$path = $_SESSION['IMG_UPLOAD_PATH'];

	//$path = "D:/inetpub/wwwroot/in.loksuvidha.com/content/";
	//$path = "D:/inetpub/wwwroot/dev.loksuvidha.local/content/";

	$response = array();
	if (isset($_FILES['image']['name'])) {
		$filename = isset($_POST['filename']) ? $_POST['filename'] : '';
		$dealid = isset($_POST['dealid']) ? $_POST['dealid'] : '';
		$deal_no = isset($_POST['dealno']) ? $_POST['dealno'] : '';
		$dealname = isset($_POST['dealname']) ? $_POST['dealname'] : '';
		$posid = isset($_POST['posid']) ? $_POST['posid'] : '';
		$rtoregno = isset($_POST['rtoregno']) ? $_POST['rtoregno'] : '';
		$file_name = $filename;
		$dealno = 0;
		if(strlen($deal_no) < 6){
			$dealno = str_pad($deal_no, 6, "0", STR_PAD_LEFT);
		}
		else{
			$dealno = $deal_no;
		}

		$subpath = 'RtoCopy';
		$newpath = $path.$subpath."/";
		if (!file_exists($newpath)) {
		    mkdir($newpath, 0777, true);
		}

    	$target_path = $newpath . $filename;

		if( file_exists($target_path) ) {
			$no = 1;
			while(file_exists($target_path)){
			   	$name = substr($filename, 0, -5);
				$no++;
				$file_name=$name.$no.".jpg";
			   	$target_path = $newpath.$file_name;
			}
		}

		$sql_insert = "INSERT INTO ".$dbPrefix.".tbmdealnocjrnl(TranDt,JrnlNo,DealId,DealNo,DealNm,UpdSts,RTORegNo,POSId)
		VALUES(NOW(),CONCAT('R-','$dealid'),'$dealid','$dealno','$dealname','0','$rtoregno','$posid')
		ON DUPLICATE KEY UPDATE RTORegNo = '$rtoregno',POSId = '$posid'";

		$lastid = executeInsert($sql_insert);

		if($lastid>0){
			try {
				if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
					$response["success"] = 0;
					$response["message"] = 'Could not upload the document!';
				}
				else{
					$response["success"] = 1;
					$response["message"] = 'NOC request has been successfully posted!';
				}
			}
			catch (Exception $e) {
				$response["success"] = 0;
				$response["message"] = $e->getMessage();
   			}
		}
		else{
			$response["success"] = 0;
			$response["message"] = 'Failed to post NOC request';
		}
	}
	else {
	    $response["success"] = 0;
	    $response["message"] = 'Not received any file!';
	}
	echo json_encode($response);
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

	$sql_insert = "insert into ".$dbPrefix_curr.".tbxsms(CmpnyCd,MsgDtTm,Mobile,Message,MsgTag,DealNo,MsgPriority,status,SentTo)
	values ('LKSA',now(),'$mobileno','$msg','$msgtag','$dealno',1,0,'$sentto')";

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

	if(!isset($l['result'])){
		return $ledger;
	}
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
	$endyy = (date('n') < 4 ? (date('Y')-1) : date('Y'));
	//TO-DO: Loop exist criteria is wrong for months of JAN, FEB, MARCH. Check it.

	for ($d = $startyy; $d <= $endyy; $d++){
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

		$sql_reference = "select refnm1 as name1, refmob1 as mobile1, refnm2 as name2, refmob2 as mobile2 from ".$dbPrefix.".tbmdeal where DealId=$dealid";
		$reference = executeSelect($sql_reference);

		//$sql_assignment = "select fr.mm as month,fr.yy as year,fr.CallerId as caller_empid,e1.name as caller_name,e1.mobile as caller_phone,fr.SRAId as sra_id,e.name as sra_name,e.Mobile as sra_phone from ".$dbPrefix_curr.".tbxfieldrcvry fr join ".$dbPrefix.".tbmemployee e join ".$dbPrefix.".tbmemployee e1 on fr.SRAId = e.id and fr.CallerId = e1.id where fr.DealId=$dealid";

		$sql_assignment = "select fr.mm as month,fr.yy as year,fr.CallerId as caller_empid,e1.name as caller_name,e1.mobile as caller_phone,fr.SRAId as sra_id,e.name as sra_name,e.Mobile as sra_phone from ".$dbPrefix_curr.".tbxfieldrcvry fr join ".$dbPrefix.".tbmemployee e join ".$dbPrefix.".tbmemployee e1 on fr.SRAId = e.oldid and fr.CallerId = e1.portalid where fr.DealId=$dealid limit 4"; //AND fr.mm = MONTH(CURDATE())";

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

		$sql_paytype = "SELECT case paytype WHEN 1 THEN 'PDC' WHEN 2 THEN 'ECS' When 3 THEN 'NACH' ELSE 'Nothing' End as type, case ApprvFlg WHEN 0 THEN 'Pending' WHEN 1 THEN 'Approved' WHEN 2 THEN 'Rejected' End as status FROM $dbPrefix.tbmpaytype p LEFT JOIN $dbPrefix_curr.tbxinstrumentrcvry r ON p.dealid = r.dealid AND mm = MONTH(NOW()) WHERE p.dealid =  $dealid ORDER BY paytype DESC LIMIT 0,1 ";
		$paytype = executeSelect($sql_paytype);
		$type = null;
		$status = null;
		if($paytype['row_count'] > 0){
			$type = $paytype['result'][0]['type'];
			$status = $paytype['result'][0]['status'];
		}

		$sql_paymenttype = "SELECT CASE paytype WHEN 1 THEN 'PDC' WHEN 2 THEN 'ECS' WHEN 3 THEN 'NACH' ELSE 'Nothing' END AS type,
		case ApprvFlg WHEN 0 THEN 'Pending' WHEN 1 THEN 'Approved' WHEN 2 THEN 'Rejected' End as status, ApprvFlg AS approved,
		DrownOn,Place as Branch, UMRN, TotAmt AS amt, Remark, ResponceDt AS dt, pendingpdc, requiredpdc
		FROM $dbPrefix.tbmpaytype p
		LEFT JOIN $dbPrefix_curr.tbxinstrumentrcvry r ON p.dealid = r.dealid AND mm = MONTH(NOW())
		WHERE p.dealid =  '$dealid' ORDER BY paytype DESC LIMIT 0,1";
		$paymenttype = executeSelect($sql_paymenttype);


		//$sql_bounce = "select concat(count(case when status=-1 then 1 end),'/',count(depositdt)) as bounced from ".$dbPrefix.".tbmpaytypedtl where active=2 and depositdt IS NOT NULL and dealid = $dealid";
		//$bounce = executeSingleSelect($sql_bounce);

		$bounce = bounced($dealid,$deal['hpdt']);

		$sql_seized = "select count(dealid)as seized from ".$dbPrefix_curr.".tbxvhclsz where dealid=$dealid";
		$seized = executeSingleSelect($sql_seized);

		$forclosureamt="";
		$fcloseamt = getForeclosureAmount($dealid);
		if($fcloseamt == -1){
			$forclosureamt = "Error";
		}
		else if($fcloseamt == -2){
			$forclosureamt = "Deal Closed";
		}
		else if($fcloseamt == -3){
			$forclosureamt = "Foreclosure not allowed before 12 months";
		}
		else if($fcloseamt == -4){
			$forclosureamt = "Can not calculate";
		}
		else{
			$forclosureamt = strval($fcloseamt);
		}

		$deals['result'][$i]['bounce'] = $bounce;
		$deals['result'][$i]['seized'] = $seized;
		$deals['result'][$i]['forclosureamt'] = $forclosureamt;
		$deals['result'][$i]['type'] = $type;
		$deals['result'][$i]['status'] = $status;
		$deals['result'][$i]['paytype'] = $paymenttype;
		$deals['result'][$i]['phonenumbers'] = $phones;
		$deals['result'][$i]['guarantor'] = $guarantor;
		$deals['result'][$i]['reference'] = $reference;
		$deals['result'][$i]['dealcharges'] = $dealcharges;
		$deals['result'][$i]['assignment'] = $assignment;
		$deals['result'][$i]['ledger'] = $ledgers;
		$deals['result'][$i]['logs'] = $logs;
	}
	return $deals;
}



function bounced($dealid,$hpdt){
	$dbPrefix = $_SESSION['DB_PREFIX'];


	$hp_mm = date("n", strtotime($hpdt));
	$hp_yy= date("Y", strtotime($hpdt));
	$startyy = ($hp_mm < 4 ? ($hp_yy-1) : $hp_yy);

	$fy_arr = array();
	$mm = date('m'); $yy = date('Y'); $i = date('Y');
	if($mm < 4) $i--;
	for($i; $i >= $startyy; $i--){
		$fy_arr[] = array(substr($i,-2)."-".substr($i+1,-2),$i,$i+1);
	}

	$q_bounce ="SELECT CONCAT(SUM(bounce),'/', SUM(dpst)) AS bounced FROM (";
	foreach($fy_arr AS $i=> $fy1){
		$db = "$dbPrefix".$fy1[1]."".str_pad($fy1[2]-2000, 2, '0', STR_PAD_LEFT);
		$d = $fy1[1];
		$q_bounce .="
		SELECT COUNT(CASE WHEN cbflg=-1 THEN 1 END) AS bounce, COUNT(rcptid) AS dpst  FROM $db.`tbxdealrcpt`
		WHERE dealid = $dealid AND rcptpaymode IN (2,6,7) AND cclflg = 0
		UNION";
	}
	$q_bounce = RTRIM($q_bounce, "UNION");
	$q_bounce .=") b";

	$bounce = executeSingleSelect($q_bounce);

	return $bounce;
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





function getForeclosureAmount($dealid, $withAllCharges = false, $adminLogin = false){
    // Returns
    //-2 if deal is closed,
    //-1 if Error
    //-3 If foreclosure Not allowed.
    //-4 Can not calculate
    // Amount if deal is live and allowed

    $fc_amt = -1;

    $dbPrefix = $_SESSION['DB_PREFIX'];
    $q = "SELECT d.dealid, d.closedt, d.hpexpdt, d.financeamt, d.period, d.dealsts, d.hpdt, period_diff(date_format(now(), '%Y%m'), date_format(startduedt, '%Y%m')) as elapsed_period, sum(round(dl.DueAmt+dl.CollectionChrgs)) as due FROM $dbPrefix.tbmdeal d JOIN $dbPrefix.tbmduelist dl ON d.dealid = $dealid AND d.cancleflg = 0 AND dl.dealid = $dealid AND dl.duedt <= curdate()";

    $t1 = executeSelect($q);
    if($t1['row_count'] <= 0){
        return;
    }
    $deal = $t1['result'][0];
    if($deal['dealsts'] == 3){
        return -2;
    }
    $period = $deal['period'];
    $elapsed_period = $deal['elapsed_period'];
    $dueListSum = $deal['due'];

    $q3 ="SELECT SUM(receiptamt) as rcptamt FROM (";
    foreach($_SESSION['FY_ARR'] as $i=> $fy1){
        $db = "$dbPrefix".$fy1[1]."".str_pad($fy1[2]-2000, 2, '0', STR_PAD_LEFT);
        $d = $fy1[1];
        $q3 .="
        SELECT $d as yy, SUM(acxnamt) as receiptamt FROM $db.tbxdealrcpt r join $db.tbxacvoucher v
        ON r.dealid = $dealid and r.rcptno = v.rcptno AND cbflg = 0 AND cclflg = 0 AND v.xreftyp = 1100 and v.acvchtyp = 4 AND v.acxnsrno = 0 and v.reconind = 1
        UNION";
    }
    $q3 = rtrim($q3, "UNION");
    $q3 .=") vouchers";

    $pending_amount = executeSingleSelect($q3);

    $q17 = "select sum(dueamt+collectionchrgs) as hpod, sum(collectionchrgs) as cc, sum(finchrg) as fc from ".$_SESSION['DB_PREFIX'].".tbmduelist where duedt > NOW() and dealid = $dealid group by dealid";

    $q11 = "SELECT
    SUM(CASE WHEN dctyp IN (101,102,111) THEN chrgsrcvd ELSE 0 END) AS rEMI,
    SUM(CASE WHEN dctyp IN (101,102,111) THEN chrgsapplied - chrgsrcvd ELSE 0 END) AS pEMI,
    SUM(CASE WHEN dctyp NOT IN (101,102,111) THEN chrgsapplied - chrgsrcvd ELSE 0 END) AS AllOtherCharges
    FROM tbmdealchrgs WHERE dealid = $dealid";

    if($period > $elapsed_period){//Deal is not expired
        $t1 = executeSelect($q17);    if($t1['row_count'] > 0){$premature = $t1['result'];}
        $t1 = executeSelect($q11); if($t1['row_count'] > 0){$dealcharges = $t1['result'][0];}
        $hpod = 0; $cc = 0; $fc = 0;
        if(isset($premature)){
            $hpod = $premature[0]['hpod']; $cc = $premature[0]['cc']; $fc = $premature[0]['fc'];
        }
        $fc_penalty = 8; //Percentage

        $pending_period = $period - $elapsed_period;
        if($pending_period > 0 && $pending_period <= 8){
            $fc_amt = $hpod + $pending_amount;
            $fc_bal = $dueListSum - $dealcharges['rEMI'];
            $fc_amt += $fc_bal;
        }
        else{
            if($elapsed_period <= 11){
                $fc_penalty = 8;
                if(!$adminLogin)
                    return -3; //Foreclosure not allowed
            }
//            else if($elapsed_period > 6 and $elapsed_period <= 12){
//                $fc_penalty = 6;
//            }
            else{
                $fc_penalty = 5;
            }
            $fc_amt = (1+$fc_penalty/100) * $hpod - $cc - $fc + $pending_amount;
            if($fc_amt < 0){
                $fc_amt = -4; //Foreclosure is -ve and cant be calculated
                return $fc_amt;
            }
            $fc_bal = (1+$fc_penalty/100) * ($dueListSum - $dealcharges['rEMI']);
            $fc_amt += $fc_bal;
        }
        if($withAllCharges){
            $fc_amt += $dealcharges['AllOtherCharges'];
        }
    }

   return $fc_amt;

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

	$sql = "select d.dealid as deal_id, d.dealno as deal_no,case when d.dealsts = 1 then 'Active' when d.dealsts = 2 then 'Draft' when d.dealsts = 3 then 'Closed' end as deal_status,round(d.financeamt) as loan_amt,(case when d.paytype=1 then 'PDC' when d.paytype=2 then 'ECS' when d.paytype=3 then 'NACH' end) as payment_mode,tcase(concat(dv.make, ' ', dv.model, ' ',dv.modelyy)) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as vehicle_rto_reg_no,
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


function getAcBalance($acid,$acxndt){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];

	$response = array();
	$AcTotCR_AcxnAmt = 0;
	$AcTotDR_AcxnAmt = 0;

	$sql_selectTotCR = "SELECT AcId, SUM(AcxnAmt) AS TotCr FROM ".$dbPrefix_curr.".tbxAcVoucher WHERE AcId = '$acid' AND AcxnTyp = 2 AND AcxnDt <='$acxndt' GROUP BY AcId";
	$totCR = executeSelect($sql_selectTotCR);
	if($totCR['row_count']>0){
		$AcTotCR_AcxnAmt = $totCR['result'][0]['TotCr'];
	}

	$sql_selectTotDR = "SELECT AcId, SUM(AcxnAmt) AS TotDr FROM ".$dbPrefix_curr.".tbxAcVoucher WHERE AcId = '$acid' AND AcxnTyp = 1 AND AcxnDt <='$acxndt' GROUP BY AcId";
	$totDR = executeSelect($sql_selectTotDR);
	if($totDR['row_count']>0){
	 	$AcTotDR_AcxnAmt = $totDR['result'][0]['TotDr'];
	}

	$sql_selectOpBal = "SELECT AcOpBalTyp,AcOpBal FROM ".$dbPrefix_curr.".tbxacbalance WHERE AcId = '$acid'";
	$opBal = executeSelect($sql_selectOpBal);
	if($opBal['row_count']>0){

		$AcOpBal_AcxnDt = $acxndt;
	   	$AcOpBal_AcId = $acid;
		$AcOpBal_AcxnTyp = $opBal['result'][0]['AcOpBalTyp'];
       	$AcOpBal_AcxnAmt = $opBal['result'][0]['AcOpBal'];
		$TotDR = 0;
		$TotCR = 0;


		if( $AcOpBal_AcxnTyp == 1){
			$TotDR = $AcOpBal_AcxnAmt + $AcTotDR_AcxnAmt;
			$TotCR = $AcTotCR_AcxnAmt;
		}
		else{
			$TotCR = $AcOpBal_AcxnAmt + $AcTotCR_AcxnAmt;
			$TotDR = $AcTotDR_AcxnAmt;
	   	}

	 	if( $TotCR > $TotDR ){
			$AcOpBal_AcxnAmt = $TotCR - $TotDR;
		    if( $AcOpBal_AcxnAmt > 0){
		       	$AcOpBal_AcxnTyp = 2;
		    }
		}
		else{
		  	$AcOpBal_AcxnAmt = $TotDR - $TotCR;
		   	if ($AcOpBal_AcxnAmt > 0){
		   		$AcOpBal_AcxnTyp = 1;
		   	}
	     }

	  	$response["success"] = 1;
		$response["acid"] = $acid;
		$response["acxndate"] = $acxndt;
		$response["acbalance"] = $AcOpBal_AcxnAmt;
		$response["acxntype"] = $AcOpBal_AcxnTyp;

	}
	else{
		$response = error_code(1046);
		echo json_encode($response);
		return;
	}

	echo json_encode($response);

}


function getUnreconcileDepositEntry($posid){
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];

    $sql_q1 = "SELECT p.TranDate, sa.bankShNm, bb.`BankBrnchNm`, (CASE WHEN p.CclFlg = -1 THEN 4 ELSE (CASE WHEN v.ReconInd IS NULL THEN 0 ELSE v.ReconInd END) END) ReconInd, p.Amount, (CASE WHEN v.AcxnAmt IS NULL THEN p.Amount ELSE v.AcxnAmt END) AS BankDpstAmt
    FROM ".$dbPrefix_curr.".`tbxdealpmntjrnl` p LEFT JOIN ".$dbPrefix_curr.".tbxAcVoucher AS v ON  p.VchrNo=v.AcVchNo AND AcVchTyp=3 AND v.AcxnSrNo = 1
    JOIN ".$dbPrefix.".tbmsourcebank sa ON sa.BankId=p.BankId
    JOIN ".$dbPrefix.".tbmsourcebankbrnch bb ON bb.BankBrnchId= p.BranchId
    WHERE POSId='$posid'";

    $sql_q2 = "SELECT p.TranDate, sa.bankShNm, bb.`BankBrnchNm`, (CASE WHEN p.CclFlg = -1 THEN 4 ELSE (CASE WHEN v.ReconInd IS NULL THEN 0 ELSE v.ReconInd END) END) ReconInd, p.Amount, (CASE WHEN v.AcxnAmt IS NULL THEN p.Amount ELSE v.AcxnAmt END) AS BankDpstAmt
	FROM ".$dbPrefix_last.".`tbxdealpmntjrnl` p LEFT JOIN ".$dbPrefix_last.".tbxAcVoucher AS v ON  p.VchrNo=v.AcVchNo AND AcVchTyp=3 AND v.AcxnSrNo = 1
	JOIN ".$dbPrefix.".tbmsourcebank sa ON sa.BankId=p.BankId
	JOIN ".$dbPrefix.".tbmsourcebankbrnch bb ON bb.BankBrnchId= p.BranchId
    WHERE POSId='$posid'";

    $sql_trandt = "SELECT * FROM (SELECT MIN(TranDate) as TranDate FROM ".$dbPrefix_curr.".tbxdealpmntjrnl p LEFT JOIN ".$dbPrefix_curr.".tbxAcVoucher AS v ON  p.VchrNo=v.AcVchNo AND AcVchTyp=3  AND v.AcxnSrNo=1  WHERE POSId='$posid' AND ( p.VchrNo IS NULL OR v.ReconInd !=3)
	UNION
	SELECT MIN(TranDate) as TranDate FROM ".$dbPrefix_last.".tbxdealpmntjrnl p LEFT JOIN ".$dbPrefix_last.".tbxAcVoucher AS v ON  p.VchrNo=v.AcVchNo AND AcVchTyp=3  AND v.AcxnSrNo=1  WHERE POSId='$posid' AND ( p.VchrNo IS NULL OR v.ReconInd !=3))t1
	ORDER BY TranDate";
    $trandt = executeSelect($sql_trandt);

    if($trandt['row_count']>0 && isset($trandt['result'][0]['TranDate'])){

        $trandate = $trandt['result'][0]['TranDate'];

		$sql_unreconcileentry = "SELECT * FROM (".$sql_q1." AND TranDate >= '$trandate' UNION ".$sql_q2." AND TranDate >= '$trandate')t1 ORDER BY TranDate";

		$rowcount = executeSelect($sql_unreconcileentry);

        if($rowcount['row_count'] < 10){

			$sql_unreconcileentry = "SELECT * FROM (".$sql_q1." UNION ".$sql_q2.")t1 ORDER BY TranDate DESC limit 10";
		}
    }
    else{

        $sql_unreconcileentry = "SELECT * FROM (".$sql_q1." UNION ".$sql_q2.")t1 ORDER BY TranDate DESC limit 10";
	}

    $unreconcileentry = executeSelect($sql_unreconcileentry);

    $response = array();
    if($unreconcileentry['row_count']>0){
        $response["success"] = 1;
    }
    else if($unreconcileentry['row_count']==0){
	   	$response["success"] = 1;
    }
    else{
        $response = error_code(1049);
        echo json_encode($response);
        return;
    }
    $response["unreconciledepositentry"] = $unreconcileentry;
    echo json_encode($response);
}


function getCashInHand($sraid){
	$dbPrefix = $_SESSION['DB_PREFIX'];

	$response = array();
    $rcptamt = 0;
    $dpstamtpercent = 0;
    $limit = 0;
    $printerid = 0;

    //$sql = "SELECT rcptamt FROM ".$dbPrefix.".tbmcashinhand WHERE empid = '$sraid'";
    //$result = executeSelect($sql);

    $sql = "SELECT IFNULL(c.rcptamt,0) AS rcptamt, e.DpstAmtPercent, e.Walletlimit, d.printerid
    FROM ".$dbPrefix.".tbmemployee e
    LEFT JOIN ".$dbPrefix.".tbmdevices d ON e.id = d.empid
    LEFT JOIN ".$dbPrefix.".tbmcashinhand c ON c.empid = e.oldid  AND e.active=2 WHERE e.Oldid = '$sraid'";
    $result = executeSelect($sql);


    if($result['row_count']>0){
		$rcptamt = $result['result'][0]['rcptamt'];
		$dpstamtpercent = $result['result'][0]['DpstAmtPercent'];
		$limit = $result['result'][0]['Walletlimit'];
		$printerid = $result['result'][0]['printerid'];
		$response["success"] = 1;
        $response["rcptamt"] = $rcptamt;
        $response["dpstamtpercent"] = $dpstamtpercent;
        $response["walletlimit"] = $limit;
        $response["printerid"] = $printerid;
	}
    else{
        $response = error_code(1050);
        echo json_encode($response);
        return;
    }
	echo json_encode($response);
}


function postDepositEntry($tranno,$posid,$trandate,$bankid,$bankacid,$branchid,$branchcode,$amount,$trantime,$usedlimit){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix = $_SESSION['DB_PREFIX'];

	$sql_locktable = "LOCK TABLES ".$dbPrefix_curr.".`tbxcuryymmno` WRITE";
	$lockid = executeQuery($sql_locktable);

	$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),mm,curid) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
	$jrno = executeSingleSelect($sql_jrnlno);

	if (isset($jrno)){
		$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
		$affectedrows_CurId = executeUpdate($sql_updateCurId);
	}

	else{
		$sql_insertcurno = "INSERT INTO ".$dbPrefix_curr.".`tbxcuryymmno`(`FieldNm`,`YY`,`MM`,`CurId`,`JrnlInd`) VALUES ('CASHDEPO',YEAR(NOW()),MONTH(NOW()),'1','P1')";
		$lastid_insertcurno = executeInsertQuery($sql_insertcurno);

		if($lastid_insertcurno>0){
			$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),mm,curid) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
			$jrno = executeSingleSelect($sql_jrnlno);

			if (isset($jrno)){
				$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
				$affectedrows_CurId = executeUpdate($sql_updateCurId);
			}
		}
	}

	$sql_unlocktables = "UNLOCK TABLES";
	$unlockid = executeQuery($sql_unlocktables);
	if($unlockid >0){
		$jrnlno = $jrno;
	}

	$sql = "INSERT INTO ".$dbPrefix_curr.".tbxdealpmntjrnl (JrnlNo,TranNo,POSId,TranDate,BankId,BankAcId,BranchId,BranchCode,Amount,TranTime,UsedLimit) VALUES ('$jrnlno', '$tranno', '$posid', '$trandate', '$bankid', '$bankacid', '$branchid', '$branchcode', '$amount', '$trantime', '$usedlimit')";
	$lastid = executeInsert($sql);

	$response = array();
	if($lastid > 0){

		$sql_brkrid = "Select BrkrId From ".$dbPrefix.".tbasrapos WHERE POSId = '$posid' AND WefDt <= '$trandate' Order By WefDt Desc limit 1";
		$brkrid = executeSingleSelect($sql_brkrid);

		if($brkrid>0){

			$sql_updateCashinhand = "Update ".$dbPrefix.".tbmcashinhand SET RcptAmt = RcptAmt - '$amount' WHERE EmpId = '$brkrid'";
			$affectedrows_Cashinhand = executeUpdate($sql_updateCashinhand);

			if($affectedrows_Cashinhand == 0){

				$sql_insert = "INSERT INTO ".$dbPrefix.".tbmcashinhand(EmpId,RcptAmt) VALUES ('$brkrid','-$amount')";
				$lastid = executeInsert($sql_insert);
			}
    	}

		$response["success"] = 1;
		$response["jrnlno"] = $jrnlno;
		$response["message"] = 'Deposit Entry Successfully Posted';
	}
	else{
		$response = error_code(1051);
		echo json_encode($response);
		return;
	}
	echo json_encode($response);
}




function post_DepositEntry(){
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();


	$tranno = $request->params('tranno');
	$posid = $request->params('posid');
	$trandate = $request->params('trandate');
	$bankid = $request->params('bankid');
	$bankacid = $request->params('bankacid');
	$branchid = $request->params('branchid');
	$branchcode = $request->params('branchcode');
	$amount = $request->params('amount');
	$trantime = $request->params('trantime');
	$usedlimit = $request->params('usedlimit');



	$sql_locktable = "LOCK TABLES ".$dbPrefix_curr.".`tbxcuryymmno` WRITE";
	$lockid = executeQuery($sql_locktable);

	$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),mm,curid) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
	$jrno = executeSingleSelect($sql_jrnlno);

	if (isset($jrno)){
		$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
		$affectedrows_CurId = executeUpdate($sql_updateCurId);
	}

	else{
		$sql_insertcurno = "INSERT INTO ".$dbPrefix_curr.".`tbxcuryymmno`(`FieldNm`,`YY`,`MM`,`CurId`,`JrnlInd`) VALUES ('CASHDEPO',YEAR(NOW()),MONTH(NOW()),'1','P1')";
		$lastid_insertcurno = executeInsertQuery($sql_insertcurno);

		if($lastid_insertcurno>0){
			$sql_jrnlno = "SELECT CONCAT(jrnlind,'-',SUBSTRING(yy, 3),mm,curid) AS jrno FROM ".$dbPrefix_curr.".`tbxcuryymmno` WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
			$jrno = executeSingleSelect($sql_jrnlno);

			if (isset($jrno)){
				$sql_updateCurId = "update ".$dbPrefix_curr.".`tbxcuryymmno` set curid = curid+1 WHERE fieldnm = 'CASHDEPO' AND mm = MONTH(NOW()) AND yy = YEAR(NOW())";
				$affectedrows_CurId = executeUpdate($sql_updateCurId);
			}
		}
	}

	$sql_unlocktables = "UNLOCK TABLES";
	$unlockid = executeQuery($sql_unlocktables);
	if($unlockid >0){
		$jrnlno = $jrno;
	}

	$sql = "INSERT INTO ".$dbPrefix_curr.".tbxdealpmntjrnl (JrnlNo,TranNo,POSId,TranDate,BankId,BankAcId,BranchId,BranchCode,Amount,TranTime,UsedLimit) VALUES ('$jrnlno', '$tranno', '$posid', NOW(), '$bankid', '$bankacid', '$branchid', '$branchcode', '$amount', CURTIME(), '$usedlimit')";
	$lastid = executeInsert($sql);

	$response = array();
	if($lastid > 0){

		$sql_brkrid = "Select BrkrId From ".$dbPrefix.".tbasrapos WHERE POSId = '$posid' AND WefDt <= '$trandate' Order By WefDt Desc limit 1";
		$brkrid = executeSingleSelect($sql_brkrid);

		if($brkrid>0){

			$sql_updateCashinhand = "Update ".$dbPrefix.".tbmcashinhand SET RcptAmt = RcptAmt - '$amount' WHERE EmpId = '$brkrid'";
			$affectedrows_Cashinhand = executeUpdate($sql_updateCashinhand);

			if($affectedrows_Cashinhand == 0){

				$sql_insert = "INSERT INTO ".$dbPrefix.".tbmcashinhand(EmpId,RcptAmt) VALUES ('$brkrid','-$amount')";
				$lastid = executeInsert($sql_insert);
			}
    	}

		$response["success"] = 1;
		$response["jrnlno"] = $jrnlno;
		$response["message"] = 'Deposit Entry Successfully Posted';
	}
	else{
		$response = error_code(1051);
		echo json_encode($response);
		return;
	}
	echo json_encode($response);
}





function proposalCriteria($proposalno){
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];

    /**************************************************************************************
    ***************** LOAN ELIGIBILITY CRITERIA CHECKING ********************************
    1. Exposure Min and Max Amount. Min 15K, Max 75 or 2L depending on NIP vs IP
    2. Age - Min & Max depending on Proffesion Type (Sal vs Self Emp)
    3. Net Tenure - Min & Max depding on net Finance Amount (Disbursment + PF) -  (Above 1L and below 1L)
    4. Loksuvidha Flat ROI - Depending on ProfessionType (Sal Only), Salary Class (Gov vs Non-Gov) and Net Salary Amount
    5. Loan Amount Eligiblity Calcuation - Min of folllwings
       a. Exposure Max Amount. Max 75 or 2L depending on NIP vs IP
       b. NIP vs IP - Check Loan Multiplier Grid
       c. LTV - Loksuvidha Norms on asset type and manufacture
    6. Stability in Employment or Business - 6months vs 1 year - Not checking currently because data is captured in later stages.
    **************************************************************************************/

    $sql_verifyproposal = "select dob,Period,GrossPeriod,AdvancePeriod,AnnualIncome,RoI,ProfessionType,FinanceAmt,AdvanceEMI,StartDueDt,proposaldt,IncomeProof,IRR,VehManufacture,
    VehCategory,OnRoadPrice,DATE_ADD(startDuedt,INTERVAL period -1 MONTH) as expiarydt,Salarytype from ".$dbPrefix.".tbmproposal WHERE proposalNo = '$proposalno' ;";

    $verifyproposal = executeSelect($sql_verifyproposal);

    $response = array();

    if($verifyproposal['row_count']>0){
        $proposal = $verifyproposal['result'][0];

        $i = 0;
        $dob = strtotime($proposal['dob']);
        $ProfessionType = $proposal['ProfessionType'];
        $Period = $proposal['Period'];
        $GrossPeriod = $proposal['GrossPeriod'];
        $AdvancePeriod = $proposal['AdvancePeriod'];
        $AnnualIncome = $proposal['AnnualIncome'];
        $roi = $proposal['RoI'];
        $Salarytype = $proposal['Salarytype'];
        $MonthlyIncome = $AnnualIncome / 12;
        $StartDueDt = strtotime($proposal['StartDueDt']);
        $expiarydt = strtotime($proposal['expiarydt']);
        $proposaldt =  strtotime($proposal['proposaldt']);
        $FinanceAmt = $proposal['FinanceAmt'];
        $AdvanceEMI = $proposal['AdvanceEMI'];
        $IncomeProof=$proposal['IncomeProof'];
        $IRR=$proposal['IRR'];
        $VehManufacture=$proposal['VehManufacture'];
        $VehCategory=$proposal['VehCategory'];
        $OnRoadPrice=$proposal['OnRoadPrice'];

        $NetPeriod= $GrossPeriod - $AdvancePeriod;
        $NetFinance= $FinanceAmt - $AdvanceEMI;

        $ltvcolumnnm = 'with'. ($IncomeProof != 1 ? 'out' :''). 'income_'.tolower($VehCategory);

        $sql_ltv = "SELECT $ltvcolumnnm FROM ".$dbPrefix.".tbmmanufacturerltv AS ltv JOIN ".$dbPrefix.".tbmmanufacturer AS m ON ltv.manufactureid=m.pkid AND ltv.BankId=9 AND m.shortnm = '".$VehManufacture."';";

        $ltv = executeSingleSelect($sql_ltv);
        $ltvAmt= $OnRoadPrice *$ltv/100;

        $Asonage = date('Y',$proposaldt) - date('Y',$dob);
        $Expireage = date('Y',$expiarydt) - date('Y',$dob);

    /**************************************************************************************
    1. Exposure Min and Max Amount. Min 15K, Max 75 or 2L depending on NIP vs IP
    **************************************************************************************/
        if($IncomeProof == 1) // IP =1 , NIP=2
        {
            if($NetFinance < 15000 || $NetFinance > 200000 )
            {
                array_push($response,'Finance amount does not match with policy.');
            }
        }
        else
           {
            if($NetFinance < 15000 || $NetFinance > 75000 )
            {
                array_push($response,'Finance amount does not match with policy.');
            }
        }

    /**************************************************************************************
    2. Age - Min & Max depending on Proffesion Type (Sal vs Self Emp)
    **************************************************************************************/
        if($ProfessionType == 1)
        {
            if($Asonage < 18 || $Expireage > 65 )
            {
                array_push($response,'Customer age does not match with salaried policy.');
            }
        }
        else if($ProfessionType == 2)
        {
            if($Asonage < 21 || $Expireage > 65)
            {
                array_push($response,'Customer age does not match with self employed policy.');
            }
        }

    /**************************************************************************************
    3. Net Tenure - Min & Max depding on net Finance Amount (Disbursment + PF) -  (Above 1L and below 1L)
    **************************************************************************************/
        if($NetFinance != 0)
        {
            if($NetFinance > 100000 )
            {
                if($NetPeriod < 12  || $NetPeriod > 48  )
                {
                    array_push($response,'Period does not match with annual income more than 1,00,000 Rs policy.');
                }
            }
            else
            {
                if($NetPeriod < 12  || $NetPeriod > 36  )
                {
                    array_push($response,'Period does not match with annual income less than 1,00,000 Rs policy.');
                }
            }
        }

    /**************************************************************************************
    4. Loksuvidha Flat ROI - Depending on ProfessionType (Sal Only), Salary Class (Gov vs Non-Gov) and Net Salary Amount
    **************************************************************************************/
        if($ProfessionType == 1 && $Salarytype == 1 && $MonthlyIncome >= 15000)
        {
            if($NetPeriod <= 23 && $roi < 13.9 )
            {
                array_push($response,'ROI does not match with salary more than 15,000 for government employee.');
            }
            if($NetPeriod >= 24 && $roi < 12.4 )
             {
                array_push($response,'ROI does not match with salary more than 15,000 for government employee.');
            }
        }
        else if($ProfessionType == 1 && $Salarytype == 1 && $MonthlyIncome < 15000)
        {
            if( $NetPeriod <= 23 && $roi < 13.9 )
            {
                array_push($response,'ROI does not match with salary less than 15,000 for government employee.');
            }
            if($NetPeriod >= 24  && $roi < 12.96 )
            {
                array_push($response,'ROI does not match with salary less than 15,000 for government employee.');
            }
        }
        else if($Salarytype == 2)
        {
            if($roi < 13.9 )
            {
                array_push($response,'ROI does not match with normal employee.');
            }

        }


    /**************************************************************************************
    5. Loan Amount Eligiblity Calcuation - Min of folllwings
       a. Exposure Max Amount. Max 75 or 2L depending on NIP vs IP
       b. NIP vs IP - Check Loan Multiplier Grid
       c. LTV - Loksuvidha Norms on asset type and manufacture
    **************************************************************************************/
        if($MonthlyIncome < 8000)
        {
            array_push($response,'Loan Amount does not match with less than 8000 p.m income policy.');
        }
        else
        {
            if($IncomeProof == 1 )  //Ip=1 NIP=2
            {
                if(min($ltvAmt,$MonthlyIncome * ($MonthlyIncome <= 20000 ? 4 :7),200000) < $NetFinance )
                {
                    array_push($response,'Loan Amount does not match with income profile.');
                    $response["success"] = 0;
                }

            }
            else if($IncomeProof == 2 )
            {
                if($NetFinance > min($ltvAmt,75000))
                {
                    array_push($response,'Loan Amount does not match with income profile.');
                }
            }
        }

        /**************************************************************************************
        6. Stability in Employment or Business - 6months vs 1 year - Not checking currently because data is captured in later stages.
        **************************************************************************************/
        //if($ProfessionType == 1 && Biz_Length < 6)
        //{
        //    array_push($response,'Stability in Employment or Business does not match with policy.');
        //}
        //else if($ProfessionType == 1 && Biz_Length < 12)
        //{
        //    array_push($response,'Stability in Employment or Business does not match with policy.');
        //}

    }
    else{
        $response = error_code(1052);
        echo json_encode($response);
        return;
    }
    echo json_encode($response);
}




function getProposaldata($imei){
	$dbPrefix = $_SESSION['DB_PREFIX'];

	$sql_salesmanid = "select e.salesmanid from ".$dbPrefix.".tbmemployee e join ".$dbPrefix.".tbmdevices d on d.empid = e.id where d.imei = $imei";
	$salesmanid = executeSingleSelect($sql_salesmanid);

	if(isset($salesmanid)){

		$sql_proposal = "select p.ProposalId,p.ProposalNo,date_format(p.ProposalDt,'%d-%b-%Y') as ProposalDt,p.ProposalStatus as ProposalStatusId,(case when p.ProposalStatus=0 then 'ALL' when p.ProposalStatus=1 then 'DRAFT' when p.ProposalStatus=2 then 'FOR APPROVAL' when p.ProposalStatus=3 then 'MORE INFO' when p.ProposalStatus=4 then 'APPROVED' when p.ProposalStatus=5 then 'REJECTED' when p.ProposalStatus=6 then 'DEAL CREATED' when p.ProposalStatus=7 then 'CANCELLED' when p.ProposalStatus=8 then 'DRAFT OR MORE INFO' when p.ProposalStatus=9 then 'DRAFT OR MORE INFO OR APPROVED' when p.ProposalStatus=10 then 'PENDING FOR VERIFICATION' end) as ProposalStatus,p.ProposalNm,(case when p.Gender=1 then 'MALE' when p.Gender=2 then 'FEMALE' end) as Gender,p.DOB,p.ProfessionType,p.Mobile,otp1.VerifyStatus as MobileVerify,p.Mobile2,otp2.VerifyStatus as Mobile2Verify,p.Tel1,p.Pin,p.Add1,p.Add2,p.Area,p.City,p.Tahasil,p.State,p.Profession,round(p.AnnualIncome) as AnnualIncome,p.AadharCardNo,p.Language,p.RefNm1,p.RefMob1,otpre.VerifyStatus as ReferenceMobileVerify,p.BankId,round(p.CostOfVhcl) as CostOfVhcl,p.RoI,pfd.GrossPeriod,pfd.AdvancePeriod,p.Period,p.BrkrId,br.BrkrNm,tcase(p.VehMake)as VehMake,tcase(p.VehModel) as VehModel,round(p.FinanceAmt) as FinanceAmt,pfd.ECSFlag,pfd.DocumentChrgFlag,pg.GrtrNm,pg.Add1 as GrtrAdd1,pg.Add2 as GrtrAdd2,pg.City as GrtrCity,pg.Mobile as GrtrMobile,otpgr.VerifyStatus as GuarantorMobileVerify,pg.AadharCardNo as GrtrAadharCardNo,md.CodeDscrptnMasterDataId as ECSAmount from ".$dbPrefix.".tbmproposal p
		left join ".$dbPrefix.".tbmbroker br on p.BrkrId = br.BrkrId
		left join ".$dbPrefix.".tbmprpslguarantors pg on p.ProposalId = pg.ProposalId
		left join ".$dbPrefix.".tbaproposalfnncdtls pfd on p.ProposalId = pfd.ProposalId
		left join ".$dbPrefix.".tbmotp otp1 on p.Mobile = otp1.mobile and otp1.VerifyStatus=1
		left join ".$dbPrefix.".tbmotp otp2 on p.Mobile2 = otp2.mobile and otp2.VerifyStatus=1
		left join ".$dbPrefix.".tbmotp otpgr on pg.Mobile = otpgr.mobile and otpgr.VerifyStatus=1
		left join ".$dbPrefix.".tbmotp otpre on p.RefMob1 = otpre.mobile and otpre.VerifyStatus=1
		left join ".$dbPrefix.".tbmcodedscrptnmasterdata md on MasterId=265
		where p.salesmanid = $salesmanid AND p.ProposalDt > DATE_SUB(NOW(), INTERVAL 4 MONTH)order by p.pkid desc";
		$proposal = executeSelect($sql_proposal);

		foreach($proposal['result'] as $i=> $doc){
			$proposalid = $doc['ProposalId'];

			$sql_proposalremark = "SELECT rnote as remark FROM ".$dbPrefix.".tbmprpslremark WHERE proposalid = '$proposalid'";
			$proposalremark = executeSelect($sql_proposalremark);
			$proposal['result'][$i]['ProposalRemark'] = $proposalremark;

			$sql_docs = "SELECT docid,(case when docid = 1 then 'POST DATED CHEQUES' when docid = 2 then 'ADDRESS PROOF' when docid = 3 then 'CUSTOMER AADHAR CARD' when docid = 4 then 'GUARANTOR AADHAR CARD' when docid = 5 then 'INSURANCE' when docid = 6 then 'INCOME PROOF' when docid = 7 then 'ID PROOF' when docid = 8 then 'CUSTOMER SIGNATURE' when docid = 9 then 'AGREEMENT' when docid = 10 then 'INVOICE' when docid = 11 then 'DOWNPAYMENT RECEIPT' when docid = 12 then 'QUATATION' when docid = 13 then 'CUSTOMER PHOTO' when docid = 14 then 'GUARANTOR PHOTO' end) as docname,snote as doctype FROM ".$dbPrefix.".`tbmprpsldoc` WHERE proposalid='$proposalid' AND docsub='Y'";
			$docs = executeSelect($sql_docs);
			$proposal['result'][$i]['UploadedDocuments'] = $docs;
		}

		$Sql_brokers = "select b.brkrid,b.brkrnm from ".$dbPrefix.".tbmbroker b join ".$dbPrefix.".tbmsalesman s on b.centre = s.centre where b.brkrtyp = 1 and b.active = 2 and s.salesmanid = $salesmanid order by b.brkrnm";
		//$Sql_brokers = "SELECT sd.brkrid,b.brkrnm FROM ".$dbPrefix.".tbasalesmandealer sd JOIN ".$dbPrefix.".tbmbroker b ON sd.brkrid = //b.brkrid WHERE b.brkrtyp = 1 AND b.active = 2 AND sd.salesmanid = $salesmanid AND sd.active = 2 ORDER BY b.brkrnm";
		$brokers = executeSelect($Sql_brokers);

		foreach($brokers['result'] as $i=> $broker){
			$brkrid = $broker['brkrid'];

			$sql_pf_pfPrcnt = "SELECT BrkrId,SourceBnkId,`ProcessingFees`,`ProcessingFeesPrcnt` FROM ".$dbPrefix.".`tbabrokersrcbnk` WHERE brkrid = '$brkrid'";
			$pf_pfPrcnt = executeSelect($sql_pf_pfPrcnt);

			$brokers['result'][$i]['processingfee'] = $pf_pfPrcnt;
	 	}


		$Sql_banks = "select bankid,banknm from ".$dbPrefix.".tbmsourcebank where sourcebank = 1 and active = 2";
		$banks = executeSelect($Sql_banks);

		$Sql_makemodel = "select make,model from ".$dbPrefix.".tbmmakemodel where active = 2";
		$makemodel = executeSelect($Sql_makemodel);

		$Sql_documents = "select docid,docnm from ".$dbPrefix.".tbmdocument order by docnm";
		$documents = executeSelect($Sql_documents);

		$Sql_documenttypes = "SELECT `SystemDscrptn` as doctypenm,CASE WHEN Masterid = '201' THEN '2' WHEN Masterid = '202' THEN '6' WHEN Masterid = '263' THEN '7' END AS docid FROM ".$dbPrefix.".`tbmcodedscrptnmasterdata` WHERE Masterid = '201' OR Masterid = '202' OR Masterid = '263'";
		$documenttypes = executeSelect($Sql_documenttypes);

	}
	else{
		$response = error_code(1038);
		echo json_encode($response);
		return;
	}
	$response = array();
	$response["success"] = 1;

	$response["salesmanid"] = $salesmanid;
	$response["proposal"] = $proposal;
	$response["brokers"] = $brokers;
	$response["banks"] = $banks;
	$response["makemodel"] = $makemodel;
	$response["documents"] = $documents;
	$response["doctypes"] = $documenttypes;
	echo json_encode($response);

}

function postNewProposal($salesmanid,$brkrid,$bankid,$prslname){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$userdbPrefix = $_SESSION['USER_DB_PREFIX'];

		$sql_locktables = "LOCK TABLES ".$dbPrefix.".tbrcrrntid WRITE, ".$userdbPrefix.".tbmcrrntnumberglobal WRITE";
		$lockid = executeQuery($sql_locktables);

		$sql_prid = "select CrrntBaseId,CrrntId from ".$dbPrefix.".tbrcrrntid";
		$pr_id = executeSelect($sql_prid);
		$baseid = $pr_id['result'][0]['CrrntBaseId'];
		$curid = $pr_id['result'][0]['CrrntId'];
		$prid = ($baseid*100000000+$curid);

		$sql_prno = "select CrrntNumber from ".$userdbPrefix.".tbmcrrntnumberglobal where NumberSeriesId = '100000379'";
		$prno = str_pad(executeSingleSelect($sql_prno)+1, 6, "0", STR_PAD_LEFT);

		if(isset($prid) && isset($prno)){
			$sql_updateprid = "update ".$dbPrefix.".tbrcrrntid set crrntid = crrntid+1";
			$affectedrows1 = executeUpdate($sql_updateprid);
			$sql_updateprno = "update ".$userdbPrefix.".tbmcrrntnumberglobal set CrrntNumber = CrrntNumber+1 where NumberSeriesId = '100000379'";
			$affectedrows2 = executeUpdate($sql_updateprno);
		}
		$sql_unlocktables = "UNLOCK TABLES";
		$unlockid = executeQuery($sql_unlocktables);

        if($unlockid >0){
			$sql_insertproposal = "insert into ".$dbPrefix.".tbmproposal(ProposalId,ProposalNo,ProposalDt,BrkrId,BankId,ProposalNm,SalesmanId,ProposalStatus,EntryTyp) values($prid,'$prno',now(),$brkrid,$bankid,'$prslname','$salesmanid',1,1)";
        	$lastid = executeInsert($sql_insertproposal);

        	//echo $prid." ".$prno." ".$affectedrows1." ".$affectedrows2." ".$lastid;
		}
		if($lastid>0){
			$sql_proposalid_no = "select ProposalId,ProposalNo from ".$dbPrefix.".tbmproposal where pkid = '$lastid'";
			$proposalid_id_no = executeSelect($sql_proposalid_no);
			$propid = $proposalid_id_no['result'][0]['ProposalId'];

			$sql_docid = "SELECT docid FROM ".$dbPrefix.".`tbmdocument`";
			$docid = executeSelect($sql_docid);

			foreach($docid['result'] as $i=> $document){
				$documentid = $document['docid'];

				$sql_insertdoc = "INSERT INTO ".$dbPrefix.".`tbmprpsldoc`(docid,proposalid,docsub) VALUES ('$documentid','$propid','N')";
				$lastid_insertdoc = executeInsert($sql_insertdoc);
 			}
		}

		$response = array();
			if($proposalid_id_no['row_count']>0){
				$response["success"] = 1;
				$response["message"] = 'New Proposal Successfully Created';
				$response["proposalid"] = $proposalid_id_no['result'][0]['ProposalId'];
				$response["proposalno"] = $proposalid_id_no['result'][0]['ProposalNo'];
			}
			else{
				$response = error_code(1039);
			}
	echo json_encode($response);

}


function getAddress($pincode){
	$dbPrefix = $_SESSION['DB_PREFIX'];

	$sql_address = "select * from ".$dbPrefix.".tbmpincode where pincode = '$pincode'";
	$address = executeSelect($sql_address);

	$response = array();
	if($address['row_count']>0){
		$response["success"] = 1;
	}
	else{
		$response = error_code(1041);
		echo json_encode($response);
		return;
	}
	$response["address"] = $address;
	echo json_encode($response);

}


function sendOtp($salesmanid,$mobileno){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
	$otp = generateOtp();
	$msg = "Hello, Your one time password for mobile number verification is ".$otp." -LokSuvidha 9209058000";

	$sql_chkstatus = "SELECT pkid FROM ".$dbPrefix.".tbmotp WHERE mobile = '$mobileno' and VerifyStatus=1";
	$pkid = executeSingleSelect($sql_chkstatus);

	if($pkid>0){
		$response["success"] = 2;
		$response["message"] = 'Mobile number is already verified!';
		echo json_encode($response);
		return;
	}

	$sql_insert = "INSERT INTO ".$dbPrefix.".tbmotp(`Mobile`,`Otp`,`InsertDateTime`,`ValidityDateTime`,`InsertUserId`) VALUES('$mobileno','$otp',NOW(),DATE_ADD(NOW(),INTERVAL 20 MINUTE),'$salesmanid')";
	$lastid = executeInsert($sql_insert);

	$response = array();
	if($lastid>0){
		$sql_insertsms = "insert into ".$dbPrefix_curr.".tbxsms(CmpnyCd,MsgDtTm,Mobile,Message,MsgTag,MsgPriority,status,SentTo)
		values ('LKSA',now(),'$mobileno','$msg','OTP',0,0,3)";

		$lastid = executeInsert($sql_insertsms);
		if($lastid>0){
			$response["success"] = 1;
			$response["message"] = 'OTP send successfully!';
		}
		else{
			$response = error_code(1047);
			echo json_encode($response);
			return;
		}
	}
	else{
		$response = error_code(1047);
		echo json_encode($response);
		return;
	}

	echo json_encode($response);
}

function generateOtp() {
	$string = '';
	$length = 6;
	$chars = array();
	$chars = array_merge($chars,array(48,49,50,51,52,53,54,55,56,57));
  	for ($i=0;$i<$length;$i++){
  		shuffle($chars);
  		$string.=chr(reset($chars));
  	}
  	return $string;
}

function verifyOtp($mobileno,$otp){
	$dbPrefix = $_SESSION['DB_PREFIX'];

	$sql_otp = "SELECT pkid,VerifyStatus FROM ".$dbPrefix.".tbmotp WHERE mobile = '$mobileno' AND otp = '$otp' AND `ValidityDateTime` > NOW()";
	$otp = executeSelect($sql_otp);

	$pkid = 0;
	$status = 0;
	if($otp['row_count']>0){
		$pkid = $otp['result'][0]['pkid'];
		$status = $otp['result'][0]['VerifyStatus'];
	}

	if($pkid>0 AND $status==0){
		$sql_updatestatus = "update ".$dbPrefix.".tbmotp set VerifyStatus = 1 where pkid = '$pkid'";
		$affectedrows = executeUpdate($sql_updatestatus);

		if($affectedrows>0){
			$response["success"] = 1;
			$response["message"] = 'Mobile number successfully verified!';
		}
		else{
			$response = error_code(1048);
			echo json_encode($response);
			return;
		}
	}
	else if($pkid>0 AND $status==1){
			$response["success"] = 1;
			$response["message"] = 'Mobile number successfully verified!';
	}

	else{
		$response = error_code(1048);
		echo json_encode($response);
		return;
	}
	echo json_encode($response);
}



function uploadDocuments(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$path = $_SESSION['IMG_UPLOAD_PATH']."ProposalFiles/";

	//$path = "D:/inetpub/wwwroot/in.loksuvidha.com/content/ProposalFiles/";
	//$path = "D:/inetpub/wwwroot/dev.loksuvidha.local/content/ProposalFiles/";

	$response = array();
	if (isset($_FILES['image']['name'])) {
		$filename = isset($_POST['filename']) ? $_POST['filename'] : '';
		$proposalid = isset($_POST['proposalid']) ? $_POST['proposalid'] : '';
		$proposalno = isset($_POST['proposalno']) ? $_POST['proposalno'] : '';
		$docid = isset($_POST['docid']) ? $_POST['docid'] : '';
		$note = isset($_POST['doctypenm']) ? $_POST['doctypenm'] : '';
		$file_name = $filename;

		$subpath = floor($proposalno/1000);
		$newpath = $path."Images_".$subpath."/";
		if (!file_exists($newpath)) {
		    mkdir($newpath, 0777, true);
		}

    	$target_path = $newpath . $filename;

		if( file_exists($target_path) ) {
			$no = 1;
			while(file_exists($target_path)){
			   	$name = substr($filename, 0, -5);
				$no++;
				$file_name=$name.$no.".jpg";
			   	$target_path = $newpath.$file_name;
			}
		}

		try {
		 	 if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
			 	$response["success"] = 0;
		        $response["message"] = 'Could not upload the document!';
		  	 }
		  	 else{
				$sql_insert = "INSERT INTO ".$dbPrefix.".tbmprpsldocimages(`DocId`,`ProposalId`,`ImageName`) VALUES('$docid','$proposalid','$file_name')";
				$lastid = executeInsert($sql_insert);

				if($note == ''){
					$sql_update = "UPDATE ".$dbPrefix.".`tbmprpsldoc` SET DocSub = 'Y' WHERE `ProposalId` = '$proposalid' and DocId = '$docid'";
					$affectedrows = executeUpdate($sql_update);
				}
				else{
					$sql_update = "UPDATE ".$dbPrefix.".`tbmprpsldoc` SET `SNote` = IF(SNote IS NOT NULL, CONCAT(SNote,',$note'),'$note'),DocSub = 'Y' WHERE `ProposalId` = '$proposalid' and DocId = '$docid'";
					$affectedrows = executeUpdate($sql_update);
				}

				$sql_docs = "SELECT '$proposalid' as proposalid,docid,(case when docid = 1 then 'POST DATED CHEQUES' when docid = 2 then 'ADDRESS PROOF' when docid = 3 then 'CUSTOMER AADHAR CARD' when docid = 4 then 'GUARANTOR AADHAR CARD' when docid = 5 then 'INSURANCE' when docid = 6 then 'INCOME PROOF' when docid = 7 then 'ID PROOF' when docid = 8 then 'CUSTOMER SIGNATURE' when docid = 9 then 'AGREEMENT' when docid = 10 then 'INVOICE' when docid = 11 then 'DOWNPAYMENT RECEIPT' when docid = 12 then 'QUATATION' when docid = 13 then 'CUSTOMER PHOTO' when docid = 14 then 'GUARANTOR PHOTO' end) as docname,snote as doctype FROM ".$dbPrefix.".`tbmprpsldoc` WHERE proposalid='$proposalid' AND docsub='Y'";
				$docs = executeSelect($sql_docs);

				if($docs['row_count']>0){
					$response["success"] = 1;
		        	$response["message"] = 'Document successfully uploaded!';
		        	$response["UploadedDocuments"] = $docs;
				}
				else{
					$response["success"] = 0;
		        	$response["message"] = 'Failed to upload document!';
		        }

		   	 }
		}
		catch (Exception $e) {
		  	$response["success"] = 0;
		 	$response["message"] = $e->getMessage();
   		}
	}
	else {
	    $response["success"] = 0;
	    $response["message"] = 'Not received any file!';
	}
	echo json_encode($response);

}


function updateProposal(){
	$dbPrefix = $_SESSION['DB_PREFIX'];
	$request = Slim::getInstance()->request();

	$proposalId = $request->params('proposalId');
	$proposalNm = $request->params('proposalNm');
	$proposalStatusId = $request->params('proposalStatusId');
	$gender = $request->params('gender');
	$dob = $request->params('dob');
	$mobile = $request->params('mobile');
	$mobile2 = $request->params('mobile2');
	$tel1 = $request->params('tel1');

	$add1 = $request->params('add1');
	$area = $request->params('area');
	$city = $request->params('city');
	$tahasil = $request->params('tahasil');
	$state = $request->params('state');
	$pin = $request->params('pin');

	$profession = $request->params('profession');
	$annualIncome = $request->params('annualIncome');
	$aadharCardNo = $request->params('aadharCardNo');

	$guarantorName = $request->params('GuarantorName');
	$guarantorMobile1 = $request->params('GuarantorMobile1');
	$guarantorAadharNo = $request->params('GuarantorAadharNo');

	$preferredLanguage = $request->params('preferredLanguage');
	$ReferenceName = $request->params('ReferenceName');
	$ReferenceMobile = $request->params('ReferenceMobile');

	$bankId = $request->params('bankId');
	$costOfVhcl = $request->params('costOfVhcl');
	$downPayment = $request->params('downPayment');
	$roi = $request->params('roi');
	$grossTenure = $request->params('grossTenure');
	$advanceEMIPeriod = $request->params('advanceEMIPeriod');

	$docChrgFlag = $request->params('docChrgFlag');
	$ecsFlag = $request->params('ecsFlag');

	$brkrId = $request->params('brkrId');
	$vehMake = $request->params('vehMake');
	$vehModel = $request->params('vehModel');

	$sql_pfAmt_pfPrcnt = "SELECT `ProcessingFees`,`ProcessingFeesPrcnt` FROM ".$dbPrefix.".`tbabrokersrcbnk` WHERE brkrid = '$brkrId' AND `SourceBnkId` = '$bankId'";
	$pfAmt_pfPrcnt = executeSelect($sql_pfAmt_pfPrcnt);
	if($pfAmt_pfPrcnt['row_count']>0){
		$PF = $pfAmt_pfPrcnt['result'][0]['ProcessingFees'];
		$PFPrcnt = $pfAmt_pfPrcnt['result'][0]['ProcessingFeesPrcnt'];
	}
	else{
		$PF = 0;
		$PFPrcnt = 0;
	}

	if($ecsFlag == 1){
		$sql_ECSAmt = "SELECT `CodeDscrptnMasterDataId` FROM ".$dbPrefix.".`tbmcodedscrptnmasterdata` WHERE `MasterId` = '265'";
		$ECSAmt = executeSingleSelect($sql_ECSAmt);

		//$ECSAmt = 350;
	}
	else{
		$ECSAmt = 0;
	}

	$sql_bankroi = "SELECT BankRoi FROM ".$dbPrefix.".`tbmsourcebank` WHERE BankId = '$bankId'";
	$bankroi = executeSingleSelect($sql_bankroi);

	//$bankroi = 12.50;

	$serviceTaxRate = 0;

	$marginMoney = $downPayment;
	$finAmt = CalcFinAmt($costOfVhcl, $downPayment);
	$period = CalcPeriod($grossTenure, $advanceEMIPeriod);
	$totInterest = CalcTotInterest($finAmt, $roi, $grossTenure);
	$instAmt = CalcInstallment($finAmt,$totInterest,$grossTenure);
	$extraCharges = CalcExtraChrgs($finAmt, $roi, $grossTenure);
	$srvTaxAmt = CalcSrvTax($finAmt, $roi, $grossTenure, $extraCharges, $serviceTaxRate);
	$totAmt = CalcTotAmount($finAmt, $roi, $grossTenure, $extraCharges, $serviceTaxRate);
	$emi = CalcEMI($totAmt, $grossTenure);
	$advncEMIAmt = CalcAdvncEMIAmt($advanceEMIPeriod, $emi);
	$totDueAmt = $totAmt - $advncEMIAmt;
	$DocChrg = CalcDocumentChrg($finAmt,$docChrgFlag);
	$PFAmt = CalcPFAmt($finAmt,$PF,$PFPrcnt);
	$disbursementAmt = CalcDisbursementAmt($finAmt,$PFAmt,$DocChrg,$ECSAmt,$advncEMIAmt);
	$bankEmi = CalcBankEMI($disbursementAmt, $period, $bankroi);
	$collectionChrgs = CalcCollectionChrgs($emi, $bankEmi, $period);

	$affectedrows = 0;
	$affectedrows_grtr = 0;
	$lastid_grtr = 0;
	$affectedrows_financedtl = 0;
	$lastid_financedtl = 0;

	$sql_locktable = "LOCK TABLES ".$dbPrefix.".tbrcrrntid WRITE";
	$lockid = executeQuery($sql_locktable);

	$sql_grid = "select CrrntId from ".$dbPrefix.".tbrcrrntid";
	$grid = executeSingleSelect($sql_grid);

	if(isset($grid)){
		$sql_updateCrrntId = "update ".$dbPrefix.".tbrcrrntid set crrntid = crrntid+1";
		$affectedrows_CrrntId = executeUpdate($sql_updateCrrntId);
	}
	$sql_unlocktables = "UNLOCK TABLES";
	$unlockid = executeQuery($sql_unlocktables);
	if($unlockid >0){
		$grtrId = $grid;
		$srNo = 1;
	}

	$sql_updateProposal = "update ".$dbPrefix.".tbmproposal set ProposalNm ='$proposalNm', Gender='$gender', DOB='$dob', Mobile='$mobile', Mobile2='$mobile2', Tel1='$tel1', Add1='$add1', Area='$area', City='$city', Tahasil='$tahasil', State='$state', Pin='$pin', Profession='$profession', AnnualIncome='$annualIncome', AadharCardNo='$aadharCardNo', BankId='$bankId', CostOfVhcl='$costOfVhcl', RoI='$roi', BrkrId='$brkrId', VehMake='$vehMake', VehModel='$vehModel', ProposalStatus='1', RefNm1='$ReferenceName', RefMob1='$ReferenceMobile', Language='$preferredLanguage', FinanceAmt='$finAmt', TotDueAmt='$totDueAmt', ExtraCharges='$extraCharges', CollectionChrgs='$collectionChrgs', Period='$period', MarginMoney='$marginMoney', PFAmt='$PFAmt' where ProposalId = '$proposalId'";
	$affectedrows_proposal = executeUpdate($sql_updateProposal);

	$sql_chkprid_grtr = "SELECT pkid FROM ".$dbPrefix.".tbmprpslguarantors WHERE proposalid = '$proposalId'";
	$prid_grtr = executeSingleSelect($sql_chkprid_grtr);
	if($prid_grtr>0){
		$sql_updategrtr = "UPDATE ".$dbPrefix.".tbmprpslguarantors SET `GrtrNm`='$guarantorName',`Mobile`='$guarantorMobile1',`AadharCardNo`='$guarantorAadharNo' WHERE ProposalId = '$proposalId'";
		$affectedrows_grtr = executeUpdate($sql_updategrtr);
	}
	else{
		$sql_insertgrtr = "INSERT INTO ".$dbPrefix.".tbmprpslguarantors(`SrNo`,`GrtrId`,`ProposalId`,`GrtrNm`,`Mobile`,`AadharCardNo`) VALUES('$srNo','$grtrId','$proposalId','$guarantorName','$guarantorMobile1','$guarantorAadharNo')";
		$lastid_grtr = executeInsert($sql_insertgrtr);
	}

	$sql_chkprid_financedtl = "SELECT ProposalId FROM ".$dbPrefix.".tbaproposalfnncdtls WHERE ProposalId = '$proposalId'";
	$prid_financedtl = executeSingleSelect($sql_chkprid_financedtl);
	if($prid_financedtl>0){
		$sql_updatefinancedtl = "UPDATE ".$dbPrefix.".tbaproposalfnncdtls SET `FinanceAmt`='$finAmt',`RoI`='$roi',`BankRoI`='$bankroi',`SrvTaxRate`='$serviceTaxRate',`TotDueAmt`='$totDueAmt',`ExtraCharges`='$extraCharges',`CollectionChrgs`='$collectionChrgs',`Period`='$period',`CostOfVhcl`='$costOfVhcl',`MarginMoney`='$marginMoney',`PFAmt`='$PFAmt',`OnRoadPrice`='$costOfVhcl',`DownPayment`='$downPayment',`DocumentChrgAmt`='$DocChrg',`DisbursementAmt`='$disbursementAmt',`BankEMI`='$bankEmi',`AdvanceEMI`='$advncEMIAmt',`GrossPeriod`='$grossTenure',`AdvancePeriod`='$advanceEMIPeriod',`ECSAmount`='$ECSAmt',`ECSFlag`='$ecsFlag',`DocumentChrgFlag`='$docChrgFlag' WHERE ProposalId = '$proposalId'";
		$affectedrows_financedtl = executeUpdate($sql_updatefinancedtl);
	}
	else{
		$sql_insertfinancedtl = "INSERT INTO ".$dbPrefix.".tbaproposalfnncdtls(`ProposalId`,`FinanceAmt`,`RoI`,`BankRoI`,`SrvTaxRate`,`TotDueAmt`,`ExtraCharges`,`CollectionChrgs`,`Period`,`CostOfVhcl`,`MarginMoney`,`PFAmt`,`OnRoadPrice`,`DownPayment`,`DocumentChrgAmt`,`DisbursementAmt`,`BankEMI`,`AdvanceEMI`,`GrossPeriod`,`AdvancePeriod`,`ECSAmount`,`ECSFlag`,`DocumentChrgFlag`)VALUES('$proposalId','$finAmt','$roi','$bankroi','$serviceTaxRate','$totDueAmt','$extraCharges','$collectionChrgs','$period','$costOfVhcl','$marginMoney','$PFAmt','$costOfVhcl','$downPayment','$DocChrg','$disbursementAmt','$bankEmi','$advncEMIAmt','$grossTenure','$advanceEMIPeriod','$ECSAmt','$ecsFlag','$docChrgFlag')";
		$lastid_financedtl = executeInsertQuery($sql_insertfinancedtl);
	}


	$response = array();
		if($affectedrows_proposal>0 OR $affectedrows_grtr>0 OR $lastid_grtr>0 OR $affectedrows_financedtl>0 OR $lastid_financedtl>0){
			if($proposalStatusId == 3){
				$sql_updateProposalStatus = "update ".$dbPrefix.".tbmproposal set ProposalStatus='2' where ProposalId = '$proposalId'";
				$affectedrows_proposalstatus = executeUpdate($sql_updateProposalStatus);
			}
			$response["success"] = 1;
			$response["message"] = 'Proposal Successfuly Saved';
		}
		else{
			$response = error_code(1040);
		}
	echo json_encode($response);

}





function CalcFinAmt($OnRoadPrice, $DownPaymnt){
	$FinAmt = 0;
  	$FinAmt = $OnRoadPrice - $DownPaymnt;
   	return $FinAmt;
}

function CalcPeriod($GTenure, $AEmiPeroid){
 	$Period = 0;
 	$Period = $GTenure - $AEmiPeroid;
 	return $Period;
}

function CalcTotInterest($FinAmt, $roi, $GTenure){
   	$IntAmt  = 0;
    $IntAmt = ($FinAmt * $roi * $GTenure) / (100 * 12);
    return $IntAmt;
}

function CalcInstallment($FinAmt, $InterestAmt, $GTenure){
  	$InstAmt = 0;
  	$InstAmt = ($FinAmt + $InterestAmt) / $GTenure;
 	//return ceil($InstAmt);
 	return $InstAmt;
}

function CalcExtraChrgs($FinAmt, $roi, $GTenure){
  	$IntAmt = 0;
    $InstAmt = 0;
    $RndInstAmt = 0;
    $ExtraChrgs = 0;

    $IntAmt = CalcTotInterest($FinAmt, $roi, $GTenure);      				//Calculating total interest

    if($GTenure>0){
       $InstAmt = ($FinAmt + $IntAmt) / $GTenure;           				//Calculating installment amount
	}
    $RndInstAmt = ceil($InstAmt);                							//Rounding off

    $InstAmt = $InstAmt - floor($InstAmt);

    if ($InstAmt > 0){
    	$ExtraChrgs = (($RndInstAmt * $GTenure) - ($FinAmt + $IntAmt));   	//Calculating extra charges
    }
    else{
        $ExtraChrgs = 0;
  	}
	return $ExtraChrgs;

}

function CalcSrvTax($FinAmt, $roi, $GTenure, $ExtraChrgs, $SrvTaxRate){
  	$SrvTaxAmt = 0;
  	$IntAmt = 0;

  	$IntAmt = CalcTotInterest($FinAmt, $roi, $GTenure);
   	$SrvTaxAmt = (($IntAmt + $ExtraChrgs) * $SrvTaxRate) / 100;

    return $SrvTaxAmt;
}

function CalcTotAmount($FinAmt, $roi, $GTenure, $ExtraChrgs, $SrvTaxRate){
   	$TotAmt = 0;
    $SrvTaxAmt = 0;
    $IntAmt = 0;

 	$IntAmt = CalcTotInterest($FinAmt, $roi, $GTenure);          					//Calculation of total interest
    $SrvTaxAmt = CalcSrvTax($FinAmt, $roi, $GTenure, $ExtraChrgs, $SrvTaxRate);     //Calculating service tax amount

    $TotAmt = $FinAmt + $IntAmt + $SrvTaxAmt + $ExtraChrgs;    						//Calculating total amount

  	return $TotAmt;
}

function CalcEMI($TotAmt, $GTenure){
  	$EMI = 0;
	$EMI = floor($TotAmt / $GTenure);

    return $EMI;
}

function CalcAdvncEMIAmt($AEmiPeroid, $EMI){
 	$AEmiAmt = 0;
   	$AEmiAmt = $EMI * $AEmiPeroid;
    return $AEmiAmt;
}

function CalcDocumentChrg($FinAmt,$DocChrgFlag){
 	$DocChrg = 0;

 	if ($DocChrgFlag == 1){
    	$DocChrg = ($FinAmt * (0.5)) / 100;
    }
    else{
       $DocChrg = 0;
   	}
	return $DocChrg;
}

function CalcPFAmt($FinAmt,$PF,$PFPrcnt){
	$PFAmt = 0;
	$TolFee = 0;
	if ($PFPrcnt > 0){
		$TolFee = ($FinAmt * $PFPrcnt) / 100;
	}
	$PFAmt = $TolFee + $PF;
	return Round($PFAmt);
}

function CalcDisbursementAmt($FinanceAmt,$PFAmt,$DocumentChrg,$ECSAmount,$AdvaceEMI){
   	$DisbursementAmt = 0;
	$DisbursementAmt = $FinanceAmt - ($PFAmt + $DocumentChrg + $ECSAmount + $AdvaceEMI);
    return $DisbursementAmt;
}

function CalcBankEMI($p, $n, $r){
	$BankEMI = 0;
 	$lamount = $p;
 	$period = $n;
 	$roi = $r;

	$mi = $roi/100;  // Monthly interest %ge
	$ny = $period;   // No of months
	$mic = $mi /12;  // Monthly interest

	$top = pow((1+$mic),$ny);
	$bottom = $top - 1;
	$sp = $top / $bottom;

	//$BankEMI = floor((($lamount * $mic) * $sp));
	$BankEMI = round((($lamount * $mic) * $sp));

 	return $BankEMI;
}

function CalcCollectionChrgs($EMI, $BankEMI, $Period){
	$CollChrgs = 0;
   	$CollChrgs = ($EMI - $BankEMI) * $Period;
   	return $CollChrgs;
}

?>