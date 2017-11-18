<?php
    /**
     * @author    Amasty Team
     * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
     * @package   Amasty_Stockstatus
     */
    
    namespace Amasty\Stockstatus\Plugin\Product;
    
    use Pivotal\IndentOrders\Model\IndentOrders;
    
    class Status
    {
        
        /**
         * @var \Amasty\Stockstatus\Helper\Data
         */
        protected $_helper;
        
        /**
         * @var \Magento\Framework\App\Config\ScopeConfigInterface
         */
        protected $scopeConfig;
        
        /**
         * Added by Pivotal
         *
         * @var IndentOrders $indentOrders
         */
        protected $indentOrders;
        
        public function __construct(
            \Amasty\Stockstatus\Helper\Data $helper,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            IndentOrders $indentOrders
        ) {
            $this->_helper      = $helper;
            $this->_scopeConfig = $scopeConfig;
            
            $this->indentOrders = $indentOrders;
        }
        
        public function afterToHtml(
            \Magento\Catalog\Block\Product\AbstractProduct $subject,
            $result
        ) {
            $name = $subject->getNameInLayout();
            
            if (in_array($name,
                array('product.info.configurable', 'product.info.simple', 'product.info.type_schedule_block6', 'product.info.bundle', 'product.info.virtual', 'product.info.downloadble'))) {
                $status = $this->_helper->getCustomStockStatusText($subject->getProduct());
                if ($status) {
                    $tmp = $this->_helper->showStockStatus($subject->getProduct(), 1, 0);
                    /*if ($tmp != '') {*/
                        $result = $tmp;
                    //}
                }
            }
            
            return $result;
        }
    }
