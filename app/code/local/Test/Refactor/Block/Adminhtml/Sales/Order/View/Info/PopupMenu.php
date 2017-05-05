<?php
class Test_Refactor_Block_Adminhtml_Sales_Order_View_Info_PopupMenu extends Mage_Adminhtml_Block_Widget_Form//Mage_Core_Block_Template
{
    public function getOrder()
    {
        return Mage::registry('current_order');
    }
    public function getWindowUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/refactor_options/get', array('id' => $this->getOrder()->getId()));
    }
}
