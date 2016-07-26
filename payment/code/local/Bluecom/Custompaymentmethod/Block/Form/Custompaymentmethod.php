<?php
// app/code/local/Envato/Custompaymentmethod/Block/Form/Custompaymentmethod.php
class Bluecom_Custompaymentmethod_Block_Form_Custompaymentmethod extends Mage_Payment_Block_Form
{
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('custompaymentmethod/form/custompaymentmethod.phtml');
  }
}