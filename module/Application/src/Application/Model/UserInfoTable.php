<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

//For join tables
use Zend\Db\Sql\Sql;
//For condtion statment
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet;
use Zend\Db\Sql\Select;


class UserInfoTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll($where=null)
    {
        
        $resultSet = $this->tableGateway->select($where);
       $resultSet->buffer();
        return $resultSet;
    }
public function userInfoAll($where=null, $order_by=null, $order=null)
{
     $select = $this->tableGateway->getSql()->select();
        // $select->from('event'); 
        //$select->columns(array('event_id')); 
      //  $select->join('memreasintdb.user', "user.user_id = user_info.user_id", array('username', 'profile_photo'),'left'); 
        //$select->join('media', new \Zend\Db\Sql\Expression('media.user_id = user.user_id AND media.is_profile_pic = 1'), array('metadata'),'left'); 
         if(!empty($order_by))  $select->order($order_by . ' ' . $order);
         $select->where('user_id !="total-s3"');
         if(!empty($where))  $select->where($where);

      //print_r($select->getSqlString());exit;
         $results = $this->tableGateway->selectWith($select);
        $results->buffer();
         return $results;
        
}

   
   
    public function getUserInfo($id)
    {
        $rowset = $this->tableGateway->select(array('user_id' => $id));
        $row = $rowset->current();
        
        return $row;
    }
    public function totalPercentUsed($id)
    {
        $rowset = $this->tableGateway->select(array('user_id' => $id));
        $row = $rowset->current();
        $allowed_size = $row -> allowed_size;
        $data_usage = $row -> data_usage;
        $totalused = $data_usage*100/allowed_size;

        return $totalused;
    }

   public function saveUserInfo($data)
    {
 
          $id = $data['user_id'];
  
            if ($this->getUserInfo($id)) { 
                 $data['updated']=strtotime(date('Y-m-d'));
                 $this->tableGateway->update($data, array('user_id' => "$id"));
 
                 
                return true;
            } else {

            $data['created']=strtotime(date('Y-m-d'));
            $this->tableGateway->insert($data);
            return true;
        
            }
        }
  
    
}