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

use Zend\Db\Sql\Sql;
      use Zend\Form\Element;

  
class ModerateEventController extends AbstractActionController {

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
                 
        $event = $this->getEventTable()->moderateFetchAll();
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
   
  public function viewAction() {
        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $repository = $entityManager->getRepository('Application\Entity\Feedback');
       
             $id = $this->params()->fromRoute('id');
             $Feedback = $repository->findOneBy(array('feedback_id' => $id));

          
          
                  $view =  new ViewModel();
                  $view->setVariable('Feedback',$Feedback );
                  $view->setVariable('messages',$this->messages );
                  $view->setVariable('status',$this->status );
        return $view;
    }
  public function mediaAction() {
       
             $event_id = $this->params()->fromRoute('id');
             
             $event= $this->getEventTable()->getEventMedia($event_id);
  
             $view =  new ViewModel();
                  $view->setVariable('medias',$event );
                 
        return $view;
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
  
}

// end class IndexController
