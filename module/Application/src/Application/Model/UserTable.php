<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

//For join tables
use Zend\Db\Sql\Sql;
//For condtion statment
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet;
use Zend\Db\Sql\Select;
use Application\Model\MUUID;



class UserTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll($where=null, $order_by=null, $order=null)
    { 
     $select = $this->tableGateway->getSql()->select();
         if(!empty($order_by))  $select->order($order_by . ' ' . $order);
         if(!empty($where))  $select->where($where);
         //echo $select->getSqlString();
    $resultSet = $this->tableGateway->selectWith($select);

        $resultSet->buffer();
        //$resultSet->next();
        return $resultSet;
    }
    
    public function FetchAdmins($where, $order_by, $order)
    {
         
      $select = $this->tableGateway->getSql()->select();
             $select->join('media', new \Zend\Db\Sql\Expression('media.user_id = user.user_id AND media.is_profile_pic = 1'), array('metadata'),'left'); 
      $select->where('user.role != 2'); 
      if(!empty($order_by))  $select->order($order_by . ' ' . $order);
         if(!empty($where))  $select->where($where);
    //echo $select->getSqlString();
      $results = $this->tableGateway->selectWith($select);
      $results->buffer();
        return $results;
        
      }
	  
	   public function adminLog($log_id =0)
    {
         
      $select = $this->tableGateway->getSql()->select();
      
      $select->where('user.role != 2'); 
	  
   //  $select->join('account', "user.user_id = account.user_id", array('username', 'account_id'),'left'); 
     $select->join('admin_log', new \Zend\Db\Sql\Expression('admin_log.admin_id = user.user_id')); 
     
     if(!empty($log_id)){
          $select->where(array('admin_log.log_id = ?'=>$log_id ));
          
     }
          $results = $this->tableGateway->selectWith($select);
        $results->buffer();
        if(!empty($log_id)){
          $select->where(array('admin_log.log_id = ?'=>$log_id ));
         return $results->current();
     }
        return $results;
        }

        

    public function getUser($id)
    {
        
        $rowset = $this->tableGateway->select(array('user_id' => $id));
        $row = $rowset->current();
         
        return $row;
    }
    
    
    public function addAdmin($data)
    {
        $this->tableGateway->insert($data);
    }

    public function saveUser(User $user)
    {
//        (isset($user->user_id)) ? $data['user_id']= $user->user_id : null;
        (isset($user->database_id)) ? $data['database_id']= $user->database_id : null;
        (isset($user->username)) ? $data['username']= $user->username : null;
        (isset($user->password)) ? $data['password']= $user->password : null;
        (isset($user->email_address)) ? $data['email_address']= $user->email_address : null;
        (isset($user->role)) ? $data['role']= $user->role : null;
        (isset($user->profile_photo)) ? $data['profile_photo']= $user->profile_photo : null;
        (isset($user->facebook_username)) ? $data['facebook_username']= $user->facebook_username : null;
        (isset($user->twitter_username)) ? $data['twitter_username']= $user->twitter_username : null;
        (isset($user->disable_account)) ? $data['disable_account']= $user->disable_account : null;
        (isset($user->create_date)) ? $data['create_date']= $user->create_date : null;
        (isset($user->update_time)) ? $data['update_time']= strtotime(date('Y-m-d')) : strtotime(date('Y-m-d'));

        $id = $user->user_id;
        if (empty($id)) {
            $uuid = MUUID::fetchUUID ();
            $data['user_id']=$uuid;
            $data['create_date']=strtotime(date('Y-m-d'));
            $x = $this->tableGateway->insert($data);
             if($x){
                return $uuid;
            }
            return false;
        } else {
            if ($this->getUser($id)) {
                $this->tableGateway->update($data, array('user_id' => $id));
                return true;
            } else {
                throw new \Exception('User does not exist');
            }
        }
    }

    public function deleteUser($id)
    {
        $this->tableGateway->delete(array('user_id' => $id));
    }
    
        public function getUserByUsername($username)
    {
        $rowset = $this->tableGateway->select(array('username' => $username));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $username");
        }
        return $row;
    }
    
    public function getUserByRole($role)
    {
        $rowset = $this->tableGateway->select(array('role' => $role));
        return $rowset;
    }
    
    public function getUserBy($where)
    {
        $rowset = $this->tableGateway->select($where);
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row ");
        }
        return $row;
    }
    
     public function updateUser($data,$id)
    {
         
          if ($this->getUser($id)) {
                $this->tableGateway->update($data, array('user_id' => $id));
                return true;
            }   
                
        
    }

      public function getUserData($where, $order_by =null,  $order=null)
    {
        $select = $this->tableGateway->getSql()->select();
             $select->join('media', new \Zend\Db\Sql\Expression('media.user_id = user.user_id AND media.is_profile_pic = 1'), array('metadata'),'left'); 
      //$select->where('user.role != 2'); 
      if(!empty($order_by))  $select->order($order_by . ' ' . $order);
         if(!empty($where))  $select->where($where);
    //echo $select->getSqlString();
      $results = $this->tableGateway->selectWith($select);
      $results->buffer();
        return $results->current();
        
    }

    
     
    public function getUserRegisterCount($time='')
    {
        
        
        $select = new Select;
        $select->from($this->tableGateway->getTable());
        $select->where("create_date >= $time");
        $select->columns(array('num' => new \Zend\Db\Sql\Expression('COUNT(user_id)')));
        
        $results = $this->tableGateway->selectWith($select);
        //print_r($results); exit;
        //print_r($select->getSqlString());
        $t=$results->current()->num;
        return $t;
    }
    
     

    public function planUsage($value='')
    {
        $pastDay = date('Y-m-d', strtotime(' -1 day'));
        $pastWeek = date('Y-m-d', strtotime(' -1 week'));
        $pastMonth = date('Y-m-d', strtotime(' +1 month'));
        $totalRegisterUser = 0;
        $totalInvite = 0;
    }
    public function InviteSummary($value='')
    {

        $pastDay = date('Y-m-d', strtotime(' -1 day'));
        $pastWeek = date('Y-m-d', strtotime(' -1 week'));
        $pastMonth = date('Y-m-d', strtotime(' +1 month'));
        $totalRegisterUser = 0;

        $totalInvite = 0;
    }
    
    public function isExist($where){
         $select = new Select;
        $select->from($this->tableGateway->getTable())
        ->where->NEST->like('username', $where['username'])->or->like('email_address',$where['email_address'])
                ->UNNEST;
        
       $statement = $this->tableGateway->getAdapter()->createStatement();
        $select->prepareStatement($this->tableGateway->getAdapter(), $statement);

        $resultSet = new ResultSet\ResultSet();
        $resultSet->initialize($statement->execute());
        
//        echo "<pre>";echo $select->getSqlString();        
//        print_r($resultSet->current());
//        exit(0);
//        
        if($resultSet->current())
            return true;
        else
            return false;
    }
    
    
}