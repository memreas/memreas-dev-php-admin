<?php
namespace Application\View\Helper;
 use Zend\View\Helper\AbstractHelper;
use Application\Model\MemreasConstants;
  
class Mem extends AbstractHelper
{
    public $sm;
    public function __construct($sm) {
        $this->sm;
    }
    public function ProfilePic($metadata="") {
        $json_array = json_decode ( $metadata, true );
        $url = '/img/profile-pic.jpg';
        if (! empty ( $json_array ['S3_files']['thumbnails']['79x80'])){
            $url = MemreasConstants::CLOUDFRONT_DOWNLOAD_HOST . $json_array ['S3_files']['thumbnails']['79x80'];
        }
        return $url ;
    }
    public function EventPic($metadata="") {
        $json_array = json_decode ( $metadata, true );
        $url = '/img/small-pic-3.jpg';
        if (! empty ( $json_array ['S3_files'] ['path'] )){
            $url = MemreasConstants::CLOUDFRONT_DOWNLOAD_HOST . $url98x78 = $json_array ['S3_files']['thumbnails']['98x78'];
        }
        return $url;
    }
    public function showDate($date="") {
        return empty($date)?'-':date('m-d-Y',$date);
    }
    public function showFullDate($date="") {
        return empty($date)?'-':date('Y-m-d H:i:s',$date);
    }
   

    
}