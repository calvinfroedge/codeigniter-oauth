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
		$this->ci->load->library('email');
		$this->ci->load->helper('cookie');
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
		$module_location = dirname(__FILE__).'/types/'.$module.'.php';
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
		
		if(isset($details['error'])) $return['error'] = $details['error'];
		
		if(isset($details['user'])) $return['user'] = $details['user'];
		
		return (object) $return;
	}
}