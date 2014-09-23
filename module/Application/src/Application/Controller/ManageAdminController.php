<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\Common;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
// use DoctrineORMModule\Form\Annotation\AnnotationBuilder;
use Zend\Form\Annotation;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Form;
use Zend\Form\Element;
use Application\Model\User;

class ManageAdminController extends AbstractActionController {

    public $messages = array();
    public $status;
    protected $AdminUserTable;

    public function getAdminUserTable() {
        if (!$this->AdminUserTable) {
            $sm = $this->getServiceLocator();
            $this->AdminUserTable = $sm->get('Application\Model\AdminUserTable');
        }
        return $this->AdminUserTable;
    }

    protected $accountTable;

    public function getAccountTable() {
        if (!$this->accountTable) {
            $sm = $this->getServiceLocator();
            $this->accountTable = $sm->get('Application\Model\AccountTable');
        }
        return $this->accountTable;
    }

    protected $adminLogTable;

    public function getAdminLogTable() {
        if (!$this->adminLogTable) {
            $sm = $this->getServiceLocator();
            $this->adminLogTable = $sm->get('Application\Model\AdminLogTable');
        }
        return $this->adminLogTable;
    }

    public function indexAction() {
        $order_by = $this->params()->fromQuery('order_by', 0);
        $order = $this->params()->fromQuery('order', 'DESC');
        $q = $this->params()->fromQuery('q', 0);
        $where = array();
        $column = array('username', 'role', 'create_date');
        $url_order = 'DESC';
        if (in_array($order_by, $column))
            $url_order = $order == 'DESC' ? 'ASC' : 'DESC';

        try {
            //$account = $this->getAccountTable()->getAccount(array('user_id'=>$id));
            // $account_id =   $account;
            //echo '<pre>'; print_r($account->account_id); exit;


            $admin = $this->getAdminUserTable()->FetchAdmins($where, $order_by, $order);

            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($admin);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
            $paginator->setItemCountPerPage(10);
        } catch (Exception $exc) {

            return array();
        }
        return array('paginator' => $paginator, 'admin_total' => count($admin),
            'order_by' => $order_by, 'order' => $order, 'q' => $q, 'page' => $page, 'url_order' => $url_order);
    }

     
   
    public function addadminAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $this->params()->fromPost();
            $where['email_address'] = $postData['email_address'];
                    $where['username'] = $postData['username'];
                    $userExist = $this->getAdminUserTable()->isExist($where);

                   if ($userExist) {
                        $this->messages[] = 'User Name or email already exist';
                        $this->status = 'error';
                    } else {

                        $user['username'] = $postData['username'];
            $user['email_address'] = $postData['email_address'];
            $user['password'] = $postData['password'];
            $user['disable_account'] = 1;
            $user['role'] = $postData['role'];

            $user_id=$this->getAdminUserTable()->saveUser($user);

            $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_user_added', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $user_id));


            $this->messages[] = 'Data Added sucessfully';
            $to[] = $postData['email_address'];
				        $viewVar = array (
						        'email'    => $postData['email_address'],
						        'username' => $postData['username'],
						        'passwrd'  => $postData['password']
				        );
				        $viewModel = new ViewModel ( $viewVar );
				        $viewModel->setTemplate ( 'email/register' );
				        $viewRender = $this->getServiceLocator()->get ( 'ViewRenderer' );
				        $html = $viewRender->render ( $viewModel );
				        $subject = 'Welcome to Event App';
				        if (empty ( $aws_manager ))
					        $aws_manager = new AWSManagerSender ( $this->getServiceLocator() );
				        $aws_manager->sendSeSMail ( $to, $subject, $html ); //Active this line when app go live
				        $this->status = $status = 'Success';
				        $message = "Welcome to Event App. Your profile has been created.";
                    }
            
        }

        return array('status'=>$this->status,'messages'=>$this->messages);
    }

    public function editAction() {
        $postData = array();
        if ($this->request->isPost()) {
            $user_id = $this->params()->fromPost('id');
            $user = $this->getAdminUserTable()->getUser($user_id);


            if (empty($user_id) or empty($user)) {
                $this->messages[] = 'Admin Not Found';
            } else {
                $postData = $this->params()->fromPost();
                if ($user['username'] != $postData['username'] || $user['email_address'] != $postData['email_address']) {
                    //$where['email_address'] = $postData['email_address'];
                    //$where['username'] = $postData['username'];
                    //$userExist = $this->getAdminUserTable()->isExist($where);

                  /*  if ($userExist) {
                        $this->messages[] = 'User Name or email already exist';
                        $this->status = 'error';
                    } else {

                        $user['username'] = $postData['username'];
                        $user['email_address'] = $postData['email_address'];
                    }*/
                }
                if (!empty($postData['role'])) {
                    $user['role'] = $postData['role'];
                }

                $user['update_time'] = time();
                //$user['disable_account'] = $postData['disable_account'];
                
                // Save the changes
                if ($this->status != 'error') {
                    $this->getAdminUserTable()->saveUser($user);
                    $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_info_updated', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $user_id));
                    $this->messages[] = 'Data Update sucessfully';
                    $user = $this->getAdminUserTable()->getUser($user_id);
                }
            }
        } else {
            $id = $this->params()->fromRoute('id');
            $user = $this->getAdminUserTable()->getUser($id);
         }



        return array('admin' => $user, 'messages' => $this->messages, 'status' => $this->status, 'post' => $postData);
    }
    
    

    

    public function deactivateAction() {
      $vdata=array();
        $request = $this->getRequest();
        if ($request->isPost()) {
             $id = $this->params()->fromPost('id');
             if(empty($postdata['reason'])){
              $status='error';
            }

                
                $this->getAdminUserTable()->updateUser(array('disable_account' => '1'), $id);
                $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_deactivated', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $id));
               
                $this->messages[] = 'User Dactivated';
                $this->status = 'success';
 
            // Redirect to list of albums
        }else{
            $id = $this->params()->fromRoute('id', 0);
             
        }
        error_log('user-id---'.$id);
         $user = $this->getAdminUserTable()->getUser($id);
            $vdata['user'] = $user;
            $vdata['messages']= $this->messages;
            $vdata['status'] = $this->status;
        return $vdata;
    }
    
    public function activateAction() {
      $vdata=array();
        $request = $this->getRequest();
        if ($request->isPost()) {
             $id = $this->params()->fromPost('id');
             if(empty($postdata['reason'])){
              $status='error';
            }

                $this->getAdminUserTable()->updateUser(array('disable_account' => '0'), $id);
                $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_activate', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $id));
                
                $this->messages[] = 'User activated';
                $this->status = 'success';
 
        }else{
            $id = $this->params()->fromRoute('id', 0);
        }
         $user = $this->getAdminUserTable()->getUser($id);
            $vdata['user'] = $user;
            $vdata['messages']= $this->messages;
            $vdata['status'] = $this->status;
        return $vdata;
    }
    
    
     public function viewAction() {              
        $user_id = $this->params()->fromRoute('id');
        $page = $this->params()->fromQuery('page', 1);
        $users_log =  $this->getAdminLogTable()->fetchAll(array('admin_id' =>$user_id));
        //$this->getAdminLogTable()->saveLog(array('log_type'=>'admin_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$user_id));
 	//$admin = $this->getAdminUserTable()->adminLog($user_id);
             

         // echo '<pre>'; print_r($users_log); exit;
                  //  $users = $this->getAdminUserTable()->adminLog();

             $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($users_log);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(10);

        
        
        return array('paginator' => $paginator, 'row' => $users_log );
        
  
    }
    
    public function detailAction() {
      $log_id = $this->params()->fromRoute('id');
try {
      	$log_info = $this->getAdminUserTable()->adminLog($log_id);
      $this->getAdminLogTable()->saveLog(array('log_type'=>'admin_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$log_id));

} catch (\Exception $e) {
   $admin = null;
}
	    
             //echo '<pre>'; print_r($admin); exit;
	   return array('row' =>$log_info);

  
    }

}

// end class IndexController
