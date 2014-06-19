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

class EventController extends AbstractActionController {

    protected $url = "http://test";
    protected $user_id;
	public $messages = array();
	public $status ;
    protected $eventTable;


       
     public function getEventTable() {
        if (!$this->eventTable) {
            $sm = $this->getServiceLocator();
            $this->eventTable = $sm->get('Application\Model\EventTable');
        }
        return $this->eventTable;
    }
    public function indexAction() {
        try {
        $event = $this->getEventTable()->fetchAll();
        
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($event);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(10);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator, 'event_total' => count($event));
    
  
    }
	public function addAction() {

  
    }
	public function editAction() {
		
		


		                    
         $id = $this->params()->fromRoute('id');
           try {
        if ($this->request->isPost()) {
            $id = $this->params()->fromPost('event_id');
	          $event = $this->getEventTable()->getEvent($id);
            if(empty($id) or empty($event)){
              $this->messages[] ='Event Not Found';
            } else { 
             $postData =$this->params()->fromPost();
              
              // Save the changes
			
		$event = $this->getEventTable()->saveEvent($postData);
                  $this->messages[] ='Data Update sucessfully';

              	
            
          }
                }
 		  
		 $event = $this->getEventTable()->getEvent($id);
               
     } catch (Exception $e) {               
                   $this->messages[] =$e->getMessage();
     }     
		

          
                  $view =  new ViewModel();
                  $view->setVariable('event',$event );
                  $view->setVariable('messages',$this->messages );
                  //$view->setVariable('medias',$medias );
				          //          $view->setVariable('form',$form );



        return $view;

  
    }
	public function deleteAction() {

  
    }

    public function viewAction() {
          
             $id = $this->params()->fromRoute('id'); 
             $event = $this->getEventTable()->getEvent($id);

 
              $medias = $this->getEventMediaTable()->getEventedia($id);;

                  $view =  new ViewModel();
                  $view->setVariable('event',$event );
                  $view->setVariable('medias',$medias );

                  //$view->setVariable('messages',$this->messages );
                  //$view->setVariable('status',$this->status );


        return $view;
  
    }
	function validate(){
  $result = true ;
return $result;
}
   

}

// end class IndexController
