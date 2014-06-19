<?php

/**
 * Description of Users
 * @author shivani
 */

namespace Application\Model;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Feedback {

    public $feedback_id,
           $user_id,
	   $name,
	   $email,
	   $create_time,
           $message;
       

    public function exchangeArray($data) {
        $this->user_id = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->feedback_id = (isset($data['feedback_id'])) ? $data['feedback_id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->email = (isset($data['email'])) ? $data['email'] : null;
        $this->create_time = (isset($data['create_time'])) ? $data['create_time'] : null;
        $this->message = (isset($data['message'])) ? $data['message'] : null;

    }
 
}

?>
