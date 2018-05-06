<?php

class Imena_PointCheckout_Model_Payment extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = "pointcheckout";

    protected $_canSaveCc   = true;
    protected $_formBlockType = 'pointcheckout/form';
    // protected $_infoBlockType = 'pointcheckout/info';
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canCaptureOnce              = false;
    protected $_canRefund                   = false;
    protected $_canRefundInvoicePartial     = false;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;


    /**
     * Return url that will be used to check the status of the payment
     */
    const REDIRECT_URL = "pointcheckout/index/redirect";


    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $customer  ;


    /**
     * @param null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        /// this is going to be cached , please tell merchants to clear cache after changing this options
        $selectedCustomerGroups = $this->isSelectedCustomerGroupsEnabled(); 
        $this->customer = $this->getCustomer();
        if($selectedCustomerGroups){
            if($this->customer instanceof Mage_Customer_Model_Customer ){ /// user is logged in and we have selected groups
            $groupId = $this->customer->getGroupId();
                if(!in_array($groupId,$selectedCustomerGroups)){
                    return false; // it will disable the payment method
                }
            }else{/// user isn't logged in and we have selected groups , point checkout shouldn't be displayed
               return false; // it will disable the payment method
            }
        }// if selected groups is not enabled then pointcheckout would be disblayed within parent check
        return parent::isAvailable($quote);
    }


    /**
     * redirect customer to index/redirect controller/action
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl(self::REDIRECT_URL);
    }

    /*
    public function assignData($data)
    {
        parent::assignData($data);

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setTransactionId($data->getTransactionId());
        return $this;
    }
    */

    /**
     * Validate payment method information object
     *
     * @return $this
     */
    /*
    public function validate()
    {
        parent::validate();

        $info = $this->getInfoInstance();
        $transaction_id = $info->getTransactionId();

        if(!$transaction_id){
            $errorMsg = $this->_getHelper()->__('Please insert your employee id.');
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;
    }
    */
    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     */
    /*
    public function authorize(Varien_Object $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            Mage::throwException(
                Mage::helper('payment')->__('Authorize action is not available.')
            );
        }
        $result = $this->createPointCheckoutPlan($payment);
        $payment->setTransactionId($result['id']);
        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionApproved(true);
        $order = $payment->getOrder();
        /// this will disable new order email
        ///$order->setCanSendNewEmailFlag(false);
        $order->addStatusToHistory(
            $order->getStatus(),
            'PointCheckoutPlan was created with number ID: '
            . $result['id'],
            false
        );
        return $this;
    }
    */

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this
     */
    /*
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->canCapture()) {
            Mage::throwException(
                Mage::helper('payment')->__('Capture action is not available.')
            );
        }

        $this->createPointCheckoutPlan($payment);


        $order = $payment->getOrder();

        $currentUserName =  Mage::getSingleton('admin/session')->getUser()->getUsername();
        $order->addStatusToHistory(
            false,
            "PointCheckout payment verified by: " . $currentUserName, false
        );

        //// send new order upon capture
        // $order->setCanSendNewEmailFlag(true);
        // $order->sendNewOrderEmail();
        // $order->setEmailSent(1);

        $order->save();

        return $this;
    }
    */

    /**
     * Retrieve model helper
     *
     * @return Mage_Payment_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('pointcheckout');
    }


    /**
     * return customer object from session
     * @return Mage_Customer_Model_Customer
     */
    private function getCustomer()
    {
        $customer = Mage::helper("customer")->getCustomer();
        if(! $customer instanceof  Mage_Customer_Model_Customer)
        {
            return false;
        }
        return $customer;
    }


    /**
     * get Grand total
     * @return float
     */
    private function _getGrandTotal()
    {
        $info = $this->getInfoInstance();
        if ($this->_isPlacingOrder()) {
            return (float) $info->getOrder()->getQuoteGrandTotal();
        } else {
            return (float) $info->getQuote()->getGrandTotal();
        }
    }

    /**
     * Whether current operation is order placement
     *
     * @return bool
     */
    private function _isPlacingOrder()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            return false;
        } elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
            return true;
        }
    }


    /**
     * Return Quote or Order Object depending what the Payment is
     *
     * @return Mage_Sales_Model_Order
     */
    private function getOrder()
    {
        $paymentInfo = $this->getInfoInstance();

        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            return $paymentInfo->getOrder();
        }

        return $paymentInfo->getQuote();
    }

    /**
     * @return Varien_Http_Client
     */
    private function getHTTPClient() {
        $client = new Varien_Http_Client();
        $adapter = new Varien_Http_Adapter_Curl();
        $adapter->setOptions($this->_allowedParams);
        $client->setAdapter($adapter);
        return $client;
    }

    /**
     * @return bool|array
     */
    public function isSelectedCustomerGroupsEnabled()
    {
        /** @var Imena_PointCheckout_Helper_Data  $helper */
        $helper = $this->_getHelper();
        $isSelectedCustomerGroupsEnabled = $helper->getStoreConfig("allowspecific_customergroups");
        if($isSelectedCustomerGroupsEnabled){
            $selectedCustomerGroups  = $helper->getStoreConfig("specificcustomergroups");
            return explode(",",$selectedCustomerGroups);

        }
        return false;
    }

}
