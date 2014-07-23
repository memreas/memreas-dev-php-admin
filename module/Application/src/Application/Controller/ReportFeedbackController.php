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

    
class ReportFeedbackController extends AbstractActionController {

public $messages = array();
public $status ;

protected $feedbackTable;


       
     public function getFeedbackTable() {
        if (!$this->feedbackTable) {
            $sm = $this->getServiceLocator();
            $this->feedbackTable = $sm->get('Application\Model\FeedbackTable');
        }
        return $this->feedbackTable;
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
             $column = array('username','create_time');
             $url_order = 'DESC';
  if (in_array($order_by, $column))
    $url_order = $order == 'DESC' ? 'ASC' : 'DESC';
                 try {
                 
        $feedback = $this->getFeedbackTable()->FetchFeedDescAll($where, $order_by, $order);
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($feedback);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(10);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator, 'feedback_total' => count($feedback),
                      'order_by'=>$order_by,'order' => $order,'q'=>$q,'page' => $page, 'url_order'=>$url_order);

    }
	 
	

function validate(){
  $result = true ;
return $result;
}
    
	public function deleteAction() {

  
    }
	
   public function init()
    {
        
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
	
	public function detailAction() {
             $feedback_id = $this->params()->fromRoute('id'); 
        $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$feedback_id));

             $feedback = $this->getFeedbackTable()->getFeedback($feedback_id);

	   return array('feedback' => $feedback);

  
    }
	
}

// end class IndexController
