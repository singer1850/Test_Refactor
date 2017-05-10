<?php
class Test_Refactor_Adminhtml_Refactor_OptionsController extends Mage_Adminhtml_Controller_Action
{
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }

    public function getAction()
    {
        $this->loadLayout();
        $currentOrder = $this->getRequest()->getParam('id');
        if (!(empty($currentOrder))) {
            if ($this->getRequest()->isAjax()) {
                $result = $this->getLayout()->createBlock('test_refactor/adminhtml_sales_order_view_info_popupWindow', 'refactor.popup.window')->setResponse($currentOrder)->toHtml();
                echo $result;
            } else {
                $this->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $currentOrder)));
            }
        } else {
            $this->_forward('noRoute');
        }
    }

    public function setAction()
    {
        $this->loadLayout();
        $updateOptions = array();
        $updateOptions = $this->getRequest()->getPost('options');
        $order = $this->_initOrder();
        $edited = false;
        $price = 0;
        $i = 0;
        $result = array();
        $result['message_errors'] = null;
        $result['message_warning'] = null;
        $tmpResultSku = '';
        $dateVar = array();
        $fileVar = array();

        if ($this->getRequest()->isAjax()) {
            try {
                if (empty($order)) {
                    throw new Exception($this->__('This order no exists.'));
                }

                if (!(empty($updateOptions))) {
                    $items = $order->getAllVisibleItems();
                    foreach ($items as $item) {
                        $options = $item->getProductOptions();
                        if (is_array($options)) {
                            if ((isset($options['info_buyRequest']['options'])) && (!(empty($options['info_buyRequest']['options']))) && (is_array($options['info_buyRequest']['options']))) {
                                foreach ($options['info_buyRequest']['options'] as $key1 => $value1) {
                                    foreach ($updateOptions as $key2 => $value2) {
                                        if ($key1 == $key2) {

                                            //-----price checking-----
                                            $optionTmp = $item->getProduct()->getOptionById($key2);
                                            if (empty($optionTmp)) {
                                                $edited = false;
                                                throw new Exception($this->__('This custom option no longer exists at product.'));
                                            }
                                            if ( ($optionTmp['type'] == 'radio') || ($optionTmp['type'] == 'drop_down')
                                                || ($optionTmp['type'] == 'checkbox') || ($optionTmp['type'] == 'multiple') ) {
                                                foreach ($optionTmp->getValues() as $v) {
                                                    if ($v['option_type_id'] == $value2) {
                                                        $price = $v->getPrice();
                                                    }
                                                }
                                            } else {
                                                $price = $optionTmp['price'];
                                            }
                                            if ($price > 0) {
                                                $edited = false;
                                                $result['message_warning'][$i] = $this->__('The option "' . $optionTmp['title'] . '" has price more than 0. It would cause to change the final order price.');
                                                continue;
                                            }
                                            //-----price checking end-----
                                            //-----options/infoBuyRequest processing-----

                                            if ((isset($updateOptions[$key2]['date_type'])) && ($updateOptions[$key2]['date_type'] == 'date')) {
                                                $options['info_buyRequest']['options'][$key1] = array();
                                                $options['info_buyRequest']['options'][$key1]['month'] = $updateOptions[$key2]['month'];
                                                $options['info_buyRequest']['options'][$key1]['day'] = $updateOptions[$key2]['day'];
                                                $options['info_buyRequest']['options'][$key1]['year'] = $updateOptions[$key2]['year'];
                                                $dateVar[$key2] = mktime(0, 0, 0, $updateOptions[$key2]['month'], $updateOptions[$key2]['day'], $updateOptions[$key2]['year']);
                                                $options['info_buyRequest']['options'][$key1]['date_internal'] = date('Y-m-d H:i:s', $dateVar[$key2]);
                                            }
                                            if ((isset($updateOptions[$key2]['date_type'])) && ($updateOptions[$key2]['date_type'] == 'time')) {
                                                $options['info_buyRequest']['options'][$key1] = array();
                                                $options['info_buyRequest']['options'][$key1]['hour'] = $updateOptions[$key2]['hour'];
                                                $dateVar[$key2] = $updateOptions[$key2]['hour'] . ':';
                                                $options['info_buyRequest']['options'][$key1]['minute'] = $updateOptions[$key2]['minute'];
                                                $dateVar[$key2] .= $updateOptions[$key2]['minute'] . ' ';
                                                $options['info_buyRequest']['options'][$key1]['day_part'] = $updateOptions[$key2]['day_part'];
                                                $dateVar[$key2] .= $updateOptions[$key2]['day_part'];
                                                $options['info_buyRequest']['options'][$key1]['date_internal'] = date('Y-m-d h:i:s',  strtotime($dateVar[$key2]));
                                            }
                                            if ((isset($updateOptions[$key2]['date_type'])) && ($updateOptions[$key2]['date_type'] == 'date_time')) {
                                                $options['info_buyRequest']['options'][$key1] = array();
                                                $options['info_buyRequest']['options'][$key1]['year'] = $updateOptions[$key2]['year'];
                                                $dateVar[$key2] = $updateOptions[$key2]['year'] . '-';
                                                $options['info_buyRequest']['options'][$key1]['month'] = $updateOptions[$key2]['month'];
                                                $dateVar[$key2] .= $updateOptions[$key2]['month'] . '-';
                                                $options['info_buyRequest']['options'][$key1]['day'] = $updateOptions[$key2]['day'];
                                                $dateVar[$key2] .= $updateOptions[$key2]['day'] . ' ';
                                                $options['info_buyRequest']['options'][$key1]['hour'] = $updateOptions[$key2]['hour'];
                                                $dateVar[$key2] .= ($updateOptions[$key2]['hour'] > 10) ? $updateOptions[$key2]['hour'] . ':' : '0' . $updateOptions[$key2]['hour'] . ':' ;
                                                $options['info_buyRequest']['options'][$key1]['minute'] = $updateOptions[$key2]['minute'];
                                                $dateVar[$key2] .= ($updateOptions[$key2]['minute'] > 10) ? $updateOptions[$key2]['minute'] . ' ' : '0' . $updateOptions[$key2]['minute'] . ' ';
                                                $options['info_buyRequest']['options'][$key1]['day_part'] = $updateOptions[$key2]['day_part'];
                                                $dateVar[$key2] .= $updateOptions[$key2]['day_part'];
                                                $options['info_buyRequest']['options'][$key1]['date_internal'] = date('Y-m-d h:i:s',  strtotime($dateVar[$key2]));
                                            }
                                            if ((isset($updateOptions[$key2]['is_file'])) && (isset($_FILES['options_' . $key2]['name'])) && (!(empty($_FILES['options_' . $key2]['name'])))) {
                                                $options['info_buyRequest']['options'][$key1]['type'] = $_FILES['options_' . $key2]['type'];
                                                $options['info_buyRequest']['options'][$key1]['title'] = $_FILES['options_' . $key2]['name'];

                                                $fileHash = md5(file_get_contents($_FILES['options_' . $key2]['tmp_name']));
                                                $path = Mage::getBaseDir('media') . DS . 'custom_options' . DS . 'order' ;
                                                $uploader = new Varien_File_Uploader('options_' . $key2);
                                                $uploader->setFilesDispersion(true);
                                                $uploader->setAllowRenameFiles(false);
                                                $uploader->save($path, $_FILES['options_' . $key2]['name']);
                                                $uploadedFilePath = pathinfo($uploader->getUploadedFileName());
                                                rename($path . $uploader->getUploadedFileName(), $path . $uploadedFilePath['dirname'] . DS .  $fileHash . '.txt' );
                                                $options['info_buyRequest']['options'][$key1]['fullpath'] = $path . $uploadedFilePath['dirname'] . DS . $fileHash . '.txt';
                                                $options['info_buyRequest']['options'][$key1]['order_path'] = DS . 'media' . DS . 'custom_options' . DS . 'order' . $uploadedFilePath['dirname'] . DS . $fileHash . '.txt';
                                                $options['info_buyRequest']['options'][$key1]['size'] = $_FILES['options_' . $key2]['size'];
                                                $options['info_buyRequest']['options'][$key1]['width'] = 0;
                                                $options['info_buyRequest']['options'][$key1]['height'] = 0;
                                                $options['info_buyRequest']['options'][$key1]['secret_key'] = substr($fileHash, 0, 20);
                                                $fileVar[$key2] = $options['info_buyRequest']['options'][$key1];



                                            } elseif ( (isset($updateOptions[$key2]['is_file'])) && (empty($_FILES['options_' . $key2]['name'])) ) {
                                                throw new Exception($this->__('The file maybe too large.'));
                                            }
                                            if ((empty($dateVar[$key2])) || (empty($fileVar[$key2]))) {
                                                $options['info_buyRequest']['options'][$key1] = $updateOptions[$key2];
                                            }
                                            $edited = true;
                                            //-----options/infoBuyRequest processing end-----

                                            //--------options/options new processing-----
                                            if (!(isset($options['options'][$i]))) {
                                                $options['options'][$i]['label'] = $optionTmp['title'];
                                                $options['options'][$i]['option_id'] = $optionTmp['option_id'];
                                                $options['options'][$i]['option_type'] = $optionTmp['type'];
                                                if (!(empty($optionTmp['sku']))) {
                                                    $tmpResultSku = $options['simple_sku'] . '-' . $optionTmp['sku'];
                                                }
                                            }

                                            if ((isset($dateVar[$key2])) || (isset($fileVar[$key2]))) {
                                                if ($options['options'][$i]['option_type'] == 'date') {
                                                    $options['options'][$i]['value'] = date('M d, Y', mktime(0, 0, 0, $updateOptions[$key2]['month'], $updateOptions[$key2]['day'], $updateOptions[$key2]['year']));
                                                    $options['options'][$i]['print_value'] = date('M d, Y', mktime(0, 0, 0, $updateOptions[$key2]['month'], $updateOptions[$key2]['day'], $updateOptions[$key2]['year']));
                                                    $options['options'][$i]['option_value'] = date('Y-m-d H:i:s', mktime(0, 0, 0, $updateOptions[$key2]['month'], $updateOptions[$key2]['day'], $updateOptions[$key2]['year']));
                                                    $options['options'][$i]['custom_view'] = false;
                                                }
                                                if ($options['options'][$i]['option_type'] == 'time') {
                                                    $options['options'][$i]['value'] = date('h:i a', strtotime($dateVar[$key2]));
                                                    $options['options'][$i]['print_value'] = date('h:i a', strtotime($dateVar[$key2]));
                                                    $options['options'][$i]['option_value'] = date('Y-m-d h:i:s', strtotime($dateVar[$key2]));
                                                    $options['options'][$i]['custom_view'] = false;
                                                }
                                                if ($options['options'][$i]['option_type'] == 'date_time') {
                                                    $options['options'][$i]['value'] = date('m/d/Y h:i A', strtotime($dateVar[$key2]));
                                                    $options['options'][$i]['print_value'] = date('m/d/Y h:i A', strtotime($dateVar[$key2]));
                                                    $options['options'][$i]['option_value'] = date('Y-m-d h:i:s', strtotime($dateVar[$key2]));
                                                    $options['options'][$i]['custom_view'] = false;
                                                }
                                                if ($options['options'][$i]['option_type'] == 'file') {
                                                    $fileVar[$key2]['url'] = array('route' => 'downloadOrderCustomOption/download/download', 'params' => array('id' => $item->getId(), 'key' => $fileVar[$key2]['secret_key']));
                                                    $options['options'][$i]['value'] = '<a href="' . $this->getUrl($fileVar[$key2]['url']['route'], $fileVar[$key2]['url']['params']) . '" target="_blank">' . $fileVar[$key2]['title'] . '</a> ';
                                                    $options['options'][$i]['print_value'] = $fileVar[$key2]['title'];
                                                    $options['options'][$i]['option_value'] = serialize($fileVar[$key2]);
                                                    $options['options'][$i]['custom_view'] = true;

                                                }
                                            } else {
                                                if ($optionTmp->getValues()) {
                                                    if (is_array($value2)) {
                                                        $titlesTmp = array();
                                                        $idTmp = array();
                                                        $tmpSku = array();
                                                        foreach ($optionTmp->getValues() as $k => $v) {
                                                            foreach ($value2 as $valueValue) {
                                                                if ($valueValue == $k) {
                                                                    $idTmp[] = $k;
                                                                    $titlesTmp[] = $v->getTitle();
                                                                    $tmpSku[] = $v->getSku();
                                                                }
                                                            }
                                                        }
                                                        $tmpResultSku = (empty($tmpResultSku)) ? $options['simple_sku'] . '-' . implode('-', $tmpSku) : $tmpResultSku . '-' . implode('-', $tmpSku);
                                                        $options['options'][$i]['value'] = implode(', ', $titlesTmp);
                                                        $options['options'][$i]['print_value'] = implode(', ', $titlesTmp);
                                                        $options['options'][$i]['option_value'] = implode(', ', $idTmp);
                                                        $options['options'][$i]['custom_view'] = false;
                                                    } else {
                                                        foreach ($optionTmp->getValues() as $v) {
                                                            if ($v['option_type_id'] == $updateOptions[$key2]) {
                                                                $options['options'][$i]['value'] = $v->getTitle();
                                                                $options['options'][$i]['print_value'] = $v->getTitle();
                                                                $options['options'][$i]['option_value'] = $v->getId();
                                                                $tmpResultSku = (empty($tmpResultSku)) ? $options['simple_sku'] . '-' . $v->getSku() : $tmpResultSku . '-' . $v->getSku();
                                                                $options['options'][$i]['custom_view'] = false;
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    $options['options'][$i]['value'] = $updateOptions[$key2];
                                                    $options['options'][$i]['print_value'] = $updateOptions[$key2];
                                                    $options['options'][$i]['option_value'] = $updateOptions[$key2];
                                                    $options['options'][$i]['custom_view'] = false;
                                                }
                                            }
                                            $i++;
                                            //--------options/options new processing end-----
                                        }
                                    }
                                }
                            }
                        }
                        if ($edited) {
                            $item->setProductOptions($options);
                            if (!(empty($tmpResultSku))) {
                                $item->setSku($tmpResultSku);
                            }
                            $item->save();
                            $edited = false;
                        }
                    }
                }
            } catch (Exception $e) {
                $result['message_errors'] = $e->getMessage();
            }

            $block = $this->getLayout()->getBlock('order_items');

            $result['message_html'] = $block->toHtml();

            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $order->getId())));
        }
    }
}