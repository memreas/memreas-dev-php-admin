<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

//For join tables
use Zend\Db\Sql\Sql;
//For condtion statment
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet;
use Zend\Db\Sql\Select;

 use Zend\Stdlib\Hydrator\ClassMethods;
 use Zend\Stdlib\Hydrator\ObjectProperty;

use Zend\Db\ResultSet\HydratingResultSet;


class FriendTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

   
    
    public function getFriend($id)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->join('media', new \Zend\Db\Sql\Expression('media.user_id = feedback.user_id AND media.is_profile_pic = 1'), array('metadata'),'left');
        $select->where(array('feedback_id' =>$id)); 
        
       
       
        $results = $this->tableGateway->selectWith($select);
        $results->buffer();
        return $results->current();
    }
    public function FetchFeedDescAll($where=null, $order_by=null, $order=null)
    {
         
      $select = $this->tableGateway->getSql()->select(); 
         $select->join('user', "user.user_id = feedback.user_id", array('username', 'profile_photo'),'left'); 
        $select->join('media', new \Zend\Db\Sql\Expression('media.user_id = user.user_id AND media.is_profile_pic = 1'), array('metadata'),'left'); 
              
        if(!empty($order_by))  $select->order($order_by . ' ' . $order);
        if(!empty($where))  $select->where($where);
    
         $results = $this->tableGateway->selectWith($select);
        $results->buffer();
        return $results;
        
         
       
        
    }
    
     public function saveFriends($data)
    {
         //echo '<pre>'; print_r($data);
         $data=$this->validate($data);
        $id = $data['feedback_id'];
        if (empty($id)) {
            $data['create_date']=strtotime(date('Y-m-d'));
            $this->tableGateway->insert($data);
            return true;
        } else {
            if ($this->getFeedback($id)) {
                $this->tableGateway->update($data, array('feedback_id' => $id));
                return true;
            } else {
                throw new \Exception('User does not exist');
            }
        }
    }

     
    public function deleteFeedback($id)
    {
        $this->tableGateway->delete(array('feedback_id' => $id));
    }

    public function getOtherInviteCount($time='',$network='memreas')
    {
        
        
        $select = new Select;
        $select->from($this->tableGateway->getTable());
        $select->join('event_friend', new \Zend\Db\Sql\Expression('event_friend.friend_id = friend.friend_id'), array());
        $select->where(array(
          'create_date>=?' => $time,
                    'network=?' => $network,

          ));
       $select->columns(array('num' => new \Zend\Db\Sql\Expression('COUNT("*")')));
        
        $results = $this->tableGateway->selectWith($select);
       
        $t=$results->current()->num;
        //print_r($t);exit;
       
        return $t;
    }
    
    
    
}