<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Twitter
{
	/*
	* The Oauth Object
	*/
	public $oauth;

	/*
	* The Consumer Key
	*/	
    protected $key;
   
	/*
	* The Secret Key
	*/    
    protected $secret;

	/*
	* The Request Token Endpoint
	*/    
    protected $request_token_endpoint;
    
	/*
	* The Authentication Endpoint
	*/      
    protected $authenticate_endpoint;
    
    /*
    * The Access Endpoint
    */
    protected $access_endpoint;

    public function __construct($oauth)
    {
		$this->oauth = $oauth;
		$this->oauth->ci->load->config('twitter', TRUE);
		    
        $this->key = $this->oauth->ci->config->item('consumer_key', 'twitter'); // consumer key from twitter
        $this->secret = $this->oauth->ci->config->item('key_secret', 'twitter'); // secret from twitter
        $this->request_token_endpoint = $this->oauth->ci->config->item('token_request_endpoint', 'twitter');
        $this->authenticate_endpoint = $this->oauth->ci->config->item('authenticate_endpoint', 'twitter');
        $this->access_endpoint = $this->oauth->ci->config->item('access_endpoint', 'twitter');
    }

	/*
	* Request authorization
	*/	
	public function authorize()
	{
		$token = null;
		
        $params = array(
            "oauth_version" => "1.0",
            "oauth_nonce" => time(),
            "oauth_timestamp" => time(),
            "oauth_consumer_key" => $this->key,
            "oauth_signature_method" => "HMAC-SHA1"
        );
        
		parse_str($this->make_request($params, 'GET', $this->request_token_endpoint), $token);
		redirect($this->authenticate_endpoint.'?oauth_token='.$token['oauth_token']);
	} 

	/*
	* Get the authorization result
	*
	* @return array	The response
	*/	
	public function authorize_result()
	{	
		if(isset($_GET['oauth_verifier']) && isset($_GET['oauth_token']))
		{

			return $this->oauth->response('success', array(
					'token' => $_GET['oauth_token'],
					'token2' => $_GET['oauth_verifier']
				)
			);
		}
		
		if(isset($_GET['denied']))
		{
			return $this->oauth->response('failure', array(
					'error' => "The user denied the request"
				)
			);
		}
	}	

	/*
	* Access a User's Profile
	*
	* @return array	The response
	*/		
	public function access($token = null, $verifier = null)
	{
		$results = null;
		
		$params = array(
    		'oauth_consumer_key' => 'GDdmIQH6jhtmLUypg82g',
    		'oauth_nonce' => time(),
    		'oauth_signature_method' => 'HMAC-SHA1',
    		'oauth_token' => $token,
    		'oauth_timestamp' => time(),
    		'oauth_verifier' => $verifier,
    		'oauth_version' => '1.0'
		);
		
		$request = $this->make_request($params, 'POST', $this->access_endpoint);
		
		if($request[0] == '<')
		{
			$parsed = (array) $this->oauth->parse_xml($request);
			
			if(isset($parsed['error']))
			{
				return $this->oauth->response('failure', array(
						'error' => $parsed['error']
					)
				);
			}		
		}
		else
		{
			parse_str($request, $results);
			return $this->oauth->response('success', array(
					'user' => array(
						'username' => $results['screen_name'],
						'id' => $results['user_id']
					)
				)
			);
		}
	} 
	
    protected function make_request($params, $method, $endpoint)
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
            $secret = $this->_urlencode_rfc3986($this->secret)."&";
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

    function _http($url, $post_data = null)
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

    function _urlencode_rfc3986($input)
    {
        if (is_array($input)) {
            return array_map(array('Twitter', '_urlencode_rfc3986'), $input);
        }
        else if (is_scalar($input)) {
            return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
        }
        else{
            return '';
        }
    }
}