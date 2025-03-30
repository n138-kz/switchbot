<?php
class webapp_client{
	public $result=[];
	public $dbaccess=[
		'dsn'=>null,
		'credential'=>null,
		'tableprefix'=>'',
	];

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
	function loggingDB($option=[]) {
		$default_option = [
			'evented_on'    =>null,
			'scene_id'      =>null,
			'result_status' =>null,
			'result_code'   =>null,
			'result_mesg'   =>null,
			'request_header'=>null,
		];
		$option = array_merge($default_option, $option);
		$option['result_status']=$option['result_status']?1:0;

		$dsn='{schema}:host={host};port={port};dbname={dbname};user={user};password={password}';
		$dsn=str_replace('{schema}',   $this->dbaccess['credential']['schema'],   $dsn);
		$dsn=str_replace('{host}',     $this->dbaccess['credential']['host'],     $dsn);
		$dsn=str_replace('{port}',     $this->dbaccess['credential']['port'],     $dsn);
		$dsn=str_replace('{dbname}',   $this->dbaccess['credential']['database'], $dsn);
		$dsn=str_replace('{user}',     $this->dbaccess['credential']['user'],     $dsn);
		$dsn=str_replace('{password}', $this->dbaccess['credential']['password'], $dsn);
		$tableprefix=$this->dbaccess['credential']['tableprefix'];

		try {
			$pdo = new PDO($dsn, $this->dbaccess['credential']['user'], $this->dbaccess['credential']['password']);
			$sql = 'INSERT INTO {tableName} ({columns1}) VALUES ({columns2});';
			$sql = str_replace('{tableName}', $tableprefix, $sql);
			$sql = str_replace('{columns1}', 'remote_addr, remote_port, useragent, evented_on, scene_id, result_status', $sql);
			$sql = str_replace('{columns2}', '?,?,?,?,?,?', $sql);
			$pre = $pdo -> prepare($sql);
			$res = $pre -> execute([
				$_SERVER['REMOTE_ADDR'],
				$_SERVER['REMOTE_PORT'],
				$_SERVER['HTTP_USER_AGENT'],
				$option['evented_on'],
				$option['scene_id'],
				$option['result_status'],
			]);
			error_log(json_encode($res));
		} catch (\Throwable $th) {
			error_log('Throw: '.$th->__toString());
		}
	}
}
class discord{
	public $webhook_url='';
	public $avatar_url='';
	public $webhook_name='';
	public $embed_color='FFFFFF';
	public $header=[];
	private $latest_log_request=[];
	private $latest_log_result=[];
	private $latest_log_result_error=[];
	private $latest_log_result_header=[];

	function __construct($url='', $header=['Content-Type: multipart/form-data']) {
		$this->webhook_url=$url;
		$this->headers=$header;
		return null;
	}
	function pushMessage($text='', $option=['title'=>'','url'=>'']){
		$curl=curl_init($this->webhook_url);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
		$data=[];
		$data['username']=$this->webhook_name;
		$data['avater_url']=$this->avatar_url;
		$data['embeds']=[];
		$data['embeds'][count($data['embeds'])]=[
			'title'=>$option['title'],
			'description'=>$text,
			'url'=>$option['url'],
			'timestamp'=>date('c'),
			'color'=>hexdec($this->embed_color),
		];
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		$result=curl_exec($curl);
		$result=json_decode($result, TRUE);
		$error=curl_error($curl);
		$info=curl_getinfo($curl);
		$result=($result=='')?null:$result;
		$error=($error=='')?null:$error;

		$this->latest_log_request=$data;
		$this->latest_log_result=$result;
		$this->latest_log_result_error=$error;
		$this->latest_log_result_header=$info;

		return [
			'result'=>$result,
			'errors'=>[
				'code'=>curl_errno($curl),
				'details'=>$error,
			],
			'response_header'=>$info,
		];
	}
	function getLatestLog($item=null){
		if(false){
		}elseif(false){
		}elseif($item=='request'){
			return $this->latest_log_request;
		}elseif($item=='request_header'){
			return $this->headers;
		}elseif($item=='result'){
			return $this->latest_log_result;
		}elseif($item=='result_error'){
			return $this->latest_log_result_error;
		}elseif($item=='result_header'){
			return $this->latest_log_result_header;
		}else{
			return null;
		}
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
		$headers = array(
			"Content-Type:application/json",
			"Authorization:" . $token,
			"sign:" . $sign,
			"nonce:" . $nonce,
			"t:" . $t
		);

		$url = "https://api.switch-bot.com/v1.1/scenes";

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

		$url = "https://api.switch-bot.com/v1.1/scenes/${sceneId}/execute";

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($curl);
		curl_close($curl);
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

$_SERVER['CONTENT_TYPE'] = (isset($_SERVER['CONTENT_TYPE']))?$_SERVER['CONTENT_TYPE']:'application/octet-stream';
if($_SERVER['REQUEST_METHOD']=='POST'&&substr(strtolower($_SERVER['CONTENT_TYPE']),0,16)=='application/json'){
	try {
		$_POST = file_get_contents('php://input');
		$_POST = strlen($_POST)>0 ? json_decode($_POST, TRUE, JSON_DEPTH, JSON_OPTION_DECODE) : [];
	} catch (\JsonException $e) {
		$_POST = null;
		error_log('JSON Parse error: ' . __FILE__ . ':' . __LINE__ . PHP_EOL . $e->getTraceAsString());
	}
}

$webapp_client = new webapp_client();
$webapp_client->result['connection']['method'] = $_SERVER['REQUEST_METHOD'];
$webapp_client->result['connection']['client']['address'] = $_SERVER['REMOTE_ADDR'];
$webapp_client->result['connection']['contentType'] = $_SERVER['CONTENT_TYPE'];

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
$config=array_merge(['internal'=>['standardlib'=>['json'=>[
	'JSON_OPTION'       =>JSON_OPTION,
	'JSON_DEPTH'        =>JSON_DEPTH,
	'JSON_OPTION_ENCODE'=>JSON_OPTION_ENCODE,
	'JSON_OPTION_DECODE'=>JSON_OPTION_DECODE,
]]]], $config);
$webapp_client->dbaccess=[];
$webapp_client->dbaccess['credential']=$config['internal']['databases'][0];

$discord_client=new discord($config['external']['discord']['webhook']['notice']['url'].'?wait=true', ['Content-Type: application/json']);
$discord_client->avatar_url=$config['external']['discord']['webhook']['notice']['avatar'];
$discord_client->webhook_name=$config['external']['discord']['webhook']['notice']['username'];
$discord_client->embed_color=$config['external']['discord']['webhook']['notice']['color'];
$result=$discord_client->pushMessage(
	'```'.PHP_EOL.json_encode([
		'script_file'=>__FILE__,
		'get'=>$_GET,
		'post'=>$_POST,
		'connect'=>[
			'addr'=>$_SERVER['REMOTE_ADDR'],
			'port'=>$_SERVER['REMOTE_PORT'],
			'user-agent'=>$_SERVER['HTTP_USER_AGENT'],
			'header_content-type'=>$webapp_client->result['connection']['contentType'],
			'raw-request'=>file_get_contents('php://input'),
		],
	], JSON_OPTION_ENCODE|JSON_PRETTY_PRINT).PHP_EOL.'```', ['title'=>'Request', 'url'=>$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']]
);
$webapp_client->result['discord_webhook_event'] = [
	'request'=>$discord_client->getLatestLog('request'),
	'request.header'=>$discord_client->getLatestLog('request_header'),
	'result'=>$discord_client->getLatestLog('result'),
	'result.error'=>$discord_client->getLatestLog('result_error'),
	'result.header'=>$discord_client->getLatestLog('result_header'),
];

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

$webapp_client->result['config']=null;
$webapp_client->result['file']=null;
$webapp_client->result['discord_webhook_event']=null;

$accessmode=isset($_GET['accessmode'])?$_GET['accessmode']:'scenes_list';
if (false) {
} elseif ($accessmode==''&&false) {
} elseif (strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' && $accessmode=='devices_list') {
	$webapp_client->result['data_id'] = 'switchbot.'.$accessmode;
	$webapp_client->result['data'] = [
		$webapp_client->result['data_id']=>$switchbot->getDevices($token,$sign,$nonce,$t,$sceneId=null),
	];
	if(is_null($webapp_client->result['data'])){
		http_response_code(400);
		$webapp_client->result['message'] = 'API Credential has invalid.';
		$webapp_client->loggingDB([
			'evented_on'=>'devices_list',
			'result_status'=>false,
			'result_code'=>http_response_code(),
			'request_header'=>json_encode(apache_request_headers(), JSON_OPTION_ENCODE),
		]);
	} else {
		$webapp_client->result['message'] = 'Getted the API data.';
		$webapp_client->loggingDB([
			'evented_on'=>'devices_list',
			'result_status'=>true,
			'result_code'=>http_response_code(),
			'request_header'=>json_encode(apache_request_headers(), JSON_OPTION_ENCODE),
		]);
	}

	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
} elseif (strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' && $accessmode=='scenes_list') {
	$webapp_client->result['data_id'] = 'switchbot.'.$accessmode;
	$webapp_client->result['data'] = [
		$webapp_client->result['data_id']=>$switchbot->getScenes($token,$sign,$nonce,$t,$sceneId=null),
	];
	if(is_null($webapp_client->result['data'])){
		http_response_code(400);
		$webapp_client->result['message'] = 'API Credential has invalid.';
		$webapp_client->loggingDB([
			'evented_on'=>'scenes_list',
			'result_status'=>false,
			'result_code'=>http_response_code(),
			'request_header'=>json_encode(apache_request_headers(), JSON_OPTION_ENCODE),
		]);
	} else {
		$webapp_client->result['message'] = 'Getted the API data.';
		$webapp_client->loggingDB([
			'evented_on'=>'scenes_list',
			'result_status'=>true,
			'result_code'=>http_response_code(),
			'request_header'=>json_encode(apache_request_headers(), JSON_OPTION_ENCODE),
		]);
	}
	array_multisort($webapp_client->result['data'][$webapp_client->result['data_id']]);

	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
} elseif (strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' && $accessmode=='scene_activate') {
	$sceneId = isset($_REQUEST['scene_id'])?$_REQUEST['scene_id']:'';
	if(!$sceneId){
		http_response_code(400);
		$webapp_client->result['message'] = 'API scene_id has empty. this params is required.';
		$webapp_client->loggingDB([
			'evented_on'=>'scene_activate',
			'scene_id'=>$sceneId,
			'result_status'=>false,
			'result_code'=>http_response_code(),
			'result_mesg'=>$webapp_client->result['message'],
			'request_header'=>json_encode(apache_request_headers(), JSON_OPTION_ENCODE),
		]);
		echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
		exit(1);
	}
	$webapp_client->result['data_id'] = 'switchbot.'.$accessmode;
	$webapp_client->result['data'] = [
		$webapp_client->result['data_id']=>$switchbot->runScene($token,$sign,$nonce,$t,$sceneId),
	];
	if(is_null($webapp_client->result['data'])){
		http_response_code(400);
		$webapp_client->result['message'] = 'API Credential has invalid.';
		$webapp_client->loggingDB([
			'evented_on'=>'scene_activate',
			'scene_id'=>$sceneId,
			'result_status'=>false,
			'result_code'=>http_response_code(),
			'result_mesg'=>$webapp_client->result['message'],
			'request_header'=>json_encode(apache_request_headers(), JSON_OPTION_ENCODE),
		]);
	} else {
		$webapp_client->result['message'] = 'Getted the API data.';
		$webapp_client->loggingDB([
			'evented_on'=>'scene_activate',
			'scene_id'=>$sceneId,
			'result_status'=>true,
			'result_code'=>http_response_code(),
			'result_mesg'=>$webapp_client->result['message'],
			'request_header'=>json_encode(apache_request_headers(), JSON_OPTION_ENCODE),
		]);
	}

	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
} else {
	http_response_code(400);
	$webapp_client->result['message'] = 'Unknown params accessmode: '.$accessmode;

	$webapp_client->loggingDB(['evented_on'=>'unknown_parameter']);
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}

