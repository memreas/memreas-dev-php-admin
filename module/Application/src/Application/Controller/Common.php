<?php

namespace Application\Controller;

 use Guzzle\Http\Client;
use Application\Model\MemreasConstants;



class Common{
	public static  $url = MemreasConstants::MEMREAS_WS;	
		public static  $sid = '';	


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
            'sid' => self::$sid,
	    	)
		);
		$response = $request->send();
error_log("Inside fetch XML response ---> " . $response->getBody(true) . PHP_EOL);
error_log("Exit fetchXML".PHP_EOL);
		return $data = $response->getBody(true);
	}


}

// end class IndexController
