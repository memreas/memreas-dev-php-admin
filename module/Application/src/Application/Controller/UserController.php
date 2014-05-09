<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\Common;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;


class UserController extends AbstractActionController {


          
    public function indexAction() {
                $this->db = $this->getServiceLocator()->get ( 'doctrine.entitymanager.orm_default' );
                $objectRepository = $this->db->getRepository('Application\Entity\User');



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
	
   public function init()
    {
        
    }
	
}

// end class IndexController
