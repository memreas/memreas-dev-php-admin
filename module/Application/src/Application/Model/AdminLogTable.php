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

    public function fetchAll($where=null, $order=null, $count=null, $offset=null)
    {
        
        $resultSet = $this->tableGateway->select($where);
       
        return $resultSet;
    }

    public function saveLog($data)
    {
        //print_r($data);
        $result = $this->tableGateway->select(array('log_type'=>$data['log_type'],'entity_id' => $data['entity_id']));
        $rec= $result->current();
        if (empty($rec->log_id)) {
                        $uuid = MUUID::fetchUUID ();
            $data['log_id']=$uuid;
            $data['created']=strtotime(date('Y-m-d H:i:s'));
            $this->tableGateway->insert($data);
            return true;
        } else {
                $data['created']=strtotime(date('Y-m-d H:i:s'));
                $this->tableGateway->update($data, array('log_id' => $rec->log_id));
                return true;
  
        }
    }
    

    public function delete($where)
    {
        $this->tableGateway->delete($where);
        return true;
    }
    
}