<?php
namespace Application\View\Helper;
 use Zend\View\Helper\AbstractHelper;
use Application\Model\MemreasConstants;
use Application\View\Helper\MemreasSignedURL;

class Mem extends AbstractHelper
{
    public $sm;
    public function __construct($sm) {
        $this->sm;
        $this->signer = new MemreasSignedURL();
    }
    public function ProfilePic($metadata="") {
        $json_array = json_decode ( $metadata, true );
        $url = '/img/profile-pic.jpg';
        if (! empty ( $json_array ['S3_files']['path'])){
            $url = MemreasConstants::CLOUDFRONT_DOWNLOAD_HOST . $json_array ['S3_files']['path'];
            $url = json_decode($this->signer->signArrayOfUrls($url));
            $url = $url[0];
        }
        return $url ;
    }
    public function EventPic($metadata="") {
        $json_array = json_decode ( $metadata, true );
        $url = '/img/small-pic-3.jpg';
        if (! empty ( $json_array ['S3_files'] ['path'] )){
            $url = MemreasConstants::CLOUDFRONT_DOWNLOAD_HOST .  $json_array ['S3_files']['path'];
			$url = json_decode($this->signer->signArrayOfUrls($url));
            $url = $url[0];
 } 
        return $url;
    }
    public function showDate($date="") {
        return empty($date)?'-':date('m-d-Y',$date);
    }
    public function showFullDate($date="") {
        return empty($date)?'-':date('Y-m-d H:i:s',$date);
    }
   
	 /**
     * Returns the formatted size
     *
     * @param  int $size
     * @return string
     */
    public function toByteString($size)
    {
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        for ($i=0; $size >= 1024 && $i < 9; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $sizes[$i];
    }
     public function calPercentge($n=1, $d=1)
    {$r=0;
        if($n!=0 && $d!= 0 ){
            $r = $n/$d;
            $r = $r*100;
            $r= ceil($r);
        }

        return $r;
    }
    
}