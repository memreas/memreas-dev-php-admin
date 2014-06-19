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
        $url = '/memreas/img/profile-pic.jpg';
        if (! empty ( $json_array ['S3_files'] ['path'] )){
            $url = MemreasConstants::CLOUDFRONT_DOWNLOAD_HOST . $json_array ['S3_files'] ['path'];
        }
        return $url ;
    }
    public function EventPic($metadata="") {
        $json_array = json_decode ( $metadata, true );
        $url = '/memreas/img/small-pic-3.jpg';
        if (! empty ( $json_array ['S3_files'] ['path'] )){
            $url = MemreasConstants::CLOUDFRONT_DOWNLOAD_HOST . $json_array ['S3_files'] ['path'];
        }
        return $url;
    }
   

    
}