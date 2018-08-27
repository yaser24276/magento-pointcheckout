<?php

class Imena_PointCheckout_Model_System_Config_Source_Envsource
{
    const STAGING = false;
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if(self::STAGING){
            return array(
                array('value' => 1, 'label'=>'Live'),
                array('value' => 0, 'label'=>'Test'),
                array('value' => 2, 'label'=>'Staging'),
            );
        }else{
            return array(
                array('value' => 1, 'label'=>'Live'),
                array('value' => 0, 'label'=>'Test'),
            );
        }
    }
    
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        if(self::STAGING){
            return array(
                0 => 'Test',
                1 => 'Live',
                2 => 'Staging',
            );
        }else{
            return array(
                0 => 'Test',
                1 => 'Live',
            );
        }
    }
    
}

