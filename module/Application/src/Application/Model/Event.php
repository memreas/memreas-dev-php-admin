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

class Event {

    public $event_id,
            $user_id,
            $name,
            $location,
            $date,
            $friends_can_post,
            $friends_can_share,
            $public,
            $viewable_from,
            $viewable_to,
            $self_destruct,
            $create_time,
            
            $update_time;
       


     
 
}

?>
