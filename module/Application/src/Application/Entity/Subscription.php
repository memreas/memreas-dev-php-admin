<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Entity\Event;

/**
 * Media
 *
 * @ORM\Table(name="subscription")
 * @ORM\Entity
 */
class Subscription {
	
	/**
	 *
	 * @var string @ORM\Column(name="subscription_id", type="string", length=255, nullable=false)
	 *      @ORM\Id
	 *     
	 */
	private $subscription_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="account_id", type="string", length=255, nullable=false)
	 */
	private $account_id;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="currency_code", type="boolean", nullable=false)
	 */
	private $currency_code = '0';
	
	/**
	 *
	 * @var integer @ORM\Column(name="plan", type="integer", nullable=false)
	 */
	private $plan = '0';
	
	/**
	 *
	 * @var string @ORM\Column(name="plan_amount", type="text", nullable=false)
	 */
	private $plan_amount;
	
	/**
	 *
	 * @var string @ORM\Column(name="plan_description", type="string", length=1, nullable=false)
	 */
	private $plan_description = '0';
	
	/**
	 *
	 * @var string @ORM\Column(name="gb_storage_amount", type="string", length=255, nullable=false)
	 */
	private $gb_storage_amount;

	/**
	 *
	 * @var string @ORM\Column(name="billing_frequency", type="string", length=255, nullable=false)
	 */
	private $billing_frequency;

	/**
	 *
	 * @var string @ORM\Column(name="start_date", type="string", length=255, nullable=false)
	 */
	private $start_date;

	/**
	 *
	 * @var string @ORM\Column(name="end_date", type="string", length=255, nullable=false)
	 */
	private $end_date;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_subscription_profile_id", type="string", length=255, nullable=false)
	 */
	private $paypal_subscription_profile_id;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_subscription_profile_status", type="string", length=255, nullable=false)
	 */
	private $paypal_subscription_profile_status;

	/**
	 *
	 * @var string @ORM\Column(name="create_date", type="string", length=255, nullable=false)
	 */
	private $create_date;

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
