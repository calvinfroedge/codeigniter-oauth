<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Twitter
{
	/*
	* The Oauth Object
	*/
	public $oauth;

	/*
	* The Request Token Endpoint
	*/    
    private $_request_token_endpoint = 'https://api.twitter.com/oauth/request_token';
    
	/*
	* The Authentication Endpoint
	*/      
    private $_authenticate_endpoint = 'https://api.twitter.com/oauth/authenticate';
    
    /*
    * The Access Endpoint
    */
    private $_access_endpoint = 'https://api.twitter.com/oauth/access_token';

	/*
	* The config array
	*/
	private $_config;    

    public function __construct($oauth)
    {
		$this->oauth = $oauth;
		$this->_config = $this->oauth->ci->config->item('twitter', 'oauth');
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
            "oauth_consumer_key" => $this->_config['consumer_key'],
            "oauth_signature_method" => "HMAC-SHA1"
        );
        
		parse_str($this->oauth->make_request($params, 'GET', $this->_request_token_endpoint, $this->_config['key_secret']), $token);
		redirect($this->_authenticate_endpoint.'?oauth_token='.$token['oauth_token']);
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
    		'oauth_consumer_key' => $this->_config['oauth_consumer_key'],
    		'oauth_nonce' => time(),
    		'oauth_signature_method' => 'HMAC-SHA1',
    		'oauth_token' => $token,
    		'oauth_timestamp' => time(),
    		'oauth_verifier' => $verifier,
    		'oauth_version' => '1.0'
		);
		
		$request = $this->oauth->make_request($params, 'POST', $this->_access_endpoint, $this->_config['key_secret']);
		
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

}