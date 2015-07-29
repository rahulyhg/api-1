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

	$sql = "select sql_calc_found_rows fr.dealid, fr.dealid, fr.dealno, tcase(fr.dealnm) as deal_name,tcase(d.centre) as centre, tcase(fr.area) as area, tcase(fr.city) as city, tcase(fr.address) as address, fr.mobile, fr.dueamt as due_amt, fr.dd as assigned_on, null as followup_dt, fr.rgid as bucket, tcase(dg.GrtrNm) as guarantor_name, tcase(concat(dg.add1, ' ', dg.add2, ' ', dg.area, ' ', dg.tahasil, ' ', dg.city)) as guarantor_address, fr.GuarantorMobile as guarantor_mobile,round(d.financeamt) as finance_amt,fr.emi,d.period as tenure,DATE_FORMAT(d.hpexpdt, '%d-%m-%Y') as expiry_dt, DATE_FORMAT(d.startduedt, '%d')as emi_day,null as type, concat(dv.make, ' ', dv.model) as vehicle_model, dv.VhclColour as vehicle_color, dv.Chasis as vehicle_chasis_no, dv.EngineNo as vehicle_engine_no, dv.RTORegNo as rto_reg_no, b.BrkrNm as dealer, concat(b.add1, ' ', b.add2, ' ', b.area, ' ', b.tahasil, ' ', b.city) as showroom, fr.SalesmanId as salesman_id, fr.callerid as caller_empid, fr.sraid as sra_empid
	FROM ".$dbPrefix_curr.".tbxfieldrcvry fr
	join ".$dbPrefix.".tbmdealguarantors dg
	join ".$dbPrefix.".tbmdeal d
	join ".$dbPrefix.".tbmdealvehicle dv
	join ".$dbPrefix.".tbmbroker b
	on fr.dealid = dg.dealid and fr.dealid = d.dealid and fr.dealid=dv.dealid and d.brkrid = b.brkrid where fr.mm = 6 and fr.sraid = 137
	ORDER BY fr.dd desc limit 0, 20";

	//$sql = "SELECT * FROM ".$dbPrefix.".tbmdealchrgs WHERE DealId=100248396 AND DcTyp NOT IN (101,102,111) AND ChrgsApplied > ChrgsRcvd GROUP BY Dctyp";
	$deals = executeSelect($sql);
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
/*
function getDashboard($id, $month) {
	$sql = "SELECT t1.centre,  t1.sraid as emp_id, t1.sranm as emp_name, t1.sraactive as emp_active, '09972722800' as emp_phone, 'http://r.loksuvidha.com:81/pics/photo.jpg' as emp_pic,
SUM(a1) AS a1, SUM(r1) AS r1, SUM(b1) AS b1, SUM(a2) AS a2, SUM(r2) AS r2, SUM(b2) AS b2, SUM(a3) AS a3, SUM(r3) AS r3, SUM(b3) AS b3, SUM(a4) AS a4, SUM(r4) AS r4, SUM(b4) AS b4, SUM(a5) AS a5, SUM(r5) AS r5, SUM(b5) AS b5, SUM(a6) AS a6, SUM(r6) AS r6, SUM(b6) AS b6,
SUM(assigned_fd) AS assigned_fd,
SUM(recovered_fd) AS recovered_fd,
SUM(assigned_dm) AS assigned_dm,
SUM(recovered_dm) AS recovered_dm,
COUNT(dealid) AS tot_due,
SUM(recovered) AS tot_recovered
FROM (
SELECT d.inserttimestamp, d.dealid, d.OdDueAmt, d.dd, t.dealid AS rdid, t.rcptamt, d.OdDueAmt - t.rcptamt AS balance,
CASE WHEN d.dd = 1 THEN 1 ELSE 0 END AS assigned_fd,
CASE WHEN d.dd = 1 AND t.dealid IS NOT NULL THEN 1 ELSE 0 END AS recovered_fd,
CASE WHEN d.dd != 1 THEN 1 ELSE 0 END AS assigned_dm,
CASE WHEN d.dd != 1 AND t.dealid IS NOT NULL THEN 1 ELSE 0 END AS recovered_dm,  CASE WHEN d.rgid = 1 THEN 1 ELSE 0 END AS a1 , CASE WHEN t.dealid IS Not NULL AND d.rgid = 1 THEN 1 ELSE 0 END AS r1 , CASE WHEN t.dealid IS NULL AND d.rgid = 1 THEN 1 ELSE 0 END AS b1 , CASE WHEN d.rgid = 2 THEN 1 ELSE 0 END AS a2 , CASE WHEN t.dealid IS Not NULL AND d.rgid = 2 THEN 1 ELSE 0 END AS r2 , CASE WHEN t.dealid IS NULL AND d.rgid = 2 THEN 1 ELSE 0 END AS b2 , CASE WHEN d.rgid = 3 THEN 1 ELSE 0 END AS a3 , CASE WHEN t.dealid IS Not NULL AND d.rgid = 3 THEN 1 ELSE 0 END AS r3 , CASE WHEN t.dealid IS NULL AND d.rgid = 3 THEN 1 ELSE 0 END AS b3 , CASE WHEN d.rgid = 4 THEN 1 ELSE 0 END AS a4 , CASE WHEN t.dealid IS Not NULL AND d.rgid = 4 THEN 1 ELSE 0 END AS r4 , CASE WHEN t.dealid IS NULL AND d.rgid = 4 THEN 1 ELSE 0 END AS b4 , CASE WHEN d.rgid = 5 THEN 1 ELSE 0 END AS a5 , CASE WHEN t.dealid IS Not NULL AND d.rgid = 5 THEN 1 ELSE 0 END AS r5 , CASE WHEN t.dealid IS NULL AND d.rgid = 5 THEN 1 ELSE 0 END AS b5 , CASE WHEN d.rgid >= 6 THEN 1 ELSE 0 END AS a6 , CASE WHEN t.dealid IS Not NULL AND d.rgid >= 6 THEN 1 ELSE 0 END AS r6 ,	CASE WHEN t.dealid IS NULL AND d.rgid >= 6 THEN 1 ELSE 0 END AS b6,  CASE WHEN t.dealid IS NOT NULL THEN 1 ELSE 0 END AS recovered,
d.sraid, b.brkrnm as sranm, b.active as sraactive, b.centre
FROM lksa201516.tbxfieldrcvry d
LEFT JOIN lksa.tbmbroker b on d.sraid = b.brkrid and b.brkrtyp = 2
LEFT JOIN (
	SELECT r.dealid, SUM(rd.rcptamt) AS rcptamt FROM lksa201516.tbxdealrcpt r JOIN lksa201516.tbxdealrcptdtl rd ON r.rcptid = rd.rcptid
		WHERE r.cclflg = 0 AND r.CBflg = 0 AND (rd.dctyp = 101 OR rd.dctyp = 111) and r.rcptpaymode = 1
		AND r.rcptdt between '".date('Y-m-01')."' and '".date('Y-m-t')."'
		GROUP BY r.dealid having rcptamt >= 450
) AS t ON d.dealid = t.dealid WHERE d.mm = $month  and d.sraid = '$id' ) t1 GROUP BY  t1.centre, t1.sraid  having t1.sraid = '$id'";

	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("id", $id);
		$stmt->bindParam("month", $month);
		$stmt->execute();
		$deal = $stmt->fetchObject();
		if($deal){
			$deal -> unassigned ="0";
			$deal -> td_fup ="14";
			$deal -> td_fixed ="4";
			$deal -> td_recovered ="3";
			$deal -> td_collection ="10124";
			$deal -> mnth_collection ="323451";
		}
		$db = null;
		echo json_encode($deal);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
		error_log("From API Index.php: ".$e->getMessage());
	}
}

*/
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
?>