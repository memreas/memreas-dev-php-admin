<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Entity\Event;

/**
 * Media
 *
 * @ORM\Table(name="account")
 * @ORM\Entity
 */
class Account {
	
	/**
	 *
	 * @var string @ORM\Column(name="account_id", type="string", length=255, nullable=false)
	 *      @ORM\Id
	 *     
	 */
	private $account_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="user_id", type="string", length=255, nullable=false)
	 */
	private $user_id;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="username", type="boolean", nullable=false)
	 */
	private $username;
	
	/**
	 *
	 * @var integer @ORM\Column(name="account_type", type="integer", nullable=false)
	 */
	private $account_type;
	
	/**
	 *
	 * @var string @ORM\Column(name="balance", type="text", nullable=false)
	 */
	private $balance;
	
	/**
	 *
	 * @var string @ORM\Column(name="create_time", type="string", length=1, nullable=false)
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
	public function __construct() {
		$this->events = new \Doctrine\Common\Collections\ArrayCollection ();
	}
}
