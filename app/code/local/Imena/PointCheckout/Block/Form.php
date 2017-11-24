<?php
class Imena_PointCheckout_Block_Form extends Mage_Payment_Block_Form
{

    /**
     * Instructions text
     *
     * @var string
     */
    protected $_instructions;

    /**
     * Block construction. Set block template.
     */
    protected function _construct()
    {
        $class = Mage::getConfig()->getBlockClassName('core/template');
        $logo = new $class;
        $logo->setTemplate('pointcheckout/form/logo.phtml');
        $this->setTemplate('pointcheckout/form/form.phtml')
            ->setMethodTitle('')
            ->setMethodLabelAfterHtml($logo->toHtml());
        return parent::_construct();
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return "";
        /*
        if (is_null($this->_instructions)) {
            $this->_instructions = $this->getMethod()->getInstructions();
        }
        return $this->_instructions;
        */
    }

}
