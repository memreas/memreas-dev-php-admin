<?php
// ///////////////////////////////
// Author: John Meah
// Copyright memreas llc 2013
// ///////////////////////////////
namespace Application\Model;

class MemreasConstants {
	
const MEMREAS_WS = "https://memreasdev-ws1.memreas.com";
  //const MEMREAS_WS = "http://ws/";
const ORIGINAL_URL = "https://memreasdev-ws1.memreas.com/";

	
	const CLOUDFRONT_DOWNLOAD_HOST = 'https://d3sisat5gdssl6.cloudfront.net/';
    const S3HOST = 'https://s3.amazonaws.com/';
	const S3BUCKET = "memreasdevsec";
	const TOPICARN = "arn:aws:sns:us-east-1:004184890641:us-east-upload-transcode-worker-int";
	const SIGNURLS = true;
	const EXPIRES = 36000;
	const ADMIN_EMAIL ='admin@memreas.com';
	const NUMBER_OF_ROWS ='15';



	
}