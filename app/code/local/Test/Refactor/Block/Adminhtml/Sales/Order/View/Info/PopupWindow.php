<?php
class Test_Refactor_Block_Adminhtml_Sales_Order_View_Info_PopupWindow extends Mage_Adminhtml_Block_Widget_Form//Mage_Core_Block_Template
{
    protected function _prepareLayout()
    {
        $this->setTemplate('test/refactor/window.phtml');
    }
    public function getPostMaxSize()
    {
        $value = ini_get('post_max_size');
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}