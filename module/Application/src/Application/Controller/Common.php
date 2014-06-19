<?php

namespace Application\Controller;

 use Guzzle\Http\Client;



class Common{
	public static  $url = "http://test";
        
        
    public static function fetchXML($action='', $xml='') {
		$guzzle = new Client();

error_log("Inside fetch XML request url ---> " . self::$url . PHP_EOL);
error_log("Inside fetch XML request action ---> " . $action . PHP_EOL);
error_log("Inside fetch XML request XML ---> " . $xml . PHP_EOL);
        $request = $guzzle->post(
			self::$url,
			null,
			array(
			'action' => $action,
			//'cache_me' => true,
    		'xml' => $xml,
            'PHPSESSID' => empty($_COOKIE[session_name()])?'':$_COOKIE[session_name()],
	    	)
		);
		$response = $request->send();
error_log("Inside fetch XML response ---> " . $response->getBody(true) . PHP_EOL);
error_log("Exit fetchXML".PHP_EOL);
		return $data = $response->getBody(true);
	}
	
}

// end class IndexController
