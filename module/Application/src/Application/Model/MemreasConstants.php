<?php
/**
 * Copyright (C) 2015 memreas llc. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */
namespace Application\Model;

class MemreasConstants {
	const MEMREAS_WS = "https://memreasprod-ws.memreas.com";
	
	// Redis constant section
	const REDIS_SERVER_ENDPOINT = "10.154.58.83";
	const REDIS_SERVER_USE = true;
	const REDIS_SERVER_SESSION_ONLY = true;
	const REDIS_SERVER_PORT = "6379";
	const REDIS_CACHE_TTL = 3600; // 1 hour
	const REDIS_CACHE_USER_TTL = 300; // 5 minutes
	
	// s3 section
	const S3_APPKEY = 'AKIAISDIQFVJMWFYXCIA';
	const S3_APPSEC = 'eM5HG4MbYhkW1Jz1RWIdMapo2s+DbB+KnkhzTt91';
	const S3BUCKET = "memreasprodsec";
	const S3HLSBUCKET = "memreasprodhlssec";
	const S3HOST = 'https://s3.amazonaws.com/';
	const SIGNURLS = true;
	const EXPIRES = 36000; // 10 hours
	
	// cloudfront section
	const CLOUDFRONT_DOWNLOAD_HOST = 'https://d3sisat5gdssl6.cloudfront.net/';
	const CLOUDFRONT_HLSSTREAMING_HOST = 'https://d2cbahrg0944o.cloudfront.net/';
	const CLOUDFRONT_KEY_FILE = '/key/pk-APKAISSKGZE3DR5HQCHA.pem';
	const CLOUDFRONT_KEY_PAIR_ID = 'APKAISSKGZE3DR5HQCHA';
	const CLOUDFRONT_EXPIRY_TIME = 36000; // 10 hours
	
	//Plan size constants
	const _2GB = '2000000000';
	const _10GB = '10000000000';
	const _50GB = '50000000000';
	const _100GB = '100000000000';
	                       
	// admin section
	const ADMIN_EMAIL = 'admin@memreas.com';
	const NUMBER_OF_ROWS = '15';
	
	public static function fetchAWS() {
		$sharedConfig = [
				'region' => 'us-east-1',
				'version' => 'latest',
				'credentials' => [
						'key' => self::S3_APPKEY,
						'secret' => self::S3_APPSEC
				]
		];
	
		return new \Aws\Sdk ( $sharedConfig );
	}
	
}