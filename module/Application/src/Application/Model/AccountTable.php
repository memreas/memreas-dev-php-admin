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


class AccountTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAllCondition($where=null, $order=null, $count=null, $offset=null)
    {
        
//        $resultSet = $this->tableGateway->select($where);
       $select = new Select;
       $table=$this->tableGateway->getTable();
        $select->from($table);
        if($where!=null)
            $select->where($where);
        if($order!=null)
        $select->order($order);
        if($count!=null)
            $select->limit($count);
        if($offset!=null)
        $select->offset($offset);
        
 		$resultSet = $this->tableGateway->selectWith($select);
//        echo $select->getSqlString()."\n <pre>";        print_r($resultSet);
        $resultSet->buffer();
//        $resultSet->next();
        return $resultSet;
    }
    public function fetchAll(){

        $resultSet = $this->tableGateway->select();
        $resultSet->buffer();
        $resultSet->next();
          return $resultSet;
    }
   /* public function getAccount($id)
    {
        $row = $this->tableGateway->select(array('account_id' => $id));
       
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        
        return $row->current();
    }*/
    public function getAccount($user_id)
    {
         
      $select = $this->tableGateway->getSql()->select(); 
        // $select->join(array('u' => 'jhon.user'), "u.user_id = account.user_id", array('username', 'profile_photo'),'left'); 
      $select->where(array("user_id"=>$user_id)); 
      
               
        $resultSet = $this->tableGateway->selectWith($select);
             // echo  $select->getSqlString();

        $resultSet->buffer();
        return $resultSet->current();
        
  }

  public function getTransaction($where)
    {
         
      $select = $this->tableGateway->getSql()->select(); 
      $select->join('transaction', "transaction.account_id = account.account_id", array('*'),'left'); 
      $select->where(array('transaction.account_id'=>$where)); 
      
               
        $results = $this->tableGateway->selectWith($select);
      //echo  $select->getSqlString();
       // echo '<pre>';print_r($results);exit;
        
        $results->buffer();       

        return $results;
        
         
       
        
    }
    
     public function saveAccount($data)
    {
         //echo '<pre>'; print_r($data);
         $data=$this->validate($data);
        $id = $data['account_id'];
        if (empty($id)) {
            $data['create_date']=strtotime(date('Y-m-d'));
            $this->tableGateway->insert($data);
            return true;
        } else {
            if ($this->getAccount($id)) {
                $this->tableGateway->update($data, array('account_id' => $id));
                return true;
            } else {
                throw new \Exception('User does not exist');
            }
        }
    }

     public function validate($data)
    {
     $data['friends_can_post']= isset($data['friends_can_post']) ? filter_var($data['friends_can_post'], FILTER_VALIDATE_BOOLEAN):  0;
     $data['friends_can_share'] =   isset($data['friends_can_share'])?filter_var($data['friends_can_share'], FILTER_VALIDATE_BOOLEAN): 0;
     $data['public']= isset($data['public'])?  filter_var($data['public'], FILTER_VALIDATE_BOOLEAN): 0;

     
     if (!empty($data['viewable_from'])){
$data['viewable_from']= strtotime($data['viewable_from']);
            }else{
                unset($data['viewable_from']);
        }
        if (!empty($data['viewable_to'])){
$data['viewable_to']= strtotime($data['viewable_to']);
            }else{
                unset($data['viewable_to']);
        }
        
        if (!empty($data['self_destruct'])){
$data['self_destruct']= strtotime($data['self_destruct']);
            }else{
                unset($data['self_destruct']);
        }
        
        return $data;
    }

    public function deleteAccount($id)
    {
        $this->tableGateway->delete(array('account_id' => $id));
    }
    
    public function getAccountMedia($account_id){
       $select = new Select;
       $table=$this->tableGateway->getTable();
             $select = $this->tableGateway->getSql()->select();

       // $select->from(array('e'=> $table));
                 $select->join(array("em"=>"feedback_media"),
                          'feedback.feedback_id=em.feedback_id',array(),'left')
                    ->join(array("m"=>"media"),
                          'm.media_id=em.media_id',array('media_id','user_id', 'is_profile_pic', 'sync_status', 'metadata'),'left')
                   ->where(array('feedback.feedback_id'=>$feedback_id));            
           // $statement = $this->tableGateway->getAdapter()->createStatement();
        //$select->prepareStatement($this->tableGateway->getAdapter(), $statement);
        //$data=$statement->execute();
                 $data = $this->tableGateway->selectWith($select);

//            $sql = $select->__toString();
////                        
  //   echo $select->getSqlString()."\n <pre>";        print_r($data->current());exit;
            return $data;
    }

    
}