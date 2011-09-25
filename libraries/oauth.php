<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class OAuth
{
	/*
	* The CodeIgniter Instance
	*/
	public $ci;

	/*
	* The Type of Auth Being Used
	*/
	public $type;	
	
	/*
	* The Constructor Method
	*/	
	public function __construct()
	{
		$this->ci = &get_instance();
		$this->ci->load->config('oauth', TRUE);
		$this->ci->lang->load('messages');
		$this->ci->load->library('session');
		$this->ci->load->helper('url');
	}

	/**
	 * Make a call. Uses other helper methods to make the request.
	 *
	 * @param	string	The login method to use
	 * @param	array	$params[0] is the login method, $params[1] are the params for the request
	 * @return	object	Should return a success or failure, along with a response.
	 */		
	public function __call($method, $params)
	{
		$loaded = $this->_load_module($params[0], $method);
		if($loaded)
		{
			return (isset($params[1])) 
			? $this->type->$method($params[1])
			: $this->type->$method();
		}
		else
		{
			return $this->response('failure', array(
					'error' => 'Module or method does not exist'
				)
			);
		}
	}	

	/**
	 * Try to load an authentication module
	 *
	 * @param	string	The authentication module to load
	 * @return	mixed	Will return bool if file is not found.  Will return file as object if found.
	 */		
	private function _load_module($module, $method)
	{
		$module_location = dirname(__FILE__).'/providers/'.$module.'.php';
		if (!is_file($module_location))
		{
			return FALSE;
		}
		
		if(!class_exists($module))
		{
			ob_start();
			include $module_location;
			ob_get_clean();
			
			$this->type = new $module($this);
		}

		if(method_exists($this->type, $method))
		{
				return TRUE;
		}		
	}

	/**
	 * Build the request to the Oauth Provider
	 *
	 * @param	array	Params to set
	 * @param	string	The method to use (such as POST)
	 * @param	string	The endpoint for the reuest
	 * @param	string	The secret key
	 * @return	string	Response from the _http call
	 */	
    public function make_request($params, $method, $endpoint, $secret)
    {
         // BUILD SIGNATURE
            // encode params keys, values, join and then sort.
            $keys = $this->_urlencode_rfc3986(array_keys($params));
            $values = $this->_urlencode_rfc3986(array_values($params));
            $params = array_combine($keys, $values);
            uksort($params, 'strcmp');

            // convert params to string 
            foreach ($params as $k => $v) {$pairs[] = $this->_urlencode_rfc3986($k).'='.$this->_urlencode_rfc3986($v);}
            $concatenatedParams = implode('&', $pairs);

            // form base string (first key)
            $baseString= "$method&".$this->_urlencode_rfc3986($endpoint)."&".$this->_urlencode_rfc3986($concatenatedParams);
            // form secret (second key)
            $secret = $this->_urlencode_rfc3986($secret)."&";
            // make signature and append to params
            $params['oauth_signature'] = $this->_urlencode_rfc3986(base64_encode(hash_hmac('sha1', $baseString, $secret, TRUE)));

         // BUILD URL
            // Resort
            uksort($params, 'strcmp');
            // convert params to string 
            foreach ($params as $k => $v) {$urlPairs[] = $k."=".$v;}
            $concatenatedUrlParams = implode('&', $urlPairs);
            // form url
            $url = $endpoint."?".$concatenatedUrlParams;
			
         // Send to cURL
         return $this->_http($url);          
    }  

	/**
	 * Make the Curl Call to the Oauth Proivder
	 *
	 * @param	string	The URL to make the call to
	 * @param	array	Array of post fields
	 * @return	mixed	Could vary per provider
	 */	
    private function _http($url, $post_data = null)
    {       
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        if(isset($post_data))
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;

        return $response;
    }

	/**
	 * URL Encode the request
	 *
	 * @param	mixed	The input array  / string
	 * @return	mixed
	 */	
    private function _urlencode_rfc3986($input)
    {
        if (is_array($input)) {
            return array_map(array('Oauth', '_urlencode_rfc3986'), $input);
        }
        else if (is_scalar($input)) {
            return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
        }
        else{
            return '';
        }
    }
	/**
	 * Parses an XML response and creates an object using SimpleXML
	 *
	 * @param 	string	raw xml string
	 * @return	object	response object
	*/		
	public function parse_xml($xml_str)
	{
		$xml_str = trim($xml_str);
		$xml_str = preg_replace('/xmlns="(.+?)"/', '', $xml_str);
		if($xml_str[0] != '<')
		{
			$xml_str = explode('<', $xml_str);
			unset($xml_str[0]);
			$xml_str = '<'.implode('<', $xml_str);
		}
		
		$xml = new SimpleXMLElement($xml_str);
		
		return $xml;
	}	
	
	/**
	 * Normalize the response
	 *
	 * @param	string	The reponse status (success or failure)
	 * @return	object	The response status and details
	 */		
	public function response($status, $details)
	{
		$return = array(
			'status' => $status
		);
		
		if(isset($details['token'])) $return['token'] = $details['token'];
		
		if(isset($details['token2'])) $return['token2'] = $details['token2'];
		
		if(isset($details['error'])) $return['error'] = $details['error'];
		
		if(isset($details['user'])) $return['user'] = $details['user'];
		
		return (object) $return;
	}
}