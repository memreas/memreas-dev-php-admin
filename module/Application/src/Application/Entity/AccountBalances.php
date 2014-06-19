<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Entity\Event;

/**
 * Media
 *
 * @ORM\Table(name="account_balances")
 * @ORM\Entity
 */
class AccountBalances {
	
	/**
	 *
	 * @var string @ORM\Column(name="account_id", type="string", length=255, nullable=false)
	 *      @ORM\Id
	 *     
	 */
	private $account_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="transaction_id", type="string", length=255, nullable=false)
	 */
	private $transaction_id;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="transaction_type", type="boolean", nullable=false)
	 */
	private $transaction_type;
	
	/**
	 *
	 * @var integer @ORM\Column(name="starting_balance", type="integer", nullable=false)
	 */
	private $starting_balance;
	
	/**
	 *
	 * @var string @ORM\Column(name="amount", type="text", nullable=false)
	 */
	private $amount;
	
	/**
	 *
	 * @var string @ORM\Column(name="ending_balance", type="string", length=1, nullable=false)
	 */
	private $ending_balance;


	/**
	 *
	 * @var string @ORM\Column(name="create_time", type="string", length=255, nullable=false)
	 */
	private $create_time;
	
	
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
