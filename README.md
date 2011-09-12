# Codeigniter Oauth

Helps you work with different Oauth providers to authorize users with your application with a single input and response format, allowing you to pass the authentication mechanism as a variable and simplify your underlying implementation.

Note that this spark ONLY provides the authorization mechanism.  MAKE SURE you put your specific implementation details in the config file.

## Currently Supported

Facebook and Twitter are currently supported.

## TODO

This spark is a work in progress.  Things to be added:

- Lots of refactoring
- Responses in lang file (sounds like an easy pull request to me!)
- More Oauth providers

## Installing

Available via Sparks.  For info about how to install sparks, go here: http://getsparks.org/install

You can then load the spark with this:

```php
$this->load->spark('codeigniter-oauth/');
```

## Usage Example

```php
$starting_url = 'welcome';		
		
$seg = $this->uri->segment(1);
if($seg === $starting_url)
{	
	$type = 'twitter';			
	$this->oauth->authorize($type);									
}
else
{
	$result = $this->oauth->authorize_result($type);
	if($result->status === 'success')
	{
		(isset($result->token2))
		? $get_auth = $this->oauth->access($type, $result->token, $result->token2)
		: $get_auth = $this->oauth->access($type, $result->token);
				
		var_dump($get_auth);exit;
	}
	else
	{
		var_dump($result);
	}
}
```