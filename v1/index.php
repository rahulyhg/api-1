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

/*
$app->get('/employee/imei/:id', 'getEmployeeIMEI');
$app->get('/deal/:id', 'getDeal');
$app->get('/wines', 'getWines');
$app->get('/wines/:id',	'getWine');
$app->get('/wines/search/:query', 'findByName');
$app->post('/wines', 'addWine');
$app->put('/wines/:id', 'updateWine');
$app->delete('/wines/:id',	'deleteWine');
*/
$app->run();

function register($imei){

}

function getStaticData(){

}

function getDeals($page) {
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];

	$sql = "select sql_calc_found_rows dealid, dealid, dealno, dealnm, dd as assigned_on, rgid as bucket, emi, dueamt FROM ".$dbPrefix_curr.".tbxfieldrcvry where mm = 6 and sraid = 137 ORDER BY dd desc limit 0,20";

	$deals = executeSelect($sql);
	echo '{"deals": ' . json_encode($deals) . '}';
}

function getDashboards(){
    $dbPrefix = $_SESSION['DB_PREFIX'];
    $dbPrefix_curr = $_SESSION['DB_PREFIX_CURR'];
    $dbPrefix_last = $_SESSION['DB_PREFIX_LAST'];
//    $sraid = $_SESSION['userid'];
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

function getWines() {
	$sql = "select * FROM wine ORDER BY name";
	try {
		$db = getConnection();
		$stmt = $db->query($sql);
		$wines = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"wine": ' . json_encode($wines) . '}';
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
		error_log("From API Index.php: ".$e->getMessage());
	}
}

function getWine($id) {
	$sql = "SELECT * FROM wine WHERE id=:id";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("id", $id);
		$stmt->execute();
		$wine = $stmt->fetchObject();
		$db = null;
		echo json_encode($wine);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function addWine() {
	error_log('addWine\n', 3, '/var/tmp/php.log');
	$request = Slim::getInstance()->request();
	$wine = json_decode($request->getBody());
	$sql = "INSERT INTO wine (name, grapes, country, region, year, description) VALUES (:name, :grapes, :country, :region, :year, :description)";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("name", $wine->name);
		$stmt->bindParam("grapes", $wine->grapes);
		$stmt->bindParam("country", $wine->country);
		$stmt->bindParam("region", $wine->region);
		$stmt->bindParam("year", $wine->year);
		$stmt->bindParam("description", $wine->description);
		$stmt->execute();
		$wine->id = $db->lastInsertId();
		$db = null;
		echo json_encode($wine);
	} catch(PDOException $e) {
		error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function updateWine($id) {
	$request = Slim::getInstance()->request();
	$body = $request->getBody();
	$wine = json_decode($body);
	$sql = "UPDATE wine SET name=:name, grapes=:grapes, country=:country, region=:region, year=:year, description=:description WHERE id=:id";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("name", $wine->name);
		$stmt->bindParam("grapes", $wine->grapes);
		$stmt->bindParam("country", $wine->country);
		$stmt->bindParam("region", $wine->region);
		$stmt->bindParam("year", $wine->year);
		$stmt->bindParam("description", $wine->description);
		$stmt->bindParam("id", $id);
		$stmt->execute();
		$db = null;
		echo json_encode($wine);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function deleteWine($id) {
	$sql = "DELETE FROM wine WHERE id=:id";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("id", $id);
		$stmt->execute();
		$db = null;
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function findByName($query) {
	$sql = "SELECT * FROM wine WHERE UPPER(name) LIKE :query ORDER BY name";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$query = "%".$query."%";
		$stmt->bindParam("query", $query);
		$stmt->execute();
		$wines = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"wine": ' . json_encode($wines) . '}';
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}


?>