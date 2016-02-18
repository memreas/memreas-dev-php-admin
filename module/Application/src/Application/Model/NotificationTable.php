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


class NotificationTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAllCondition($where=null, $order=null,$order_by=null, $count=null, $offset=null)
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
         if(!empty($order_by))  $select->order($order_by . ' ' . $order);
         if(!empty($where))  $select->where($where);
         $resultSet = new ResultSet\ResultSet();
        $resultSet->initialize($statement->execute());

//        echo $select->getSqlString()."\n <pre>";        print_r($resultSet);
        $resultSet->buffer();
//        $resultSet->next();
        return $resultSet;
    }
    public function fetchAll($where=null, $order_by=null, $order=null){
      $select = $this->tableGateway->getSql()->select();
       if(!empty($order_by))  $select->order($order_by . ' ' . $order);
         if(!empty($where))  $select->where($where);
        $resultSet = $this->tableGateway->selectWith($select);
        $resultSet->buffer();
        $resultSet->next();
          return $resultSet;
    }
    public function getEvent($id)
    {
        $rowset = $this->tableGateway->select(array('event_id' => $id));
       
        if (empty($rowset)) {
            throw new \Exception("Could not find row $id");
        }
        return $rowset->current();
    }
	 
	
	
	public function getInviteCount($time='',$type='2')
    {
        
        
        $select = new Select;
        $select->from($this->tableGateway->getTable());
        $select->where("create_time >= $time AND notification_type = $type");
        $select->columns(array('num' => new \Zend\Db\Sql\Expression('COUNT(notification_id)')));
        
        $results = $this->tableGateway->selectWith($select);
        //print_r($results); exit;
    //print_r($select->getSqlString());
        $t=$results->current()->num;
        return $t;
    }

    
	
	 
    public function getEmailInviteCount($time='')
    {
        
        
        $select = new Select;
        $select->from($this->tableGateway->getTable());
        $select->where("create_time >= $time AND notification_method = 0");
        $select->columns(array('num' => new \Zend\Db\Sql\Expression('COUNT(notification_id)')));
        
        $results = $this->tableGateway->selectWith($select);
        //print_r($results); exit;
    //print_r($select->getSqlString());
        $t=$results->current()->num;
        return $t;
    }
    
     
	
	public function update($data,$id)
    {
        
                $this->tableGateway->update($data, array('event_id' => $id));
                
        
    }

    

    public function deleteNotification($id)
    {
        $this->tableGateway->delete(array('event_id' => $id));
    }
    
     
}