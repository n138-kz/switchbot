<?php
$config=json_decode(file_get_contents(__DIR__.'/.secret/config.json'), TRUE);
$token = $config['external']['switchbot']['credential']['client_token'];
$secret = $config['external']['switchbot']['credential']['client_secret'];
$nonce = guidv4();
$t = time() * 1000;
$data = utf8_encode($token . $t . $nonce);
$sign = hash_hmac('sha256', $data, $secret,true);
$sign = strtoupper(base64_encode($sign));

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
echo $response;
curl_close($curl);

function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
