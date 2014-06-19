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


class FeedbackTable
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
        $statement = $this->tableGateway->getAdapter()->createStatement();
        $select->prepareStatement($this->tableGateway->getAdapter(), $statement);
         $resultSet = new ResultSet\ResultSet();
        $resultSet->initialize($statement->execute());

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
    public function getFeedback($id)
    {
        $row = $this->tableGateway->select(array('feedback_id' => $id));
       
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        
        return $row->current();
    }
    public function FetchFeedDescAll()
    {
         
      $select = $this->tableGateway->getSql()->select(); 
         $select->join('user', "user.user_id = feedback.user_id", array('username', 'profile_photo'),'left'); 
        $select->join('media', new \Zend\Db\Sql\Expression('media.user_id = user.user_id AND media.is_profile_pic = 1'), array('metadata'),'left'); 
              
               
         $results = $this->tableGateway->selectWith($select);
        $results->buffer();
        return $results;
        
         
       
        
    }
    
     public function saveFeedback($data)
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

    public function deleteFeedback($id)
    {
        $this->tableGateway->delete(array('feedback_id' => $id));
    }
    
    public function getFeedbackMedia($feedback_id){
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