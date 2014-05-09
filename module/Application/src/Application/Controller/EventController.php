<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model;
use Application\Form;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Guzzle\Http\Client;
use Application\Model\MemreasConstants;
use Application\memreas\Login;


class EventController extends AbstractActionController {

    protected $url = "http://test";
    protected $user_id;
       
    
    public function indexAction() {
        //Common::$url .= '/admin/event';
        print_r($_SESSION);exit;
        $xml = '<xml><viewevent><user_id>1</user_id><is_my_event>1</is_my_event><is_friend_event></is_friend_event><is_public_event>1</is_public_event><page>1</page><limit>2</limit></viewevent></xml>';
		$result = Common::fetchXML('viewevents',$xml);
        echo $result;exit;
        return $this->response;
  
    }
	public function addAction() {

  
    }
	public function editAction() {

  
    }
	public function deleteAction() {

  
    }
   

}

// end class IndexController
