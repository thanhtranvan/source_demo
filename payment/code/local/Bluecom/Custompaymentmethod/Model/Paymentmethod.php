<?php
// app/code/local/Envato/Custompaymentmethod/Model/Paymentmethod.php

class Bluecom_Custompaymentmethod_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {
    protected $_code  = 'custompaymentmethod';
    protected $_formBlockType = 'custompaymentmethod/form_custompaymentmethod';
    protected $_infoBlockType = 'custompaymentmethod/info_custompaymentmethod';

    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canRefund               = false;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;


    protected $_canSaveCc = false; //if made try, the actual credit card number and cvv code are stored in database.

    public function process($data){

        if($data['cancel'] == 1){
         $order->getPayment()
         ->setTransactionId(null)
         ->setParentTransactionId(time())
         ->void();
         $message = 'Unable to process Payment';
         $order->registerCancellation($message)->save();
        }
    }

    /** For capture **/
    public function capture(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $result = $this->callApi($payment,$amount,'authorize');
        if($result === false) {
            $errorCode = 'Invalid Data';
            $errorMsg = $this->_getHelper()->__('Error Processing the request');
        } else {
            Mage::log($result, null, $this->getCode().'.log');
            //process result here to check status etc as per payment gateway.
            // if invalid status throw exception

            if($result['status'] == 1){
                $payment->setTransactionId($result['transaction_id']);
                $payment->setIsTransactionClosed(1);
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array('key1'=>'value1','key2'=>'value2'));
            }else{
                Mage::throwException($errorMsg);
            }

            // Add the comment and save the order
        }
        if($errorMsg){
            Mage::throwException($errorMsg);
        }

        return $this;
    }


    /** For authorization **/
    public function authorize(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $result = $this->callApi($payment,$amount,'authorize');
        if($result === false) {
            $errorCode = 'Invalid Data';
            $errorMsg = $this->_getHelper()->__('Error Processing the request');
        } else {
            Mage::log($result, null, $this->getCode().'.log');
            //process result here to check status etc as per payment gateway.
            // if invalid status throw exception

            if($result['status'] == 1){
                $payment->setTransactionId($result['transaction_id']);
                /*
                 * This marks transactions as closed or open
                */
                $payment->setIsTransactionClosed(1);
                /*
                 * This basically makes order status to be payment review and no invoice is created.
                * and adds a default comment like
                * Authorizing amount of $17.00 is pending approval on gateway. Transaction ID: "1335419269".
                *
                */
                //$payment->setIsTransactionPending(true);
                /*
                 * This basically makes order status to be processing and no invoice is created.
                * add a default comment to order like
                * Authorized amount of $17.00. Transaction ID: "1335419459".
                */
                //$payment->setIsTransactionApproved(true);

                /*
                 * This method is used to display extra informatoin on transaction page
                */
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array('key1'=>'value1','key2'=>'value2'));


                $order->addStatusToHistory($order->getStatus(), 'Payment Sucessfully Placed with Transaction ID'.$result['transaction_id'], false);
                $order->save();
            }else{
                Mage::throwException($errorMsg);
            }

            // Add the comment and save the order
        }
        if($errorMsg){
            Mage::throwException($errorMsg);
        }

        return $this;
    }

    public function processBeforeRefund($invoice, $payment){
        return parent::processBeforeRefund($invoice, $payment);
    }
    public function refund(Varien_Object $payment, $amount){
        $order = $payment->getOrder();
        $result = $this->callApi($payment,$amount,'refund');
        if($result === false) {
            $errorCode = 'Invalid Data';
            $errorMsg = $this->_getHelper()->__('Error Processing the request');
            Mage::throwException($errorMsg);
        }
        return $this;

    }
    public function processCreditmemo($creditmemo, $payment){
        return parent::processCreditmemo($creditmemo, $payment);
    }
    
    private function callApi(Varien_Object $payment, $amount,$type){
            $storeId = 'Store Id'.' '.Mage::app()->getStore()->getId();
            $order = $payment->getOrder();
            $types = Mage::getSingleton('payment/config')->getCcTypes();
            if (isset($types[$payment->getCcType()]))
            {
                $type = $types[$payment->getCcType()];
            }
            
            $billingaddress = $order->getBillingAddress();
            $totals = number_format($amount, 2, '.', '');
            $orderId = $order->getIncrementId();
            $currencyDesc = $order->getBaseCurrencyCode();
            Stripe::setApiKey($this->getConfigData('api_username')); 
            try 
                {
                    $createtoken= Stripe_Token::create(array( "card" => array( "number" => $payment->getCcNumber(), "exp_month" => $payment->getCcExpMonth(), "exp_year" => $payment->getCcExpYear(), "cvc" => $payment->getCcCid(),"name"=>$billingaddress->getData('firstname').' '.$billingaddress->getData('lastname'),"address_line1"=>$billingaddress->getData('street'),"address_city"=>$billingaddress->getData('city'),"address_state"=>$billingaddress->getData('region'),"address_zip"=>$billingaddress->getData('postcode'),"address_country"=>$billingaddress->getData('country_id'),"customer"=>Mage::getSingleton('customer/session')->getCustomerId() ) ));
                    $createcharge=Stripe_Charge::create(array( "amount" => $totals*100, "currency" => $currencyDesc, "card" => $createtoken->id,"statement_descriptor"=>$storeId,"description" => sprintf('#%s, %s', $orderId, $order->getCustomerEmail())));
                    return array('status'=>1,'transaction_id' => $createcharge->id,'fraud' => rand(0,1));
                } 
            catch (Exception $e) 
                {
                    $this->debugData($e->getMessage());
                    Mage::throwException(Mage::helper('paygate')->__($e->getMessage()));
                    die;
                }
                
    }
}