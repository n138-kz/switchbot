<?php
const JSON_OPTION = JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR;
const JSON_DEPTH = 512;
const JSON_OPTION_ENCODE = JSON_OPTION;
const JSON_OPTION_DECODE = JSON_OPTION;
$config_base=[
	'external' => [
		'switchbot' => [
			'credential' => [
				'client_token'=>'',
				'client_secret'=>'',
			],
            'apimanuals'=>[
                "https://github.com/OpenWonderLabs/SwitchBotAPI",
            ],
			'curl' => [
				'timeout' => 30,
			],
		],
	],
];
$config=__DIR__.'/.secret/config.json';
if(!file_exists($config)){
	echo json_encode([
		'message'=>'Config file does not exist.',
		'file'=>[
			'path'=>$config,
		],
	], JSON_OPTION_ENCODE);
	file_put_contents($config, json_encode($config_base, JSON_OPTION_ENCODE));
	exit(1);
}
if(!is_readable($config)){
	echo json_encode([
		'message'=>'Config file does readable.',
		'file'=>[
			'path'=>$config,
		],
	], JSON_OPTION_ENCODE);
	file_put_contents($config, json_encode($config_base, JSON_OPTION_ENCODE));
	exit(1);
}
$config=array_merge($config_base,json_decode(file_get_contents($config), TRUE, JSON_DEPTH, JSON_OPTION_DECODE));
$token = $config['external']['switchbot']['credential']['client_token'];
$secret = $config['external']['switchbot']['credential']['client_secret'];
$nonce = guidv4();
$t = time() * 1000;
$data = utf8_encode($token . $t . $nonce);
$sign = hash_hmac('sha256', $data, $secret,true);
$sign = strtoupper(base64_encode($sign));

$activates=__DIR__.'/.secret/activate_scenes.json';
try {
    $activates=__DIR__.'/.secret/activate_scenes.json';
    clearstatcache(TRUE);
    if(!file_exists($activates)){ throw new \Exception('Config file does not exist.'); }
    if(!is_readable($activates)){ throw new \Exception('Config file does readable.'); }
    if(!filesize($activates)){ throw new \Exception('Config file does readable.'); }
    
    $activates=json_decode(file_get_contents(__DIR__.'/.secret/activate_scenes.json'), TRUE);
    foreach($activates as $k => $v) {
        if (!isset($v)) { throw new \Exception('Config params does accessable.'); }
    }
} catch (\Exception $e) {
    echo json_encode([
        'message'=>$e->getMessage(),
        'file'=>[
            'path'=>$activates,
        ],
    ], JSON_OPTION_ENCODE);
	file_put_contents($activates, json_encode([], JSON_OPTION_ENCODE));
	exit(1);
}

$response=[];
foreach($activates as $k => $v) {
    $url = 'https://api.switch-bot.com/v1.1/scenes/' . $v . '/execute';
    
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
    curl_setopt($curl, CURLOPT_POST, TRUE);
    $response[$v][] = [
        'sceneId' => $v,
        'response' => curl_exec($curl),
    ];
    curl_close($curl);
}    
echo json_encode($response, JSON_OPTION_ENCODE);

function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
