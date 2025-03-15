<?php
class webapp_client{
    public $result=[];

    function __construct() {
        $this->result = [];
        $this->result['message'] = '';
        $this->result['evented_at'] = time();
        $this->result['connection'] = [];
        $this->result['connection']['method'] = '';
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
        $response = json_decode($response, JSON_DEPTH, JSON_OPTION_DECODE)['body'];
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

}
date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Content-Length, Origin, Accept, Access-Control-Allow-Headers, X-Token");

header('Server: Hidden');
header('X-Powered-By: Hidden');

$webapp_client = new webapp_client();
$webapp_client->result['connection']['method'] = $_SERVER['REQUEST_METHOD'];

if( ! ( substr( strtolower( $_SERVER['REQUEST_METHOD'] ), 0, 6 ) == 'option' || strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' ) ) {
    http_response_code(405);
    $webapp_client->result['message'] = 'Method not allowed.';
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
    exit(1);
}

const JSON_OPTION = JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR;
const JSON_DEPTH = 512;
const JSON_OPTION_ENCODE = JSON_OPTION;
const JSON_OPTION_DECODE = JSON_OPTION;

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
$token = $config['external']['switchbot']['credential']['client_token'];
$secret = $config['external']['switchbot']['credential']['client_secret'];

$switchbot = new switchbot();
$nonce = $switchbot->guidv4();
$t = time() * 1000;
$data = utf8_encode($token . $t . $nonce);
$sign = hash_hmac('sha256', $data, $secret,true);
$sign = strtoupper(base64_encode($sign));

$accessmode=isset($_GET['accessmode'])?$_GET['accessmode']:'scenes_list';
if (false) {
} elseif ($accessmode==''&&false) {
} elseif ($accessmode=='devices_list') {
} elseif ($accessmode=='scenes_list') {
    $webapp_client->result['message'] = $switchbot->getScenes($token,$sign,$nonce,$t,$sceneId=null);
} else {
	$webapp_client->result['message'] = 'Unknown params accessmode: '.$accessmode;
	echo json_encode($webapp_client->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}
