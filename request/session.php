<?php

define('REQUEST_SESS_NEW', 1);
define('REQUEST_SESS_TEMP', 2);

class request_session
{	
	private $is_temp = false;
	private $cookie_file = false;
	private $directory = false;
	private $flags = 0;
	
	public $curl_opt = [];
	public $request_opt = [];
	public $out_headers = [];
	public $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';
	
	public function __construct($flags = 0)
	{
		require_once __DIR__.'/functions.php';
		
		$this->flags = $flags;
		
		if( $flags & REQUEST_SESS_TEMP)
		{
			$this->is_temp = true;
			
			require_once __DIR__.'/tmpfile.php';
		}
	}
	
	public function curl_opt($name, $value)
	{
		$this->curl_opt[$name] = $value;
	}
	
	public function request_opt($name, $value)
	{
		$this->request_opt[$name] = $value;
	}
	
	public function get_user_agent()
	{
		return $this->user_agent;
	}
	
	public function set_user_agent($user_agent)
	{
		if( !empty($user_agent))
		{
			$this->user_agent = $user_agent;
		}
	}
	
	public function set_headers($key, $value = '')
	{
		if( is_array($key))
		{
			$this->out_headers = array_merge_recursive($this->out_headers, $key);
		}
		else
		{
			$this->out_headers[] = "{$key}: {$value}";
		}
	}
	
	public function export()
	{
		$result = $this;
		$result->cookies = file_get_contents($this->get_cookie_file());
		
		return json_encode($result);
	}
	
	public function import($settings)
	{
		if( empty($settings)) return;
		
		$settings = json_decode($settings, true);
		
		file_put_contents($this->get_cookie_file(), $settings['cookies']);
		unset($settings['cookies']);
		
		foreach($settings as $key => $value)
		{
			$this->{$key} = $value;
		}
	}
	
	public function set_sess_dir($directory)
	{
		$this->directory = $directory;
		
		if( $this->flags & REQUEST_SESS_NEW)
		{
			$filename = $this->get_cookie_file();
			
			if( file_exists($filename)) unlink($filename);
		}
	}
	
	public function get_sess_dir()
	{
		if( !$this->directory)
		{
			return false;
		}
		
		return $this->directory;
	}
	
	public function get_cookie_file()
	{
		if( empty($this->cookie_file))
		{
			if($this->is_temp)
			{
				$this->cookie_file = new tmpfile;
			}
			else
			{
				$this->cookie_file = $this->get_sess_dir().'/cookie.dat';
			}
		}
		
		return $this->cookie_file;
	}
	
	public function get_cookie_string()
	{
		return file_get_contents($this->get_cookie_file());
	}
	
	public function set_cookie_string($cookie)
	{
		return file_put_contents($this->get_cookie_file(), $cookie);
	}
	
	public function set_cookie($cookie)
	{
		$default_cookie =  array(
			'domain'	  => '',
			'domain_only' => 'FALSE',
			'path'		  => '/',
			'secure'	  => 'FALSE',
			'expires'	  => 0,
			'key'		  => '',
			'value'	      => '',
		);
		
		$cookies = $this->get_cookie();
		
		if(isset($cookie[0]) and !isset($cookie['key']))
		{
			$cookie = array_map(function($e) use ($default_cookie){
				return array_merge($default_cookie, $e);
			}, $cookie);
			
			$new_keys = array_column($cookie, 'key');
			
			$cookies = array_filter($cookies, function($e) use ($new_keys){
				if( in_array($e['key'], $new_keys)) return false;
				else return true;
			});
			
			$cookies = array_merge_recursive($cookies, $cookie);
		}
		else
		{
			$cookies = array_filter($cookies, function($e) use ($cookie){
				if( $e['key'] === $cookie['key']) return false;
				else return true;
			});
			
			$cookie = array_merge($default_cookie, $cookie);
			$cookies[] = $cookie;
		}
		
		set_cookies_in_file($cookies, $this->get_cookie_file());
	}
	
	public function get_cookie()
	{
		return get_cookies_from_file( $this->get_cookie_file());
	}
}
