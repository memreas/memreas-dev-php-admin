<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\Common;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use Application\Model\MemreasConstants;
     
class OrderHistoryController extends AbstractActionController {

public $messages = array();
public $status ;

          
    public function indexAction() {
         //  $id = $this->params()->fromRoute('id'); 
       // $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$id));
            $page = $this->params()->fromQuery('page', 1);

         
            $result = Common::fetchXML('getorderhistory',"<xml><getorderhistory><user_id>0</user_id><page>$page</page><limit>15</limit></getorderhistory></xml>");
 $orderData = simplexml_load_string($result);
//echo '<pre>';print_r($orderData); 
     return array('orderData' => $orderData,'page' => $page);

    }
   
  public function viewAction() {
         
            $feedbacks = $this->getFeedbackTable()->FetchFeedDescAll();
            $feedback_id = $this->params()->fromRoute('id'); 
            $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$feedback_id));
            $feedback = $this->getFeedbackTable()->getFeedback($feedback_id);
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($feedbacks);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(10);
                  $view =  new ViewModel();
                  $view->setVariable('feedback',$feedback );
// print_r($feedback); exit;
      // return $view;
     return array('paginator' => $paginator, 'feedback' => $feedback, 'page' => $page);

  
    }

function validate(){
  $result = true ;
return $result;
}
    
  public function deleteAction() {

  
    }
  
    public function detailAction() {
        $transaction_id = $this->params()->fromRoute('id'); 
        $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$transaction_id));
        $result = Common::fetchXML('getorder',"<xml><getorder><transaction_id>$transaction_id</transaction_id></getorder></xml>");
        $orderData = simplexml_load_string($result);
        //echo '<pre>';print_r($orderData);exit;
        return array('orderData' => $orderData);

  
    }
      protected $adminLogTable;
    public function getAdminLogTable() {
        if (!$this->adminLogTable) {
            $sm = $this->getServiceLocator();
            $this->adminLogTable = $sm->get('Application\Model\AdminLogTable');
        }
        return $this->adminLogTable;
    }

  
}

// end class IndexController
