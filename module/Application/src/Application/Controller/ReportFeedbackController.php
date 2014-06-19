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

          
    public function indexAction() {
                 try {
                 
        $feedback = $this->getFeedbackTable()->FetchFeedDescAll();
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($feedback);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(10);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator, 'feedback_total' => count($feedback));

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
          
             $feedback_id = $this->params()->fromRoute('id'); 
             $feedback = $this->getFeedbackTable()->getFeedback($feedback_id);

                  $view =  new ViewModel();
                  $view->setVariable('feedback',$feedback );
 
       return $view;
  
    }
	
}

// end class IndexController
