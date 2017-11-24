<?php

class Imena_PointCheckout_IndexController extends Mage_Core_Controller_Front_Action
{
    public function RedirectAction()
    {
        /** @var Imena_PointCheckout_Helper_Data  $helper */
        $helper = Mage::helper("pointcheckout");
        $url = $helper->generateToken();
        if($url !== false){
            return  $this->_redirectUrl($url);
        }else{
            //// return 404
            $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
            $this->getResponse()->setHeader('Status','404 File not found');
            return $this->_forward('defaultNoRoute');
        }
    }



    public function ReturnAction()
    {
        $checkoutId = Mage::getSingleton("core/session")->getCheckoutId();
        if($checkoutId){
            /** @var Imena_PointCheckout_Helper_Data $helper */
            $helper = Mage::helper("pointcheckout");
            $status = $helper->validateToken($checkoutId);
            if($status){
               return $this->_redirect("checkout/onepage/success");
            }
            return $this->_redirect("checkout/onepage/failure");
        }else{
            //// return 404
            $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
            $this->getResponse()->setHeader('Status','404 File not found');
            return $this->_forward('defaultNoRoute');
        }
    }
}
