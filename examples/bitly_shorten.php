<?php

/*
*
*	Сокращение ссылок с помощью Bit.ly
*
*/

require_once '../request/request.php';

function shorten($url, $proxy = false)
{
    $r_sess = new request_session(REQUEST_SESS_TEMP);

	if( $proxy)
	{
		$r_sess->curl_opt(CURLOPT_PROXY, $proxy);
	}
	
	$n = 0;
	do 
	{
		$request = new request('https://bitly.com/');
		$request->session($r_sess);
		$request->send();
		
		sleep(1.1);
		$n++;
	}
	while($request->error() and $n < 3);
	
	if( $request->error())
	{
		return false;
	}
	
	$cookie = array_filter($r_sess->get_cookie(), function($e){
		
		if($e['key'] === '_xsrf') return true;
		else return false;
	});
	
	$cookie = array_shift($cookie);
	$token = $cookie['value'];
	
	$data = array(
		'url' => $url
	);
	
	$n = 0;
	do 
	{
		$request = new request('https://bitly.com/data/shorten');
		$request->post($data);
		$request->session($r_sess);
		$request->set_headers('Referer', 'https://bitly.com/');
		$request->set_headers('Origin', 'https://bitly.com/');
		$request->set_headers('X-Requested-With', 'XMLHttpRequest');
		$request->set_headers('X-XSRFToken', $token);
		$request->send();
		
		sleep(1.1);
		$n++;
	}
	while($request->error() and $n < 3);
	
	if( $request->error())
	{
		return false;
	}
	
	$json = json_decode($request->response, true);
	
	if( $json['status_code'] !== 200)
	{
		echo $request->dump();
		return false;
	}
	
	if( !isset($json['data']['anon_shorten']['link']))
	{
		print_r($json);
		return false;
	}
	
	return $json['data']['anon_shorten']['link'];
}