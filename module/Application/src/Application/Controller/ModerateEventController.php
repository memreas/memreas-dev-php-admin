<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\Common;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class ModerateEventController extends AbstractActionController {

    public $messages = array();
    public $status;
    protected $eventTable;

    public function getEventTable() {
        if (!$this->eventTable) {
            $sm = $this->getServiceLocator();
            $this->eventTable = $sm->get('Application\Model\EventTable');
        }
        return $this->eventTable;
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
        $order = $this->params()->fromQuery('order', 'DESC');
        $q = $this->params()->fromQuery('q', 0);
        $where = array();
        $column = array('username', 'name');
        $url_order = 'DESC';
        if (in_array($order_by, $column))
            $url_order = $order == 'DESC' ? 'ASC' : 'DESC';

        try {

            $event = $this->getEventTable()->moderateFetchAll($where, $order_by, $order);
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($event);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
            $paginator->setItemCountPerPage(10);
        } catch (Exception $exc) {

            return array();
        }
        return array('paginator' => $paginator, 'event_total' => count($event),
            'order_by' => $order_by, 'order' => $order, 'q' => $q, 'page' => $page, 'url_order' => $url_order
        );
    }

    public function viewAction() {
        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $repository = $entityManager->getRepository('Application\Entity\Feedback');

        $id = $this->params()->fromRoute('id');
        $Feedback = $repository->findOneBy(array('feedback_id' => $id));



        $view = new ViewModel();
        $view->setVariable('Feedback', $Feedback);
        $view->setVariable('messages', $this->messages);
        $view->setVariable('status', $this->status);
        return $view;
    }

    public function mediaAction() {

        $event_id = $this->params()->fromRoute('id');
        $this->getAdminLogTable()->saveLog(array('log_type' => 'media_view', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $event_id));

        $event = $this->getEventTable()->getEventMedia($event_id);

        $view = new ViewModel();
        $view->setVariable('medias', $event);

        return $view;
    }

    function validate() {
        $result = true;
        return $result;
    }

    public function deleteAction() {
        
    }

    public function changeStatusAction() {
        $eventTable = $this->getEventTable();
        $event_id = $this->params()->fromRoute('id');
        $event = $eventTable->getEvent($event_id);
        $date = strtotime(date('d-m-Y'));
        $eventStatus = 'inactive';
        if (($event->viewable_to >= $date || $event->viewable_to == '') && ($event->viewable_from <= $date || $event->viewable_from == '') && ($event->self_destruct >= $date || $event->self_destruct == '')
        )
            $eventStatus = 'active';

        return array('eventStatus' => $eventStatus, 'event' => $event);
    }

    public function approveAction() {

        $date1 = strtotime('today + 1year');
        $date = strtotime('NOW');
        $eventTable = $this->getEventTable();
        if ($this->request->isPost()) {
            $postData = $this->params()->fromPost();

            if (empty($postdata['reason']) || $postData['reason'] == 'other') {
                $messages[] = 'Plese give reason';
                $status = 'error';
            }

            $event = $eventTable->getEvent($postData['event_id']);

            $eventStatus = 'inactive';
            if (($event->viewable_to >= $date || $event->viewable_to == '') && ($event->viewable_from <= $date || $event->viewable_from == '') && ($event->self_destruct >= $date || $event->self_destruct == '')
            )
                $eventStatus = 'active';
            $this->getAdminLogTable()->saveLog(array('log_type' => 'event_disable', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $postData['event_id']));
            $messages[] = 'Event approve succesfully';
            $status = 'success';
        }
        if ($status != 'error') {
            $eventTable->update(array('event_id' => $postData['event_id'], 'self_destruct' => $date1), $postData['event_id']);
        }



        return array('eventStatus' => $eventStatus, 'event' => $event, 'messages' => $messages, 'status' => $status);
    }

    public function disapproveAction() {

        $date1 = strtotime('today - 1 month');
        $eventTable = $this->getEventTable();
        if ($this->request->isPost()) {
            $postData = $this->params()->fromPost();
            if (empty($postdata['reason']) || $postData['reason'] == 'other') {
                $messages[] = 'Plese give reason';
                $status = 'error';
            }
            $event = $eventTable->getEvent($postData['event_id']);
            $eventTable->update(array('event_id' => $postData['event_id'], 'self_destruct' => $date1), $postData['event_id']);
            $eventStatus = 'inactive';
            $this->getAdminLogTable()->saveLog(array('log_type' => 'event_disable', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $postData['event_id']));
            $messages[] = 'Event disapprove succesfully';
            $status = 'success';
        }
        if ($status != 'error') {
            $eventTable->update(array('event_id' => $postData['event_id'], 'self_destruct' => $date1), $postData['event_id']);
        }


        return array('eventStatus' => $eventStatus, 'event' => $event, 'messages' => $messages, 'status' => $status);
    }

    public function init() {
        
    }

}

// end class IndexController
