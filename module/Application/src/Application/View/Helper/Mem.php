<?php
namespace Application\View\Helper;
 use Application\memreas\MemreasSignedURL;
 use Zend\View\Helper\AbstractHelper;

class Mem extends AbstractHelper
{
    public $sm;
    public function __construct($sm) {
        $this->sm =$sm;
        $this->signer = new MemreasSignedURL();
    }
    public function ProfilePic($metadata="") {
        $json_array = json_decode ( $metadata, true );
        $url = '/images/admin-u-profile.png';
        if (! empty ( $json_array ['S3_files']['thumbnails']['79x80'][0])){
            $url = $json_array ['S3_files']['thumbnails']['79x80'];
            $url = json_decode($this->signer->signArrayOfUrls($url));


        }
        return is_array($url)?$url[0]:$url ;
    }
    public function EventPic($metadata="") {
        $json_array = json_decode ( $metadata, true );
        $url = '/images/500Pages.png';
        if (! empty ( $json_array ['S3_files']['thumbnails']['448x306'][0])){
            $url = $json_array ['S3_files']['thumbnails']['448x306'];
			$url = json_decode($this->signer->signArrayOfUrls($url));
 		} 
        return is_array($url)?$url[0]:$url ;
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
     public function getUserTable()
    { 

        return $this->sm->get('Application\Model\UserTable');
    }
    public function getAminUserTable()
    { 

        return $this->sm->get('Application\Model\AdminUserTable');
    }
    
}