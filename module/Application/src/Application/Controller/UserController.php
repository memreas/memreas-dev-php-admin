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


      use Zend\Form\Element;

    
class UserController extends AbstractActionController {

public $messages = array();
public $status ;
    protected $userTable;


            public function getUserTable() {
        if (!$this->userTable) {
            $sm = $this->getServiceLocator();
            $this->userTable = $sm->get('Application\Model\UserTable');
        }
        return $this->userTable;
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
            //$role = $this->security();
        try {
        $users = $this->getUserTable()->fetchAll();
        
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($users);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(5);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator, 'user_total' => count($users));
    

    }
	public function addAction() {
         $form = new Form('add');
         if ($request->isPost()){
          //$form->bind($student);
            $form->setData($request->getPost());
            if ($form->isValid()){
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
            $id = $this->params()->fromPost('id');
        $user = $this->getUserTable()->getUser($id);
            if(empty($id) or empty($user)){
              $this->messages[] ='User Not Found';
            } else if ($this->validate()) { 
             $postData =$this->params()->fromPost();
              $user->username = $postData['username'];
              $user->email_address = $postData['email_address'];
              $user->facebook_username = $postData['facebook_username'];
              $user->twitter_username = $postData['twitter_username'];
              $user->disable_account = $postData['disable_account'];

              // Save the changes

              $this->getUserTable()->saveUser($user);
              $this->getAdminLogTable()->saveLog(array('log_type'=>'user_update', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$id));


              $this->messages[] ='Data Update sucessfully';
              $user = $this->getUserTable()->getUser($id);

            }
            
          }else{
              $id = $this->params()->fromRoute('id');
              $user = $this->getUserTable()->getUser($id);
          }
            
          
                  $view =  new ViewModel();
                  $view->setVariable('user',$user );
                  $view->setVariable('messages',$this->messages );
                  $view->setVariable('status',$this->status );


        return $view;
    }

function validate(){
  $result = true ;
return $result;
}
    
	public function activeAction() {
              $id = $this->params()->fromRoute('id');
              $user = $this->getUserTable()->getUser($id);
              
              if($user->disable_account==1){
                  $disable_account==0;
                  $user=$user->disable_account=0;
                  $this->getUserTable()->updateUser(array('disable_account'=>0),$id);
              }
  
    }
    public function deactiveAction() {
        
              $id = $this->params()->fromRoute('id');
              $user = $this->getUserTable()->getUser($id);
              
              if($user->disable_account==0){
                  $disable_account==1;
                  $user=$user->disable_account=0;
                  $this->getUserTable()->updateUser(array('disable_account'=>1),$id);
  
    }
    }
    
	
   
	
}

// end class IndexController
