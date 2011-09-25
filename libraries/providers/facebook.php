<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Facebook
{
	/*
	* The Oauth Object
	*/
	public $oauth;
	
	/*
	* The Authorization Endpoint
	*/	
	private $_authorize_endpoint = 'https://www.facebook.com/dialog/oauth?';

	/*
	* The Access Token Endpoint
	*/	
	private $_access_token_endpoint = 'https://graph.facebook.com/oauth/access_token?';
	
	/*
	* The Graph URL
	*/	
	private $_graph_url = 'https://graph.facebook.com/me?';
	
	/*
	* The config array
	*/
	private $_config;
	
	/*
	* The Constructor
	*
	* @param	object	The Oauth Object
	*/	
	public function __construct($oauth)
	{
		$this->oauth = $oauth;
		$this->_config = $this->oauth->ci->config->item('facebook', 'oauth');
	}

	/*
	* Get an authorization code from Facebook.  Redirects to Facebook, which this redirects back to the app using the redirect address you've set.
	*/	
	public function authorize()
	{
		$state = md5(uniqid(rand(), TRUE));
		$this->oauth->ci->session->set_userdata('state', $state);
			
		$params = array(
			'client_id' => $this->_config['client_id'],
			'redirect_uri' => $this->_config['redirect_uri'],
			'state' => $state,
			'scope' => 'email'
		);
		
		$url = $this->_authorize_endpoint.http_build_query($params);
		redirect($url);
	}

	/*
	* Get the authorization result
	*
	* @return array	The response
	*/	
	public function authorize_result()
	{	
		if(isset($_GET['code']))
		{
			if($this->oauth->ci->session->userdata('state') !== $_GET['state'])
			{
				return $this->oauth->response('failure', array(
						'error' => "You may be the victim of a cross-site forgery request."
					)
				);
			}
			else
			{
				return $this->oauth->response('success', array(
						'token' => $_GET['code'],
						'state' => $_GET['state']
					)
				);
			}
		}
		
		if(isset($_GET['error']))
		{
			return $this->oauth->response('failure', array(
					'error' => $_GET['error_description']
				)
			);
		}
	}

	/*
	* Get access to the API
	*
	* @param	string	The access code
	* @return	object	Success or failure along with the response details
	*/	
	public function access($code)
	{
		$params = array(
			'client_id' => $this->_config['client_id'],
			'redirect_uri' => $this->_config['redirect_uri'],
			'client_secret' => $this->_config['app_secret'],
			'code' => $code		
		);
		
		$url = $this->_access_token_endpoint.http_build_query($params);
		
		$response = file_get_contents($url);
		$params = null;
		parse_str($response, $params); 
		
		if(isset($params['error']))
		{
			return $this->oauth->response('failure', array(
					'error' => $params['error->message']
				)
			);
		}
		else
		{
			$params = http_build_query(array(
					'access_token' => $params['access_token']
				)
			);
			$graph_url = $this->_graph_url.$params;
			$user = json_decode(file_get_contents($graph_url));

			return $this->oauth->response('success', array(
					'user' => array(
						'id'		=> $user->id,
						'name'		=> $user->name,
						'username'	=> $user->username,
						'email'		=> $user->email
					)
				)
			);
		}
	}
}