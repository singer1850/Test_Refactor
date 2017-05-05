<?php
class Test_Refactor_DownloadController extends Mage_Core_Controller_Front_Action
{
    protected function _getFileContent($path = '')
    {
        try {
            $fl = fopen($path, 'r');
            $readContent = fread($fl, filesize($path));
            fclose($fl);
        } catch (Exception $e) {
            $readContent = null;
            Mage::logException($e, Zend_Log::ERR);
        }
        return $readContent;
    }
    public function downloadAction()
    {
        $itemId = $this->getRequest()->getParam('id');
        $fileNameKey = $this->getRequest()->getParam('key');
        $path = '';
        $fileName = '';
        $item = Mage::getModel('sales/order_item')->load($itemId);
        $options = $item->getProductOptions();
        if ( (is_array($options['options'])) && (isset($options['options'])) && (!(empty($options['options']))) ) {
            foreach ($options['options'] as $optionKey => $optionValue) {
                if ($options['options'][$optionKey]['option_type'] == 'file') {
                    $tmpOption = unserialize($options['options'][$optionKey]['option_value']);
                    if ($tmpOption['secret_key'] == substr($fileNameKey, 0, 20)) {
                        $path = $tmpOption['fullpath'];
                        $fileName = $tmpOption['title'];
                    }
                }
            }
        }

        $content = $this->_getFileContent($path);
        if (!is_null($content)) {
            $this->_prepareDownloadResponse($fileName, $content);
        }
    }
}