<?php
// app/code/local/Envato/Custompaymentmethod/Block/Form/Custompaymentmethod.php
class Bluecom_Stripe_Block_Form_Payment extends Mage_Payment_Block_Form
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('bluecom_stripe/form/stripe.phtml');
	}
}