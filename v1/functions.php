<?
require 'config.php';
require 'Slim/Slim.php';

$mm = date('m'); $yy = date('Y');
$_SESSION['DB_PREFIX'] = "lksa";
$_SESSION['DB_PREFIX_CURR'] = "lksa".($mm < 4 ? ($yy - 1)."".substr($yy,-2) : $yy."".(substr($yy,-2)+1));
$_SESSION['DB_PREFIX_LAST'] = "lksa".($mm < 4 ? ($yy - 2)."".substr(($yy-1),-2) : ($yy-1)."".(substr(($yy-1),-2)+1));
$_SESSION['USER_DB_PREFIX'] = "ob_sa";

$_SESSION['ROWS_IN_TABLE'] = 30;
$_SESSION['API_ROW_LIMIT'] = 3;
$_SESSION['MOBILE_ROWS_IN_TABLE'] = 15;

$_SESSION['PAY_MODE'] = array(0=>'Unknown', 1=> 'Cash', 2=> 'PDC',3=> 'Other', 4=> '4', 5=>'5', 6=> 'ECS',7=> '7');

function connect(){
	return mysqli_connect($_SESSION['DB_HOST'],$_SESSION['DB_USER'],$_SESSION['DB_PASSWORD'], $_SESSION['DB_PREFIX']);
}

function getConnection() {
	$dbhost=$_SESSION['DB_HOST'];
	$dbuser=$_SESSION['DB_USER'];
	$dbpass=$_SESSION['DB_PASSWORD'];
	$dbname=$_SESSION['DB_PREFIX'];
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}

function executeSelect($q, $id = NULL){
	$results = array();
	$results['row_count'] = 0;
	try {
		$db = getConnection();
		$stmt = $db->query($q);
        $results['found_rows'] = $db->query('SELECT FOUND_ROWS()')->fetch(PDO::FETCH_COLUMN);
		$results['result'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$results['row_count'] = count($results['result']);
		$db = null;
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
		error_log("From ".__FUNCTION__.": ".$e->getMessage());
	}
	return $results;
}

function executeSingleSelect($q){
        $conn = connect();
        $result = mysqli_query($conn, $q) or die(mysqli_error($conn)." DB Error at line ". __LINE__ . " in file " . __FILE__);
        mysqli_close($conn);
        $results = array();
		$value = "";
        $row_count = mysqli_num_rows($result);
        if($row_count ==0)
        	return NULL;
		foreach (mysqli_fetch_array($result, MYSQL_ASSOC) as $key => $value) {
			break;
		}
        mysqli_free_result($result);
        return $value;
}


function executeUpdate($q){
        $conn = connect();
        $result = mysqli_query($conn, $q) or die(mysqli_error($conn)." DB Error at line ". __LINE__ . " in file " . __FILE__);
		$value = mysqli_affected_rows($conn);
        mysqli_close($conn);
        return $value;
}


function executeInsert($q){
        $conn = connect();
        $result = mysqli_query($conn, $q);
		$value = mysqli_insert_id($conn);
        mysqli_close($conn);
        return $value;
}








function startsWith($haystack, $needle){return $needle === "" || strpos($haystack, $needle) === 0;}

function endsWith($haystack, $needle){return $needle === "" || substr($haystack, -strlen($needle)) === $needle;}

function print_a($a){echo "<pre>"; print_r($a); echo "</pre>";}

function nf($a, $zeroReturn = false){
	if($a == 0 || is_null($a) || empty($a))
		if($zeroReturn == true)
			return 0;
		else return "";
	return number_format($a);
}

function convertdatetime($gmttime, $pattern=null, $timezoneRequired ='Asia/Calcutta'){
	if($pattern == null) $pattern = $_SESSION['DATE_FORMAT'];
    $system_timezone = date_default_timezone_get();
    $local_timezone = $timezoneRequired;
    date_default_timezone_set($local_timezone);
    $local = date("Y-m-d h:i:s A");

    date_default_timezone_set("GMT");
    $gmt = date("Y-m-d h:i:s A");
    date_default_timezone_set($system_timezone);
    $diff = (strtotime($gmt) - strtotime($local));

    $date = new DateTime($gmttime);
    $date->modify("-$diff seconds");
    $timestamp = $date->format($pattern);
    return $timestamp;
}




function validEmail($email){

   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex){
      $isValid = false;
   }
   else{
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64){
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255){
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.'){
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local)){
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)){
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain)){
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))){
         // character not valid in local part unless
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))){
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

function titleCase($string, $delimiters = array(" ", "-", ".", "'", "O'", "Mc"), $exceptions = array("and", "to", "of", "das", "dos", "I", "II", "III", "IV", "V", "VI")){
    $string = mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
    foreach ($delimiters as $dlnr => $delimiter) {
        $words = explode($delimiter, $string);
        $newwords = array();
        foreach ($words as $wordnr => $word) {
            if (in_array(mb_strtoupper($word, "UTF-8"), $exceptions)) {
                // check exceptions list for any words that should be in upper case
                $word = mb_strtoupper($word, "UTF-8");
            } elseif (in_array(mb_strtolower($word, "UTF-8"), $exceptions)) {
                // check exceptions list for any words that should be in upper case
                $word = mb_strtolower($word, "UTF-8");
            } elseif (!in_array($word, $exceptions)) {
                // convert to uppercase (non-utf8 only)
                $word = ucfirst($word);
            }
            array_push($newwords, $word);
        }
        $string = join($delimiter, $newwords);
   }//foreach
   return $string;
}
?>