<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zend\Form\Annotation as Form;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="Application\Entity\EventRepository")
 * @Form\Hydrator("Zend\Stdlib\Hydrator\ObjectProperty")
 * @Form\Name("event")
  */
class Event {
	/**
	 *
	 * @var string @ORM\Column(name="event_id", type="string", length=255, nullable=false)
	 *      @ORM\Id
	 */
	private $event_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="user_id", type="string", length=255, nullable=false)
	 */
	private $user_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="name", type="string", length=30, nullable=false)
	      * @Form\Options({"label":"Eventname: "})

	 */
	private $name;
	
	/**
	 *
	 * @var string @ORM\Column(name="location", type="string", length=255, nullable=false)
	 * @Form\Options({"label":"Location: "})
	 */
	private $location;
	
	/**
	 *
	 * @var string @ORM\Column(name="date", type="string", length=255, nullable=false)
	 * @Form\Options({"label":"EventDate: "})
	  |* @Form\Type("Zend\Form\Element\DateTime")
	   	  * @Form\Options({"label":"Public:" , "format" : "Y-m-d"})
		 * @Form\Attributes( {"class" : "share-txtfield date-picker hasDatepicker"})

	 */
	private $date;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="friends_can_post", type="boolean", nullable=false)
	 * @Form\Options({"label":"Friends Can Post: "})
	 * @Form\Type("Zend\Form\Element\Checkbox")
	 */
	private $friends_can_post;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="friends_can_share", type="boolean", nullable=false)
	  * @Form\Options({"label":"Friends Can Share: "})
	  *  @Form\Type("Zend\Form\Element\Checkbox")
	 */
	private $friends_can_share;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="public", type="boolean", nullable=false)
	  * @Form\Options({"label":"Public: ","value_options" : {"0":"NO","1":"YES"}})
	  *@Form\Type("Zend\Form\Element\Checkbox")
	 */
	private $public = '0';
	
	/**
	 *
	 * @var string @ORM\Column(name="viewable_from", type="string", length=255, nullable=false)
	 * @Form\Options({"label":"Viewable From: "})
	 */
	private $viewable_from;
	
	/**
	 *
	 * @var string @ORM\Column(name="viewable_to", type="string", length=255, nullable=false)
	 * @Form\Options({"label":"Viewable To: "})
	 */
	private $viewable_to;
	
	/**
	 *
	 * @var string @ORM\Column(name="self_destruct", type="string", length=255, nullable=false)
	 * @Form\Options({"label":"Seld Destruct: "})
	 */
	private $self_destruct;
	
	/**
	 *
	 * @var string @ORM\Column(name="create_time", type="string", length=255, nullable=false)
	 * @Form\Options({"label":"EventDate: "})
 	 * @Form\Options({"label":"Created:" , "format" : "Y-m-d"})
	 * @Form\Attributes( {"class" : "share-txtfield date-picker hasDatepicker"})
	 */
	private $create_time;
	
	/**
	 *
	 * @var string @ORM\Column(name="update_time", type="string", length=255, nullable=false)
	 */
	private $update_time;
	public function __set($name, $value) {
		$this->$name = $value;
	}
	public function __get($name) {
		return $this->$name;
	}
}
