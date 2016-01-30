<?php
/**
 * Copyright (C) 2015 memreas llc. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */
namespace Application\Model;

class MemreasConstants {
	const MEMREAS_WS = "https://memreasdev-wsj.memreas.com";
	const ORIGINAL_URL = "https://memreasdev-wsj.memreas.com/";
	
	// Redis constant section
	const REDIS_SERVER_ENDPOINT = "10.179.214.247";
	const REDIS_SERVER_USE = true;
	const REDIS_SERVER_SESSION_ONLY = true;
	const REDIS_SERVER_PORT = "6379";
	const REDIS_CACHE_TTL = 3600; // 1 hour
	                              
	// s3 section
	const S3BUCKET = "memreasdevsec";
	const S3_APPKEY = 'AKIAJMXGGG4BNFS42LZA';
	const S3_APPSEC = 'xQfYNvfT0Ar+Wm/Gc4m6aacPwdT5Ors9YHE/d38H';
	const S3HLSBUCKET = "memreasdevhlssec";
	const CLOUDFRONT_DOWNLOAD_HOST = 'https://d3sisat5gdssl6.cloudfront.net/';
	const CLOUDFRONT_HLSSTREAMING_HOST = 'https://d2cbahrg0944o.cloudfront.net/';
	const S3HOST = 'https://s3.amazonaws.com/';
	const SIGNURLS = true;
	const EXPIRES = 36000; // 10 hours
	
	// cloudfront section
	const CLOUDFRONT_DOWNLOAD_HOST = 'https://d3sisat5gdssl6.cloudfront.net/';
	const CLOUDFRONT_HLSSTREAMING_HOST = 'https://d2cbahrg0944o.cloudfront.net/';
	const CLOUDFRONT_KEY_FILE = '/key/pk-APKAISSKGZE3DR5HQCHA.pem';
	const CLOUDFRONT_KEY_PAIR_ID = 'APKAISSKGZE3DR5HQCHA';
	const CLOUDFRONT_EXPIRY_TIME = 36000; // 10 hours
	
	                       
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