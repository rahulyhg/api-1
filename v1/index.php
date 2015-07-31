<?php
session_cache_limiter(false);
session_start();
require 'functions.php';

$app = new Slim();

$app->get('/register/imei', 'register');
$app->get('/staticdata', 'getStaticData');
$app->get('/deals/:page', 'getDeals');
$app->get('/dashboards', 'getDashboards');
$app->get('/dashboard/:id/:month', 'getDashboard');

$app->run();

function register($imei){
}

function getStaticData(){
}

function getDeals($page) {
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
	$limit = $_SESSION['API_ROW_LIMIT'];
	$start = ($page-1) * $limit;

	$sraid = 137;

	$sql = "select sql_calc_found_rows fr.dealid, fr.dealid, fr.dealno, tcase(fr.dealnm) as name,tcase(d.centre) as centre, tcase(fr.area) as area, tcase(fr.city) as city, tcase(fr.address) as address, fr.mobile, DATE_FORMAT(fr.hpdt, '%d-%m-%Y') as hpdt, fr.dueamt as total_due,fr.OdDueAmt as overdue, fr.dd as assigned_on, DATE_FORMAT(fr.CallerFollowupDt,'%d-%m-%Y') as caller_followup_dt,DATE_FORMAT(fr.SRAFollowupDt,'%d-%m-%Y') as sra_followup_dt, fr.rgid as bucket,round(d.financeamt) as finance_amt,fr.emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d') as emi_day, (case when d.paytype=1 then 'PDC' when d.paytype=2 then 'ECS' when d.paytype=3 then 'Direct Debit' end) as type, tcase(concat(dv.make, ' ', dv.model)) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as rto_reg_no, tcase(b.BrkrNm) as dealer, tcase(trim(concat(ifnull(b.city,''), ' ', case when b.centre != b.city then b.centre else '' end))) as showroom, fr.SalesmanId as salesman_id
	FROM ".$dbPrefix_curr.".tbxfieldrcvry fr
	join ".$dbPrefix.".tbmdeal d
	join ".$dbPrefix.".tbmdealvehicle dv
	join ".$dbPrefix.".tbmbroker b
	on fr.dealid = d.dealid and fr.dealid=dv.dealid and d.brkrid = b.brkrid where fr.mm = ".date('n')." and fr.sraid = $sraid
	ORDER BY fr.dd desc limit $start, $limit";

	$deals = executeSelect($sql);

	foreach($deals['result'] as $i=> $deal){
		$dealid = $deal['dealid'];

		$q1 = "SELECT DueDt as Date, round(DueAmt) as Due, round(CollectionChrgs) as CC, round(DueAmt+CollectionChrgs) as Total, (case WHEN Duedt <= curdate() THEN 1 ELSE 0 END) as eligible  FROM ".$dbPrefix.".tbmduelist where dealid = $dealid order by Year(DueDt), Month(DueDt)";

		$q2 = "";
		$hp_mm = date("n", strtotime($deal['hpdt']));
		$hp_yy= date("Y", strtotime($deal['hpdt']));
		$startyy = ($hp_mm < 4 ? ($hp_yy-1) : $hp_yy);

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

	    $sql_guarantor = "select tcase(GrtrNm) as guarantor_name, tcase(concat(add1, ' ', add2, ' ', area, ' ', tahasil, ' ', city)) as guarantor_address, mobile as guarantor_mobile from ".$dbPrefix.".tbmdealguarantors where DealId=$dealid";
		$guarantor = executeSelect($sql_guarantor);

		$sql_assignment = "select mm as month,CallerId as caller_id,SRAId as sra_id from ".$dbPrefix_curr.".tbxfieldrcvry where DealId=$dealid";
		$assignment = executeSelect($sql_assignment);

		$sql_otherphone = "select mobile, mobile2 from ".$dbPrefix.".tbmdeal where DealId=$dealid";
		$otherphone = executeSelect($sql_otherphone);

		//print_a($otherphone);
		//die();

         $found_rows=0;
		 $ph = array();

		 if($otherphone['row_count'] > 0){
			$index=0;
			if(isset($otherphone['result'][0]['mobile'])){
				$ph[$index]= array();
				$ph[$index]["name"]='Self';
				$ph[$index]["relation"]='Self';
				$ph[$index]["number"]=$otherphone['result'][0]['mobile'];
				$index++;
				$found_rows++;
			}
			if(isset($otherphone['result'][0]['mobile2'])){
				$ph[$index]= array();
				$ph[$index]["name"]='Self';
				$ph[$index]["relation"]='Self';
				$ph[$index]["number"]=$otherphone['result'][0]['mobile2'];

			}
		}
         $ph["row_count"]=$otherphone['row_count'];
		 $ph["found_rows"]=$found_rows;




        $sql_logs = "SELECT t.dt, t.type,u.realname AS caller, b.brkrnm AS sranm, date_format(t.followupdt,'%d-%b') as followupdt, t.remark FROM(
        SELECT dealid, followupdate AS dt,'FIRSTCALL' AS `type`,  NULL AS callerid, Remark AS remark, NULL AS followupdt, NULL AS sraid FROM $dbPrefix_curr.tbxdealduedatefollowuplog WHERE dealid = $dealid
        UNION
        SELECT dealid, followupdate AS dt, 'CALLER' AS `type`, webuserid AS callerid, FollowupRemark AS remark, NxtFollowupDate AS followupdt, NULL AS sraid FROM $dbPrefix_curr.tbxdealfollowuplog WHERE dealid = $dealid
		UNION
		SELECT dealid, followupdate AS dt, 'INTERNAL' AS `type`,  webuserid AS callerid, FollowupRemark AS remark, NULL AS followupdt, sraid FROM $dbPrefix_curr.tbxsrafollowuplog WHERE dealid = $dealid
		) t
		LEFT JOIN ob_sa.tbmuser u ON t.callerid = u.userid
		LEFT JOIN lksa.tbmbroker b ON t.sraid = b.brkrid AND b.brkrtyp = 2
		ORDER BY dt DESC";
        $logs = executeSelect($sql_logs);

        $sql_dealcharges = "SELECT dctyp as dctype,(ChrgsApplied-ChrgsRcvd) as amount FROM ".$dbPrefix.".tbmdealchrgs WHERE DealId=$dealid AND DcTyp NOT IN (101,102,111) AND ChrgsApplied > ChrgsRcvd GROUP BY Dctyp";

        $dealcharges = executeSelect($sql_dealcharges);

        $sql_bounce = "select concat(count(case when status=-1 then 1 end),'/',count(depositdt)) as bounced from ".$dbPrefix.".tbmpaytypedtl where active=2 and depositdt IS NOT NULL and dealid = $dealid";
        $bounce = executeSelect($sql_bounce);

        $sql_seized = "select count(dealid)as seized from ".$dbPrefix_curr.".tbxvhclsz where dealid=$dealid";
        $seized = executeSelect($sql_seized);

   		$deals['result'][$i]['guarantor'] = $guarantor;
		$deals['result'][$i]['dealcharges'] = $dealcharges;
    	$deals['result'][$i]['bounce'] = $bounce;
		$deals['result'][$i]['seized'] = $seized;
		$deals['result'][$i]['phonenumbers'] = $ph;
		$deals['result'][$i]['assignment'] = $assignment;
		$deals['result'][$i]['ledger'] = format_ledger($ledger);
		$deals['result'][$i]['logs'] = $logs;
	}

	echo '{"deals": ' . json_encode($deals) . '}';
}


function getDashboards(){
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
//    $sraid = $_SESSION['userid']; this is a comment - Another comment
	$sraid = 137;

	$sql = "SELECT * FROM (
	SELECT yy, mm, sraid as empid, sranm as empname, collection, od, penalty, bouncing, others, assigned_fd, recovered_fd, assigned_dm, recovered_dm, assigned, recovered, assigned_b1, recovered_b1, assigned_b2, recovered_b2, assigned_b3, recovered_b3, assigned_b4, recovered_b4, assigned_b5, recovered_b5, assigned_b6, recovered_b6, target_fd FROM ".$dbPrefix_curr.".tbxdashboard WHERE sraid = $sraid
	UNION
	SELECT yy, mm, sraid as empid, sranm as empname, collection, od, penalty, bouncing, others, assigned_fd, recovered_fd, assigned_dm, recovered_dm, assigned, recovered, assigned_b1, recovered_b1, assigned_b2, recovered_b2, assigned_b3, recovered_b3, assigned_b4, recovered_b4, assigned_b5, recovered_b5, assigned_b6, recovered_b6, target_fd FROM ".$dbPrefix_last.".tbxdashboard WHERE sraid = $sraid
	)t1 ORDER BY yy DESC, mm DESC LIMIT 0, 5";

	$dashboard = executeSelect($sql);
	echo '{"dashboards": ' . json_encode($dashboard) . '}';
}

function getEmployeeIMEI($id){
/*	$sql = "select * FROM lksa201516.tbxfieldrcvry where mm = 6 and dealid = :id";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("id", $id);
		$stmt->execute();
		$deal = $stmt->fetchObject();
		$db = null;
		echo json_encode($deal);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
		error_log("From API Index.php: ".$e->getMessage());
	}
*/
}
function getDeal($id) {
	$sql = "select dealid, dealno, dealnm, hpdt, dd as assigned_on, rgid as bucket, EMI, DueAmt, city, area, centre FROM lksa201516.tbxfieldrcvry where mm = 6 and dealid = :id";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("id", $id);
		$stmt->execute();
		$deal = $stmt->fetchObject();
		$db = null;
		echo json_encode($deal);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
		error_log("From API Index.php: ".$e->getMessage());
	}
}


function format_ledger($l){
	$removeWords = array("BEING INSTALLMENT AMOUNT RECEIVED BY","BEING CASH DEPOSITED", "BEING CHQ. CLEARED IN", "BEING AMT RCVD BY ECS", "BEING AMT RCVD", "BY ECS", "BEING AMT RECD BY");

	$ledger = array();
	$i =-1;	$p = 0; $balance = 0; $rowStarted = 0;
	$EMI_CANCELLED = -1; $EMI_PENDING = 0; $EMI_CLEARED = 1; $EMI_BOUNCED = 2;

//	print_a($l);
	foreach ($l['result'] as $row){
//		print_a($row);
		if($row['source'] == 1){ // This is a row for DUE EMI from Duelist so start a new row for this
			$balance += $row['DueAmt'];
			$rowStarted = 1;
			$ledger[++$i] = array('sn' => NULL, 'dt' => NULL, 'dueamt' => NULL, 'rEMI' => NULL, 'balance' => NULL, 'status'=> NULL, 'remarks' => NULL, 'sranm' => NULL, 'mode'=>NULL);
			$ledger[$i]['sn'] = ++$p; $ledger[$i]['dt'] = $row['dDate']; $ledger[$i]['dueamt'] = $row['DueAmt'];
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
				$ledger[++$i] = array('sn' => NULL, 'dt' => NULL, 'dueamt' => NULL, 'rEMI' => NULL, 'balance' => NULL, 'status'=> NULL, 'remarks' => NULL, 'sranm' => NULL, 'mode'=>NULL);
				$rowStarted = 1;
			}
			if($row['CBFlg']== 0 && $row['CCLflg']==0){
				$balance -= $row['rEMI'];
			}
			$ledger[$i]['dt'] = $row['rDate'];
			$ledger[$i]['rEMI'] = $row['rEMI'];
			$ledger[$i]['status'] = ($row['CBFlg']==-1 ? $EMI_BOUNCED : ($row['CCLflg']== -1 ? $EMI_CANCELLED : (isset($row['reconind']) && $row['reconind'] == $EMI_PENDING ? 0 : $EMI_CLEARED)));
			$ledger[$i]['remarks'] = $row['Remarks'];
			$ledger[$i]['sranm'] = $row['sranm'];
			$ledger[$i]['mode'] = $row['mode'];
			$rowStarted = 0;
		}
		$ledger[$i]['balance'] = nf($balance, true);
	}
//	print_a($ledger);
	return $ledger;

}
?>