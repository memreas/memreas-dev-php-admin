<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway; 
use Application\Model\MUUID;




class AdminLogTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll($where=null, $order=null, $count=null,$order_by=null, $offset=null)
    {
         $select = $this->tableGateway->getSql()->select();
       if(!empty($order_by))  $select->order($order_by . ' ' . $order);
         if(!empty($where))  $select->where($where);
        $resultSet = $this->tableGateway->select($where);
                $resultSet->buffer();

       
        return $resultSet;
    }

    public function saveLog($data)
    {
        //print_r($data);
        
                        $uuid = MUUID::fetchUUID ();
            $data['log_id']=$uuid;
            $data['created']=strtotime(date('Y-m-d H:i:s'));
            $this->tableGateway->insert($data);
            return true;
        
    }
    

    public function delete($where)
    {
        $this->tableGateway->delete($where);
        return true;
    }
    
}