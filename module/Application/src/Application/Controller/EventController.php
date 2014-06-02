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


class EventController extends AbstractActionController {

    protected $url = "http://test";
    protected $user_id;
       
    
    public function indexAction() {
        $this->db = $this->getServiceLocator()->get ( 'doctrine.entitymanager.orm_default' );
        $objectRepository = $this->db->getRepository('Application\Entity\Event');
        // Create the adapter
        $adapter = new SelectableAdapter($objectRepository); // An object repository implements Selectable
        // Create the paginator itself
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber((int)$this->params()->fromQuery('page', 1));
        $paginator->setItemCountPerPage(5);

                
                

        return new ViewModel(
            array(
                'paginator' => $paginator 
            )
        );
  
    }
	public function addAction() {

  
    }
	public function editAction() {

  
    }
	public function deleteAction() {

  
    }
    public function viewAction() {
           $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $repository = $entityManager->getRepository('Application\Entity\Event');
             $id = $this->params()->fromRoute('id'); 
              $event = $repository->findOneBy(array('event_id' => $id));
 
              $medias = $repository->getEventMedia($id);

                  $view =  new ViewModel();
                  $view->setVariable('event',$event );
                  $view->setVariable('medias',$medias );

                  //$view->setVariable('messages',$this->messages );
                  //$view->setVariable('status',$this->status );


        return $view;
  
    }
   

}

// end class IndexController
