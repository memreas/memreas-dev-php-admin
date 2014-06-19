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

          
    public function indexAction() {
                 try {
        //$account = $this->getAccountTable()->getAccount(array('user_id'=>$id));

        // $account_id =   $account;
         //echo '<pre>'; print_r($account->account_id); exit;
        $admin = $this->getUserTable()->FetchAdmins();
        
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($admin);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(10);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator, 'admin_total' => count($admin));

    }
	 
	

function validate(){
  $result = true ;
return $result;
}
    
	
   
    public function viewAction() {
          
             $user_id = $this->params()->fromRoute('id'); 
             $admin = $this->getUserTable()->getUser($user_id);
             $account = $this->getAccountTable()->getAccount($user_id);

             $transactions = $this->getAccountTable()->getTransaction($account->account_id);

          //echo '<pre>'; print_r($transactions); exit;
              $page = $this->params()->fromQuery('page', 1);

             $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($transactions);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(10);

        
        
        return array('paginator' => $paginator, 'admin_total' => count($admin));













                 
  
    }
	
}

// end class IndexController
