<?php

class Imena_PointCheckout_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order;


    protected $environment;

    /**
     * Live API END POINT
     */
    const LIVE_API_END_POINT = "https://pay.pointcheckout.com";

    /**
     * Staging API END POINT
     */
    const TEST_API_END_POINT = "https://pay.test.pointcheckout.com";


    /**
     * Return url that will be used to check the status of the payment
     */
    const RETURN_URL = "pointcheckout/index/return";


    /**
     * helper method to clear cart
     * @return bool
     */
    public function clearCart()
    {
        $cartHelper = Mage::helper('checkout/cart');
        $items = $cartHelper->getCart()->getItems();

        foreach ($items as $item) {
            $itemId = $item->getItemId();
            $cartHelper->getCart()->removeItem($itemId);
        }
        $cartHelper->getCart()->save();
        return true;
    }

    /**
     * @throws Exception
     */
    public function generateToken()
    {
        $token = "";
        $order = $this->getOrder();

        $body = $items = [];
        $body["referenceId"] = $order->getIncrementId();
        $body["grandtotal"] = $this->number_format($order->getGrandTotal(), 2);
        $body["currency"] = $order->getOrderCurrency()->getCode();
        $body["tax"] = $this->number_format($order->getTaxAmount(), 2);
        $body["shipping"] = $this->number_format($order->getShippingAmount(), 2);
        $body["discount"] = $this->number_format($order->getDiscountAmount(), 2);
        $body["subtotal"] = $this->number_format($order->getSubtotal(), 2);
        $body["returnUrl"] = Mage::getUrl(self::RETURN_URL);

        foreach ($order->getAllVisibleItems() as $item) {
            $i["name"] = $item->getName();
            $i["sku"] = $item->getSku();
            $i["total"] = $this->number_format($item->getPrice());
            $i["quantity"] = $this->number_format($item->getQtyOrdered());
            $items[] = $i;
        }

        $body["items"] = $items;

        /*
        $customer["title"]      = "";
        $customer["firstName"]  = $order->getCustomerFirstname();
        $customer["lastName"]   = $order->getCustomerLastname();
        $customer["email"]      = $order->getCustomerEmail();

        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $customer["addresses"]["billing"]             = [];
        $customer["addresses"]["billing"]["name"]     = $billing->getName();
        $customer["addresses"]["billing"]["address1"] = $billing->getStreet1();
        $customer["addresses"]["billing"]["address2"] = $billing->getStreet2();
        $customer["addresses"]["billing"]["city"]     = $billing->getCity();
        $customer["addresses"]["billing"]["state"]    = $billing->getRegionCode();
        $customer["addresses"]["billing"]["zip"]      = $billing->getPostcode();
        $customer["addresses"]["billing"]["country"]  = $billing->getCountry();

        if($shipping !== false ){
            $customer["addresses"]["shipping"]             = [];
            $customer["addresses"]["shipping"]["name"]     = $shipping->getName();
            $customer["addresses"]["shipping"]["address1"] = $shipping->getStreet1();
            $customer["addresses"]["shipping"]["address2"] = $shipping->getStreet2();
            $customer["addresses"]["shipping"]["city"]     = $shipping->getCity();
            $customer["addresses"]["shipping"]["state"]    = $shipping->getRegionCode();
            $customer["addresses"]["shipping"]["zip"]      = $shipping->getPostcode();
            $customer["addresses"]["shipping"]["country"]  = $shipping->getCountry();
        }

        $body["customer"]     = $customer;
        */

        $body = json_encode($body);

        $api_key = $this->getStoreConfig("api_key");
        $api_secret = $this->getStoreConfig("secret_key");
        /*
        $use_proxy = Mage::getStoreConfig("payment/pointcheckout/use_proxy");
        $host = $port = "";
        if($use_proxy){
            $host = Mage::getStoreConfig("payment/pointcheckout/proxy_host");
            $port = Mage::getStoreConfig("payment/pointcheckout/proxy_port");
        }
        */
        $signature = base64_encode(hash_hmac("sha256", $body, $api_secret, true));

        $client = $this->_getHttpClient();
        $api_function = "/api/v1.0/checkout";
        if($this->getStoreConfig("live") == true)
        {
            $endpoint = self::LIVE_API_END_POINT;
        }else{
            $endpoint = self::TEST_API_END_POINT;
        }
        $url = $endpoint . $api_function;

        $client->setMethod("POST")->setUri($url);
        $headers = [
            "Content-Type" => "application/json",
            "Api-Key" => $api_key,
            "Api-Secret" => $api_secret, //// we shouldn't ever ever ever ever expose private keys / secret keys .
            //"Api-Signature" => $signature,
            //"Powered-By" => "Magento-" . Mage::getEdition() . "-" . Mage::getVersion()
        ];
        $client->setHeaders($headers);
        $client->setRawData($body);
        $this->log($url);
        $this->log($body);
        $this->log($headers);
        try {
            $request = $client->request();
            $body = $request->getBody();
            $data = json_decode($body, true);
            if ($request->getStatus() == "200") {
                $this->log($data);
                if ($data["success"] == true) {
                    $checkoutKey = $data["result"]["checkoutKey"];
                    Mage::getSingleton("core/session")->setData("checkoutKey", $checkoutKey);
                    Mage::getSingleton("core/session")->setData("checkoutId", $data["result"]["checkoutId"]);
                    // should I save the referenceId as comment ? maybe later
                    $redirect_url = $endpoint . "/checkout/" . $checkoutKey;
                    $this->log("redirect url : {$redirect_url}");
                    /// log checkout key to admin view order page
                    $message = "PointCheckout key : {$checkoutKey} <br/> PointCheckout Id : {$data["result"]["checkoutId"]} <br/> <a href='{$redirect_url}' target='_blank'>Payment link</a> ";
                    $commentHistory = $order->addStatusHistoryComment($message);
                    $commentHistory->setIsVisibleOnFront(0);
                    $order->save();
                    return $redirect_url;
                } else {
                    throw new \Exception($data["description"]);
                }
            } else {
                $this->log($request);
                throw new \Exception("got a response code {$request->getStatus()}");
            }
        } catch (\Exception $e) {
            Mage::logException($e);
            Mage::throwException(
                $this->__(
                    "We're sorry, an error has occurred while completing your request : " . $e->getMessage() . " " . $e->getTraceAsString()
                )
            );
        }
        return false;
    }

    /**
     * @param $checkoutId
     * @return bool
     */
    public function validateToken($checkoutId)
    {
        $api_key = $this->getStoreConfig("api_key");
        $api_secret = $this->getStoreConfig("secret_key");
        $client = $this->_getHttpClient();
        $api_function = "/api/v1.0/checkout";
        if($this->getStoreConfig("live") == true)
        {
            $endpoint = self::LIVE_API_END_POINT;
        }else{
            $endpoint = self::TEST_API_END_POINT;
        }
        $url = $endpoint . $api_function . "/" . $checkoutId;
        $client->setMethod("GET")->setUri($url);
        $headers = [
            "Content-Type" => "application/json",
            "Api-Key" => $api_key,
            "Api-Secret" => $api_secret, //// we shouldn't ever ever ever ever expose private keys / secret keys .
            //"Powered-By" => "Magento-" . Mage::getEdition() . "-" . Mage::getVersion()
        ];
        $client->setHeaders($headers);

        try {
            $request = $client->request();
            $body = $request->getBody();

            $data = json_decode($body, true);
            $success = false;
            if ($request->getStatus() == "200") {
                $this->log($data);
                if ($data["success"] == true) {
                    $status = $data["result"]["status"];
                    $order = $this->getOrder();
                    switch ($status) {
                        case $status == "CANCELLED":
                            $status_message = "The customer cancelled the payment , checkout Id: " . $checkoutId . "<br/> checkout key : " . $data["result"]["checkoutKey"];
                            $commentHistory = $order->addStatusHistoryComment($status_message, Mage_Sales_Model_Order::STATE_CANCELED);
                            $commentHistory->setIsVisibleOnFront(0);
                            $order->cancel();
                            $order->save();
                            $success = false;
                            break;
                        case $status == "FAILED":
                            $status_message = "Customer failed to pay <br/> " . $data["result"]["description"];
                            $commentHistory = $order->addStatusHistoryComment($status_message, Mage_Sales_Model_Order::STATE_CANCELED);
                            $commentHistory->setIsVisibleOnFront(0);
                            $order->save();
                            $success = false;
                            break;
                        case $status == "PAID":
                            $status_message = "Customer paid  , PointCheckout Id: " . $data["result"]["checkoutId"] . " has been captured <br/> " . $data["result"]["checkoutKey"];
                            $commentHistory = $order->addStatusHistoryComment($status_message, Mage_Sales_Model_Order::STATE_PROCESSING);
                            $commentHistory->setIsVisibleOnFront(0);
                            $order->save();
                            if ($order->getCanSendNewEmailFlag()) {
                                $order->sendNewOrderEmail();
                            }
                            if (!$order->canInvoice()) {
                                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                            }
                            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                            if (!$invoice->getTotalQty()) {
                                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                            }
                            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                            $invoice->register();
                            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
                            $transactionSave->save();
                            $success = true;
                            break;
                        default :
                        case $status == "PENDING":
                            throw new \Exception("Unknown status for token :{$checkoutId} or its still pending");
                            break;
                    }
                } else {
                    throw new \Exception("data was received : " . json_encode($data));
                    $this->log($request);
                }
            } else {
                throw new \Exception("got a response code {$request->getStatus()}");
                $this->log($request);
            }
        } catch (\Exception $e) {
            Mage::logException($e);
            Mage::throwException(
                $this->__(
                    "We're sorry, an error has occurred while completing your request : " . $e->getMessage()
                )
            );
        }
        return $success;
    }


    /**
     * @param $node
     * @return mixed
     */
    public function getStoreConfig($node)
    {
        $store_id = Mage::app()->getStore()->getStoreId();
        return Mage::getStoreConfig("payment/pointcheckout/{$node}" , $store_id);
    }

    /**
     * get Grand total
     * @return float
     */
    private function getGrandTotal()
    {
        return (float) $this->getOrder()->getGrandTotal();
    }


    /**
     * get placed Order Object
     * @return Mage_Sales_Model_Order
     * @throws \Exception
     */
    private function getOrder()
    {
        if (!$this->order) {
            $session = Mage::getSingleton('checkout/session');
            if (!$session->getLastRealOrderId()) {
                throw new \Exception("Order id can't be null ", 500);
            }
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            $this->order = $order;
        }
        return $this->order;
    }

    /**
     * creates an http client
     *
     * @param string $host
     * @param string $port
     * @return Varien_Http_Client
     */
    private function _getHttpClient($host = "", $port = "")
    {
        /*
        if ($host && $port) {
            $proxy = $host . ":" . $port;
        } else {
            $proxy = CURLOPT_PROXY; /// use  proxy already defined from configuration in case it was set : http://php.net/manual/en/curl.constants.php
        }
        */
        $_allowedParams = array(
            'timeout' => CURLOPT_TIMEOUT,
            'maxredirects' => CURLOPT_MAXREDIRS,
            // 'proxy' => $proxy,
            // 'ssl_cert' => CURLOPT_SSLCERT,
            // 'userpwd' => CURLOPT_USERPWD
        );
        $client = new Varien_Http_Client();
        $adapter = new Varien_Http_Adapter_Curl();
        $adapter->setOptions($_allowedParams);
        $client->setAdapter($adapter);
        return $client;

    }


    /**
     * return customer object from session
     * @return Mage_Customer_Model_Customer
     */
    private function _getCustomer()
    {
        return Mage::helper("customer")->getCustomer();
    }


    /**
     * format number
     * @param $number
     * @param int $decimal
     * @return string
     */
    private function number_format($number, $decimal = 2)
    {
        return str_replace(",", "", number_format(abs($number), $decimal));
    }


    private function log($message)
    {
        return Mage::log($message,null,"pointcheckout.log");
    }
}
