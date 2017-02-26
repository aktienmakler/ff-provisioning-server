<?php
$f=fopen(dirname(__FILE__)."/test.log", "a+");
fwrite($f, date("Y-m-d H:i:s")."\n");
fwrite($f, var_export($_REQUEST, true)."\n");
fwrite($f, var_export($_SERVER, true)."\n");
print_r($_REQUEST);
die();


error_reporting(E_ALL);
$file_db = new PDO('sqlite:provisionierung.db');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// print_r($_POST);
// print_r($_GET);
// print_r($_REQUEST);
// echo "fooo";

// $data		=	(isset($_POST['password']) ? TRUE : FALSE);
// $hardware	=	$_POST['hardware'];
$task		=	$_POST['task'];
// $task		=	$_REQUEST['task'];
// $user		=	
// $password	=	;

// echo getUserFromRequest();
// die();

if(!checkTasks($task)) http_response_code(501);//invalid function

if (checkUUID(getUserFromRequest())) {
	if(checkAuth()) {
		$outputData = FALSE;
		if($r = doTask($task, $outputData)) {
// 			print_r($outputData);
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"recipe_".getUserFromRequest().".tar.gz\"");
// header("Content-Disposition: attachment; filename=\"recipes.tar.gz\"");
// echo getUserFromRequest();
// 			http_response_code(200);
// readfile("/tmp/xauth-1003-_0");
// echo "foofff";
		} else {
			http_response_code(502);//error
		}
	} else {
		http_response_code(400);//no auth
	}
} else {
	if ($task=="addNode") {
		// Erstinstallation
		if(addNode()) {
			http_response_code(200);
		} else {
			http_response_codfetchAlle(503);//error
		}
	} else {
		http_response_code(204);//Erstinstallation$return
	}
}
die();
################
function getGlobalMacros() {
	return array(
		"foo"	=>	"bar",
	);
}


function checkTasks($task) {
	return in_array($task, array("addNode", "ack", "getRecipe"));
}

function checkUUID($uuid) {
	global $file_db;
	$query = 'SELECT COUNT(*) AS cnt, password FROM nodes WHERE uuid="'.$uuid.'"';
	if($result = $file_db->query($query)) {
		$r = $result->fetchAll(PDO::FETCH_ASSOC);
		if($r[0]["cnt"]>0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

function doTask($task, &$output) {
	switch ($task) {
		case "getRecipe":
			$recipe = getRecipe();
			if(!$recipe) {
// 				echo __FILE__;
				return FALSE;
			} else {
				$output = $recipe;
				return TRUE;
			}
// 			print_r($output);
// 			$session_id	= createSession();
		break;
		case "finishedIngredient":
		break;
// 		case "addNode":
// 		break;
		default:
		break;
	}
}

function checkAuth() {
	if(password_verify (getPasswordFromRequest(), getPassword(getUserFromRequest()))) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function getPassword($user) {
	global $file_db;
	$query = 'SELECT password FROM nodes WHERE uuid="'.getUserFromRequest().'"';
	if($result = $file_db->query($query)) {
		$r = $result->fetchAll(PDO::FETCH_ASSOC);
		return $r[0]["password"];
	} else {
		return FALSE;
	}
}

function getUserFromRequest() {
// print_r($_SERVER);
// die();
	return $_SERVER['PHP_AUTH_USER'];
}

function getPasswordFromRequest() {
	return $_SERVER['PHP_AUTH_PW'];
}

function getNameFromRequest() {
	return $_POST['chosenNodeName'];
}

function getHardwareFromRequest() {
	return $_POST['hardware'];
}

function getRecipe() {
// 	echo __FILE__;
	if($r = getRawRecipe()) {
		return $r2 = createRecipeFromRaw($r, getGlobalMacros());
// 		print_r($r2);
	} else {
		return FALSE;
	}
}

function getRawRecipe() {
	global $file_db;
	$query = "SELECT 
			h.macros AS macro_hardware,
			p.macros AS macro_package,
			n.macros AS macro_node,
			nr.macros AS macro_noderecipe,
			r.macros_default AS macro_recipe_default,
			r.template
                FROM nodes AS n
                LEFT OUTER JOIN noderecipes AS nr ON  n.id =  nr.node_id
                LEFT OUTER JOIN recipes AS r ON nr.recipe_id = r.id
                LEFT OUTER JOIN packages AS p on r.package_id = p.id
                INNER JOIN hardware AS h ON n.hardware_id = h.id
                WHERE 
			n.uuid = '".getUserFromRequest()."'
		";
// 		ORDER BY nr.order ASC
// 		u.macros,
// 		echo $query;
	if($result = $file_db->query($query)) {
		$r = $result->fetchAll(PDO::FETCH_ASSOC);
// print_r($r);
// 		echo $query;
		return $r;
	} else {
		return FALSE;	#	no data
	}
}

function getMergedMacros($array) {
	
}

function createRecipeFromRaw($recipe, $macros) {
	return $recipe;
}

function createSession() {
	global $file_db;
	# sessions_id
	$query = "INSERT INTO sessions (
		node_id,
		recipe_id.
		macros,
		created
	) VALUES (
		'".$node_id."',
		'".$recipe_id."',-O -J -L
		'".$macros."',
		'".date("Y-m-d H:i:s")."'
	)";
	$result = $file_db->query($query);
// 	return $inserted_id;
	return $file_db->lastInsertId();
}

function getHardwareKey($hardware) {
	$sql = "SELECT id FROM hardware WHERE name = '".$hardware."'";
	if(count($result)==1) {
		return $id;
	} else {
		return FALSE;
	}
}

/**
*	@return Integer
*/
function addHardware($hardware) {
	global $file_db;
	$query = "INSERT INTO hardware (name) VALUES('".$hardware."')";
	$file_db->query($query);
	return $file_db->lastInsertId();
}

function addNode() {
	if(!($hid=getHardwareIdFromName())) {
		$hid = addHardware(getHardwareFromRequest());
	}
	
	$query = 'INSERT INTO nodes (uuid, name, hardware_id, password) VALUES("'.getUserFromRequest().'", "'.getNameFromRequest().'", "'.intval($hid).'", "'.password_hash(getPasswordFromRequest(), PASSWORD_DEFAULT).'");';
	$result = $file_db->query($query);
// 	http_response_code(201);
	return TRUE;//Test fehlt
}

/**
*	@return false|integer
*/
function getHardwareIdFromName($hardware) {
	global $file_db;
	$query = "SELECT id FROM hardware WHERE name = '".$hardware."'";
	if($result = $file_db->query($query)) {
		if($result->fetchColumn()>0) {return 123;}
	}
	return FALSE;
}

function prepareDownloadFile() {

}
############################

/*
$f=fopen(dirname(__FILE__)."/test.log", "a+");
fwrite($f, date("Y-m-d H:i:s")."\n");
if($_POST['task']=="addNode") {
} else {
	$outputFile = "data.tar.gz";
	$query = 'SELECT COUNT(*) AS cnt, password FROM nodes WHERE nodeUUID="'.$_POST['nodeUUID'].'"';
	// echo "Foo";
	if($result = $file_db->query($query)) {
		$r = $result->fetchAll();
		if($r[0]["cnt"]>0) {
// fwrite($f, var_export($r, TRUE)."\n");
// fwrite($f, var_export(count($r), TRUE)."\n");
			if(password_verify ($_POST['pw'], $r[0]["password"])) {
				http_response_code(200);
			} else {
				http_response_code(401);
			}
			
		} else {
			http_response_code(204);
		}
	}
}

fwrite($f, var_export($_POST, TRUE)."\n");
fclose($f);
*/

$file_db = null;
die();



// header("Content-type: application/octet-stream");
// header("Content-Disposition: attachment; filename=".$outputFile);


/*

$cmd = "curl -O -J -L ".$url;
http://php.net/manual/de/function.http-response-code.php
*/
?>
