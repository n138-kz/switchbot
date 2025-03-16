<?php
class webapp_client{
	public $result=[];

	function __construct() {
		$this->result = [];
		$this->result['message'] = '';
		$this->result['evented_at'] = time();
		$this->result['connection'] = [];
		$this->result['connection']['method'] = '';
		$this->result['connection']['client'] = [];
		$this->result['connection']['client']['address'] = '';
		$this->result['file'] = [];
		$this->result['file']['path'] = '';
	}
	function setMessage($text='') {
		$this->result['message'] = $text;
		return $this->result['message'];
	}
	function result_return() {
		$result = $this->result;
		$result['evented_at'] = time();
		return $result;
	}
}
class switchbot{
	function __construct() {}
	function guidv4($data = null) {
		// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
		$data = $data ?? random_bytes(16);
		assert(strlen($data) == 16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

		// Output the 36 character UUID.
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
	function getScenes($token,$sign,$nonce,$t,$sceneId=null) {
		$url = "https://api.switch-bot.com/v1.1/scenes";

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$headers = array(
			"Content-Type:application/json",
			"Authorization:" . $token,
			"sign:" . $sign,
			"nonce:" . $nonce,
			"t:" . $t
		);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($curl);
		curl_close($curl);

		$response = json_decode($response, JSON_DEPTH, JSON_OPTION_DECODE);
		if(!isset($response['body'])){
			return null;
		}

		$response = $response['body'];
		if ($sceneId!==null) {
			foreach($response as $k => $v) {
				if ($v['sceneId'] == $sceneId) {
					$response = $v;
					break;
				}
			}
		}
		return $response;
	}
	function getDevices($token,$sign,$nonce,$t,$deviceId=null) {
		$headers = array(
			"Content-Type:application/json",
			"Authorization:" . $token,
			"sign:" . $sign,
			"nonce:" . $nonce,
			"t:" . $t
		);

		if(false){
		}elseif(!is_null($deviceId)){
			$url = "https://api.switch-bot.com/v1.1/devices/${deviceId}/status";

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			$response = curl_exec($curl);
			curl_close($curl);

			$response = json_decode($response, JSON_DEPTH, JSON_OPTION_DECODE);
			if(!isset($response['body'])){
				return null;
			}

			$response = $response['body'];
			return $response;

		}else{
			$url = "https://api.switch-bot.com/v1.1/devices";

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			$response = curl_exec($curl);
			curl_close($curl);

			$response = json_decode($response, JSON_DEPTH, JSON_OPTION_DECODE);
			if(!isset($response['body'])){
				return null;
			}

			$response = $response['body'];
			if(!isset($response['deviceList'])){
				return null;
			}

			$response = $response['deviceList'];
			foreach($response as $k1 => $v1){
				foreach($response as $k2 => $v2){
					if($v1['deviceId']!=$v2['deviceId']){
						continue;
					}
					$response[$k1]=array_merge($response[$k1],$this->getDevices($token,$sign,$nonce,$t,$v1['deviceId']));
				}
			}

			return $response;
		}
	}
	function runScene($token,$sign,$nonce,$t,$sceneId=null) {
		$headers = array(
			"Content-Type:application/json",
			"Authorization:" . $token,
			"sign:" . $sign,
			"nonce:" . $nonce,
			"t:" . $t
		);

	}
}
date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Content-Length, Origin, Accept, Access-Control-Allow-Headers, X-Token");

header('Server: Hidden');
header('X-Powered-By: Hidden');

const JSON_OPTION = JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR;
const JSON_DEPTH = 512;
const JSON_OPTION_ENCODE = JSON_OPTION;
const JSON_OPTION_DECODE = JSON_OPTION;

$webapp_client = new webapp_client();
$webapp_client->result['connection']['method'] = $_SERVER['REQUEST_METHOD'];
$webapp_client->result['connection']['client']['address'] = $_SERVER['REMOTE_ADDR'];

if( ! ( substr( strtolower( $_SERVER['REQUEST_METHOD'] ), 0, 6 ) == 'option' || strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' || strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' ) ) {
	http_response_code(405);
	$webapp_client->result['message'] = 'Method not allowed.';
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}
if(!isset($_SERVER['REMOTE_ADDR'])){
	http_response_code(421);
	$webapp_client->result['message'] = '421 Misdirected Request';
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}

$config_base = '{"external": {"switchbot": {"credential": {"client_token": "","client_secret": ""},"apimanuals": ["https://github.com/OpenWonderLabs/SwitchBotAPI"],"curl": {"timeout": 30}}}}';
$config_base = json_decode($config_base, JSON_OPTION_ENCODE);
$webapp_client->result['file']['path'] = $config = __DIR__.'/..'.'/.secret/config.json';
if(!file_exists($config)){
	$webapp_client->result['message'] = 'Config file does not exist.';
	$webapp_client->result['config'] = $config_base;
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}
if(!is_readable($config)){
	$webapp_client->result['message'] = 'Config file does readable.';
	$webapp_client->result['config'] = $config_base;
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}

$config=array_merge($config_base, json_decode(file_get_contents($config), TRUE, JSON_DEPTH, JSON_OPTION_DECODE));
$config=array_merge($config, ['internal'=>['standardlib'=>['json'=>[
	'JSON_OPTION'       =>JSON_OPTION,
	'JSON_DEPTH'        =>JSON_DEPTH,
	'JSON_OPTION_ENCODE'=>JSON_OPTION_ENCODE,
	'JSON_OPTION_DECODE'=>JSON_OPTION_DECODE,
]]]]);
$webapp_client->result['config'] = $config;
$token = $config['external']['switchbot']['credential']['client_token'];
$secret = $config['external']['switchbot']['credential']['client_secret'];
try{
	list($token, $secret) = json_decode(base64_decode($_GET['x-token']), TRUE, JSON_DEPTH, JSON_OPTION_DECODE);
	$webapp_client->result['config']['external']['switchbot']['credential']['client_token'] = '(hashed):'.hash_hmac('sha256', $token, $_SERVER['REMOTE_ADDR']);
	$webapp_client->result['config']['external']['switchbot']['credential']['client_secret'] = '(hashed):'.hash_hmac('sha256', $secret, $token);
} catch (\Exception $e) {
	list($token, $secret) = ['',''];
}

if(strlen($token)==0) {
	$webapp_client->result['message'] = 'API token has empty. this params is required.';
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}
if(strlen($secret)==0) {
	$webapp_client->result['message'] = 'API secret has empty. this params is required.';
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}

$switchbot = new switchbot();
$nonce = $switchbot->guidv4();
$t = time() * 1000;
$data = utf8_encode($token . $t . $nonce);
$sign = hash_hmac('sha256', $data, $secret,true);
$sign = strtoupper(base64_encode($sign));

$accessmode=isset($_GET['accessmode'])?$_GET['accessmode']:'scenes_list';
if (false) {
} elseif ($accessmode==''&&false) {
} elseif (strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' && $accessmode=='devices_list') {
	$webapp_client->result['data_id'] = 'switchbot.'.$accessmode;
	$webapp_client->result['data'] = [
		$webapp_client->result['data_id']=>$switchbot->getDevices($token,$sign,$nonce,$t,$sceneId=null),
	];
	if(is_null($webapp_client->result['data'])){
		$webapp_client->result['message'] = 'API Credential has invalid.';
	} else {
		$webapp_client->result['message'] = 'Getted the API data.';
	}
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
} elseif (strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' && $accessmode=='scenes_list') {
	$webapp_client->result['data_id'] = 'switchbot.'.$accessmode;
	$webapp_client->result['data'] = [
		$webapp_client->result['data_id']=>$switchbot->getScenes($token,$sign,$nonce,$t,$sceneId=null),
	];
	if(is_null($webapp_client->result['data'])){
		$webapp_client->result['message'] = 'API Credential has invalid.';
	} else {
		$webapp_client->result['message'] = 'Getted the API data.';
	}
	array_multisort($webapp_client->result['data'][$webapp_client->result['data_id']]);
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
} elseif (strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' && $accessmode=='scene_activate') {
	$sceneId = isset($_POST['scene_id'])?$_POST['scene_id']:''
	$webapp_client->result['data_id'] = 'switchbot.'.$accessmode;
	$webapp_client->result['data'] = [
		$webapp_client->result['data_id']=>$switchbot->runScene($token,$sign,$nonce,$t,$sceneId),
	];
	if(is_null($webapp_client->result['data'])){
		$webapp_client->result['message'] = 'API Credential has invalid.';
	} else {
		$webapp_client->result['message'] = 'Getted the API data.';
	}
	array_multisort($webapp_client->result['data'][$webapp_client->result['data_id']]);
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
} else {
	$webapp_client->result['message'] = 'Unknown params accessmode: '.$accessmode;
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}

