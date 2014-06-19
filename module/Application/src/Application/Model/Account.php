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

class Account {

    public  $account_id,
            $user_id,
            $username,
            $account_type,
            $balance,
            $create_time,
            $update_time;
            
       
}

?>
