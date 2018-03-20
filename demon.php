<?php
/**
 * Created by PhpStorm.
 * User: BravovRM
 * Date: 19.03.2018
 * Time: 10:09
 */

$stop = false;

define('E_MAIL_ERROR','r@13ip.ru');
define('LOG_F', dirname(__FILE__).'/log.txt');
$url = 'https://syn.su/testwork.php';
$messege = array();

function xor_bytes($data , $key){
	$l = strlen($data);
	$k = strlen($key);
	$r = '';
	for($i = 0; $i < $l; $i++){
		$r .= $data[$i] ^ $key[$i % $k];
	}
	return $r;
}
function getMessage($url, $props){
	$sendmessege = '';
	if($curl = curl_init()){
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $props);
		$out = curl_exec($curl);
		curl_close($curl);
		$out = json_decode($out, true);
	}else{
		$out = array(
			'response' => NULL,
			'errorCode' => 'arr',
			'errorMessage' => 'Fail curl_init'
		);
	}
	if ($out['response'] == NULL) {
		switch ($out['errorCode']) {
			case '15':
				$sendmessege = 'Код 15 – Нет такого метода';
				break;
			case '20':
				$sendmessege = 'Код 20 – Пустое значение параметра message';
				break;
			case '10':
				$sendmessege = 'Код 10 – Не получилось расшифровать строку';
				break;
			case 'arr':
				$sendmessege = 'Код err – curl_init';
				break;
		}
		mail(E_MAIL_ERROR, "Ошибка с кодом ".$out['errorCode'], $sendmessege);
		$stop = true;
		exit(1);
	}
	return $out;
}
$pid = pcntl_fork();
if ($pid == -1) {
	die('Error fork process' . PHP_EOL);
} elseif ($pid) {
	die('Die parent process' . PHP_EOL);
} else {
	while(!$stop) {
		$messege = getMessage($url, 'method=get');
		$key = base64_encode(xor_bytes($messege['response']['message'], $messege['response']['key']));
		$messege = getMessage($url, 'method=update&message='.$key);
		if($messege['response'] == "Success"){
			$stop = false;
			sleep(3600);
		}

	}
}
posix_setsid();