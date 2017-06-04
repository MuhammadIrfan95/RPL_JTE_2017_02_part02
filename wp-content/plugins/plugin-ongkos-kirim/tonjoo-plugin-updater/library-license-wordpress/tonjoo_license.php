<?php
if ( !class_exists('tonjoo_license') )
{
	include_once 'tpluginUpdater.php';

	class tonjoo_license
	{
		public function __construct()
		{
			$this->time_to_check = 604800;  // per 24 * 7 hours (a week)

			$this->wp_remote_args = array(
			    'sslverify'   => false,
			   	'timeout' => 30
			);
		}

		function RegisterKey($data = '')
		{
			$matches 		= array();
			preg_match_all("#^.+?[^\/:](?=[?\/]|$)#", get_site_url(), $matches); 
			$matches 		= array();
			preg_match_all("#^.+?[^\/:](?=[?\/]|$)#", get_site_url(), $matches); 
			$parameterurl 	= array(
				'plugin_update_name'=> $data['plugin_name'],
				'website'			=> $matches[0][0],
				'key'				=> $data['key'],
			); 
			$new_parameter 	= Encryption::encode(json_encode($parameterurl));
			$params_string 	= '';
			foreach($parameterurl as $var => $val)
			{
				$params_string .= '&' . $var . '=' . urlencode($val);  
			}
			$base_server = isset($_POST['base_server']) ? $_POST['base_server'] : get_option('nusantara_base_server',false);
			if($base_server === 'indonesia')
			{
				$url 	= "http://ongkir-ix.tonjoostudio.com/activateCode?code=".urlencode($new_parameter);
			}else{
				$url 	= "https://tonjoostudio.com/manage/ajax/activateCode?code=".urlencode($new_parameter);
			}
			
			$r 		= tonjoo_license::urlStatus($url);

			if($r)
			{			
				$request = wp_remote_get($url,$this->wp_remote_args);

				if(is_wp_error($request))
				{				
				 	$return['status'] = false;
				 	$return['data'] = 'Please try again ( Error: '.$request->get_error_message().' )';

				 	return $return;
				}
				else
				{
					$upload_dir = wp_upload_dir();
					$dir 		= $upload_dir['basedir'].'/json/';
					if(!is_dir($dir))
					{
						mkdir($upload_dir['basedir'].'/json/');
					}
					$base_server = isset($_POST['base_server']) ? $_POST['base_server'] : get_option('nusantara_base_server',false);
					if($base_server === 'indonesia')
					{
						$url = 'http://ongkir-ix.tonjoostudio.com/license/?token='.$data['key'].'&file='.$data['plugin_name'];
					}else{
						$url = 'https://tonjoostudio.com/manage/ajax/license/?token='.$data['key'].'&file='.$data['plugin_name'];
					}
					$request = wp_remote_get($url,$this->wp_remote_args);
					


					if(is_wp_error($request))
					{
						$return['status'] = false;
					 	$return['data'] = 'Please try again ( Error: '.$request->get_error_message().' )';

					 	return $return;
					}
					else
					{
						$fp 		= fopen($upload_dir['basedir'].'/json/'.$data['plugin_name'].'.json', 'w');
						$r_json  	= (array) json_decode($request['body']);
						$r_new 		= array_merge($r_json , array( 'created' => time() ));
						fwrite($fp, json_encode($r_new));
						fclose($fp);
						
						$return['status'] = true;
					 	$return['data'] = $request['body'];

					 	return $return;
					}
				}
			}
			else
			{
				$return['status'] = false;
			 	$return['data'] = 'Invalid license code';

			 	return $return;
			}
		}

		function unRegisterKey($data = '')
		{
			$matches 		= array();
			preg_match_all("#^.+?[^\/:](?=[?\/]|$)#", get_site_url(), $matches); 
			$parameterurl 	= array(
				'plugin_update_name'=> $data['plugin_name'],
				'website'			=> $matches[0][0],
				'key'				=> $data['key'],
			); 
			$new_parameter 	= Encryption::encode(json_encode($parameterurl));
			$params_string 	= '';
			
			foreach($parameterurl as $var => $val)
			{
				$params_string .= '&' . $var . '=' . urlencode($val);  
			}
			$base_server = isset($_POST['base_server']) ? $_POST['base_server'] : get_option('nusantara_base_server',false);
			if($base_server === 'indonesia')
			{
				$url 	= "http://ongkir-ix.tonjoostudio.com/deactivateCode?code=".urlencode($new_parameter);
			}else{
				$url 	= "https://tonjoostudio.com/manage/ajax/deactivateCode?code=".urlencode($new_parameter);
			}

			$r 		= tonjoo_license::urlStatus($url);

			if($r)
			{
				$request = wp_remote_get($url,$this->wp_remote_args);

				if(is_wp_error($request))
				{
					$return['status'] = false;
				 	$return['data'] = 'Please try again ( Error: '.$request->get_error_message().' )';

				 	return $return;
				}
				else
				{
					$return['status'] = true;
				 	$return['data'] = $request['body'];

				 	return $return;
				}				
			}
			else
			{
				$return['status'] = false;
			 	$return['data'] = 'Invalid license code';

			 	return $return;
			}
		}
		
		function urlStatus($url)
		{
			// override this function to force true

			return true;
		}

		function getStatus($data = '')
		{
			$matches 		= array();
			preg_match_all("#^.+?[^\/:](?=[?\/]|$)#", get_site_url(), $matches); 

			$parameterurl 	= array(
				'plugin_update_name'=> $data['plugin_name'],
				'website'			=> $matches[0][0],
				'key'				=> $data['key'],
			); 
			$new_parameter 	= Encryption::encode(json_encode($parameterurl));
			$params_string 	= '';
			foreach($parameterurl as $var => $val)
			{
				$params_string .= '&' . $var . '=' . urlencode($val);  
			}
			if(@$data['base_server'] === 'indonesia')
			{
				$url 	= "http://ongkir.tonjoostudio.com/getStatus?code=".urlencode($new_parameter);
			}else{
				$url 	= "https://tonjoostudio.com/manage/ajax/getStatus?code=".urlencode($new_parameter);
			}
			// die($url);

			$r 		= tonjoo_license::urlStatus($url);
			if($r)
			{
				$request = wp_remote_get($url,$this->wp_remote_args);
			
				if(is_wp_error($request))
				{
				 	$return['status'] = false;
				 	$return['data'] = 'Please try again ( Error: '.$request->get_error_message().' )';

				 	return $return;
				}
				else
				{
					try {
				        $body = json_decode( $request['body'] );			 
				    } catch ( Exception $ex ) {
				        $body = false;
				    }

				    $return['status'] = true;
				 	$return['data'] = $body;

				 	return $return;
				}
				
			}else{
				return false;				
			}
		}

		

		function getUpdater($data = array())
		{
			$status = tonjoo_license::getStatus($data);
			$status = (array) $status;
			$r 	= true;

			if($r)
			{
				if(isset($status['status'])) 
				{
					if($status['status'] == true)
					{
						$upload_dir = wp_upload_dir();
						$dir 		= $upload_dir['basedir'].'/json/';
						
						if(!is_dir($dir))
						{
							mkdir($upload_dir['basedir'].'/json/');
						}
						$base_server = isset($_POST['base_server']) ? $_POST['base_server'] : get_option('nusantara_base_server',false);
						if($base_server === 'indonesia')
						{
							$url = 'http://ongkir-ix.tonjoostudio.com/license/?token='.$data['key'].'&file='.$data['plugin_name'];
						}else{
							$url = 'https://tonjoostudio.com/manage/ajax/license/?token='.$data['key'].'&file='.$data['plugin_name'];
						}
						$request = wp_remote_get($url,$this->wp_remote_args);
						if(is_wp_error($request))
						{
							/* khusus getUpdater "is_wp_error" hanya return false */

						 	return false;
						}
						else
						{
							$fp = fopen($upload_dir['basedir'].'/json/'.$data['plugin_name'].'.json', 'w');

							$r_json  = (array) json_decode($request['body']);
							$r_new 	= array_merge($r_json , array( 'created' => time() ));
							fwrite($fp, json_encode($r_new));
							fclose($fp);
							return true;	
						}								
					}
					else
					{
						return false;
					}
				} 
				else 
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		public function getJsonLocal($data = array())
		{
			if($data['status']['status'] ==  true)
			{
				$upload_dir = wp_upload_dir();
				$pucFactory = new PucFactory();
				$rdata 		= @file_get_contents($upload_dir['basedir'].'/json/'.$data['plugin_name'].'.json');
				if($rdata)
				{
					$r_json	= json_decode($rdata);

					if((time() - $r_json->created) > $this->time_to_check)
					{
						tonjoo_license::getUpdater($data);
					}

					$upload_url = $upload_dir['baseurl'];
					$update = $pucFactory->buildUpdateChecker(
						/*'https://tonjoostudio.com/manage/ajax/license/?token='.$data['key'].'&file='.$data['plugin_name'],
						'http://ongkir-ix.tonjoostudio.com/license/?token='.$data['key'].'&file='.$data['plugin_name'],*/	
						$upload_url.'/json/'.$data['plugin_name'].'.json',
						$data['file']
					);
					return $update;
				}else{						
					tonjoo_license::getUpdater($data);
					return true;
				}

			}else{
				return false;
			}
		}

		public function showNotification($data,$message)
		{
			$tonjooPremiumNotiv = new tonjooPremiumNotiv($data,$message);
		}

		public function reactivatePopup($data)
		{
			global $current_user;

			$user_id = $current_user->ID;

			delete_user_meta($user_id, $data['plugin_name'] . '-license-notice-ignore', 'true', true);
		}

		// end
	}
}

if ( !class_exists('tonjooPremiumNotiv') )
{
	class tonjooPremiumNotiv 
	{
		function __construct($data,$message)
		{
			$this->data = $data;
			$this->message = $message;
			$this->actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

			add_action('admin_notices', array($this,'notice'));
			add_action('admin_init', array($this,'notice_ignore'));
		}

		public function notice()
		{
			global $current_user;

			$user_id = $current_user->ID;

			$data = $this->data;
			$message = $this->message;
			$actual_link = $this->actual_link;
	        
			$strpos = strpos($actual_link,$data['admin_url']);

	        /* Check that the user hasn't already clicked to ignore the message */
	        if($strpos !== false || $actual_link == $data['admin_url'])
			{
				echo '<div class="updated"><p>';
		        echo __($message);
		        echo "</p></div>";
			}
			else if (! get_user_meta($user_id, $data['plugin_name'] . '-license-notice-ignore'))
			{
		        echo '<div class="updated"><p>';
		        printf(__($message . ' <a href="%1$s" style="float:right;margin-top:5px;color:#a00;">Hide Notice</a>'), '?' . $data['plugin_name'] . '-license-notice-ignore=0&url=' . $actual_link);
		        echo "</p></div>";
			}
		}

		public function notice_ignore()
		{
			global $current_user;

			$user_id = $current_user->ID;

			$data = $this->data;
			$message = $this->message;
			$actual_link = $this->actual_link;

	        /* If user clicks to ignore the notice, add that to their user meta */
	        if ( isset($_GET[$data['plugin_name'] . '-license-notice-ignore']) && '0' == $_GET[$data['plugin_name'] . '-license-notice-ignore'] ) 
	        {
	        	add_user_meta($user_id, $data['plugin_name'] . '-license-notice-ignore', 'true', true);

	        	/**
		         * Redirect
		         */
		        echo "<meta http-equiv='refresh' content='0;url={$_GET['url']}' />";
		        echo "<h2>Loading...</h2>";
		        exit();
			}
		}
	}
}

if ( !class_exists('Encryption') )
{
	class Encryption {
	
		public static function encode($string, $key = '')
		{
			$key = Encryption::get_key($key);
			$enc = Encryption::_xor_encode($string, $key);
			return base64_encode($enc);
		}
	
		public static function get_key($key = '')
		{
			if ($key == '')
			{
				if(defined('_SALT')) {
					$key = _SALT;
				}else{
					$key = "'*#@jh$%[*H@nb]+)@Dhl;,E';";
				}
			}
			return md5($key);
		}
	
		function set_key($key = '')
		{
			$this->encryption_key = $key;
		}
	
		public static function _xor_encode($string, $key)
		{
			$rand = '';
			while (strlen($rand) < 32)
			{
				$rand .= mt_rand(0, mt_getrandmax());
			}
			$rand = Encryption::hash($rand);
			$enc = '';
			for ($i = 0; $i < strlen($string); $i++)
			{			
				$enc .= substr($rand, ($i % strlen($rand)), 1).(substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
			}
			return Encryption::_xor_merge($enc, $key);
		}
	
		public static function _xor_decode($string, $key)
		{
			$string = Encryption::_xor_merge($string, $key);
			$dec = '';
			for ($i = 0; $i < strlen($string); $i++)
			{
				$dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
			}
			return $dec;
		}
	
		public static function _xor_merge($string, $key)
		{
			$hash = Encryption::hash($key);
			$str = '';
			for ($i = 0; $i < strlen($string); $i++)
			{
				$str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
			}
			return $str;
		}
	
		public static function set_hash($type = 'sha1')
		{
			$this->_hash_type = ($type != 'sha1' AND $type != 'md5') ? 'sha1' : $type;
		}
	
		public static function hash($str)
		{
			return Encryption::sha1($str);
		}
	
		public static function sha1($str)
		{
			if ( ! function_exists('sha1'))
			{
				if ( ! function_exists('mhash'))
				{
					$SH = _class('sha');
					return $SH->generate($str);
				}
				else
				{
					return bin2hex(mhash(MHASH_SHA1, $str));
				}
			}
			else
			{
				return sha1($str);
			}
		}
	}
}