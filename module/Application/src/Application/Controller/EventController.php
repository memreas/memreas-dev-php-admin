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
		
		


		                    
         
        if ($this->request->isPost()) {
            $id = $this->params()->fromPost('id');
	    $event = $this->getEventTable()->getEvent($id);
            if(empty($id) or empty($event)){
              $this->messages[] ='Event Not Found';
            } else if ($this->validate()) { 
             $postData =$this->params()->fromPost();
                            $event->name = $postData['name'];
              $event->name = $postData['name'];
              $event->location = $postData['location'];
              $event->date = $postData['date'];
              $event->friends_can_post = strtotime($postData['friends_can_post']);
              $event->public = $postData['public'];
			       $event->viewable_from = strtotime($postData['viewable_from']);
			       $event->viewable_to = strtotime($postData['viewable_to']);
				    $event->self_destruct = strtotime($postData['self_destruct']);
                                    print_r($event); exit;
              // Save the changes
				try {
		$event = $this->getEventTable()->saveEvent($event);

              	} catch (Exception $e) {               
                  $this->messages[] ='Data Update sucessfully';
				}
            
          }
                }else{
		  $id = $this->params()->fromRoute('id');
		 $event = $this->getEventTable()->getEvent($id);
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

 
              $medias = $this->getEventTable()->getEventMedia($id);;

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
