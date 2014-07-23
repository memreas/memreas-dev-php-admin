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
public $status ;

protected $userTable;


       
     public function getUserTable() {
        if (!$this->userTable) {
            $sm = $this->getServiceLocator();
            $this->userTable = $sm->get('Application\Model\userTable');
        }
        return $this->userTable;
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
            $order    = $this->params()->fromQuery('order', 'DESC');
            $q    = $this->params()->fromQuery('q', 0);
            $where =array();
             $column = array('username','role','create_date');
             $url_order = 'DESC';
  if (in_array($order_by, $column))
    $url_order = $order == 'DESC' ? 'ASC' : 'DESC';

                 try {
        //$account = $this->getAccountTable()->getAccount(array('user_id'=>$id));

        // $account_id =   $account;
         //echo '<pre>'; print_r($account->account_id); exit;


        $admin = $this->getUserTable()->FetchAdmins($where, $order_by, $order);
        
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
          'order_by'=>$order_by,'order' => $order,'q'=>$q,'page' => $page, 'url_order'=>$url_order);

    }
	 
	

function validate(){
  $result = true ;
return $result;
}
    
	public function detailAction() {
      $user_id = $this->params()->fromRoute('id');
try {
   $user = $this->getUserTable()->getUser($user_id);
       $admin = $this->getUserTable()->adminLog($user_id);
             $this->getAdminLogTable()->saveLog(array('log_type'=>'admin_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$user_id));

} catch (\Exception $e) {
   $admin = null;
}
	    
             //echo '<pre>'; print_r($admin); exit;
	   return array('row' => $admin);

  
    }	

    public function addadminAction() {
        $form = new Form('addUserFrm');
    $request=$this->getRequest();
      if ($request->isPost()){
          $user = new User();
          //$form->bind($student);
            $form->setData($request->getPost());
            if ($form->isValid()){
                $postData =$this->params()->fromPost();
              $user->profile_photo = 0;
              $user->username = $postData['username'];
              $user->email_address = $postData['email_address'];
              $user->facebook_username = $postData['facebook_username'];
              $user->twitter_username = $postData['twitter_username'];
              $user->disable_account = $postData['disable_account'];
              $user->create_date = date('Y-m-d');
              $user->role = $postData['role'];


              // Save the changes

              $this->getUserTable()->saveUser($user);
              $this->getAdminLogTable()->saveLog(array('log_type'=>'user_update', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$id));


              $this->messages[] ='Data Added sucessfully';
              $user = $this->getUserTable()->getUser($id);

                $entityManager->persist($user);
                $entityManager->flush();

            }
          }

                  $send = new Element ( 'send' );
        $send->setValue ( 'Create' ); // submit
        $send->setAttributes ( array ('type' => 'submit' ) );
        $form->add ( $send );

                  $view =  new ViewModel();
                  $view->setVariable('form',$form);

  return $view;
    }
    
    
    

    public function editAction() {

         if ($this->request->isPost()) {
            $user_id = $this->params()->fromPost('id');
        $user = $this->getUserTable()->getUser($user_id,0);
        $admin = $this->getUserTable()->adminLog($user_id);

            if(empty($id) or empty($admin)){
              $this->messages[] ='Admin Not Found';
            } else if ($this->validate()) { 
             $postData =$this->params()->fromPost();
              $results->profile_pic = $postData['profile_pic'];
              $user->username = $postData['username'];
              $user->roll = $postData['roll'];
              $user->create_date = $postData['create_date'];

              // Save the changes

              $this->getUserTable()->saveUser($user);
              $this->getAdminLogTable()->saveLog(array('log_type'=>'user_update', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$id));


              $this->messages[] ='Data Update sucessfully';
              $user = $this->getUserTable()->getUser($id);

            }
            
          }else{
              $id = $this->params()->fromRoute('id');
              $user = $this->getUserTable()->getUser($id);
              $admin = $this->getUserTable()->adminLog($id);
          }
                  $view =  new ViewModel();
                  $view->setVariable('admin',$admin );
                  
        return array('admin' => $admin );
    }


   
    public function viewAction() {              
        $user_id = $this->params()->fromRoute('id');
        $page = $this->params()->fromQuery('page', 1);
        $this->getAdminLogTable()->saveLog(array('log_type'=>'admin_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$user_id));



	$user = $this->getUserTable()->getUser($user_id);
	$admin = $this->getUserTable()->adminLog($user_id);
             

          //echo '<pre>'; print_r($transactions); exit;
                    $users = $this->getUserTable()->adminLog();

             $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($users);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(10);

        
        
        return array('paginator' => $paginator, 'row' => $admin );
        
  
    }
    
    
    public function deactivateAction()
    {
        $id = $this->params()->fromRoute('id', 0);
        $user = $this->getUserTable()->getUser($id);
        //print_r($id); exit;
        

        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');
        
            if ($del == 'Yes') {
                  $this->getUserTable()->updateUser(array('disable_account'=>0),$id);
            }

            // Redirect to list of albums
        }

        return array(
            'id'    => $id,
            'user' => $user
        );
    }
    
    
	
}

// end class IndexController
