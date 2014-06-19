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

    
class 




RefundController extends AbstractActionController {

public $messages = array();
public $status ;

          
    public function indexAction() {
                $this->db = $this->getServiceLocator()->get ( 'doctrine.entitymanager.orm_default' );
                $objectRepository = $this->db->getRepository('Application\Entity\feedback');



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
	 
	public function viewAction() {
        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $repository = $entityManager->getRepository('Application\Entity\feedback');
        //$builder    = new AnnotationBuilder();
        //$form       = $builder->createForm('Application\Entity\Feedback');
        //$form->bind($Feedback);
     
             $id = $this->params()->fromRoute('id');
             $









$feedback = $repository->findOneBy(array('feedback_id' => $id));

          
          
                  $view =  new ViewModel();
                  $view->setVariable('









feedback',$









$feedback );
                  $view->setVariable('messages',$this->messages );
                  $view->setVariable('status',$this->status );
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
