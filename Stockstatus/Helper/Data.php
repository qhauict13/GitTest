<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Helper;

use Pivotal\IndentOrders\Model\IndentOrders;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    protected $_registry;
    protected $_resultPageFactory;
    protected $_objectManager;
    protected $_messageManager;
    protected $_scopeConfig;
    protected $_statusId = null;
    public    $_imageHelper;
    protected $_rangesFactory;
    protected $_optionManagementFactory;
    protected $_stockRegistry;
    protected $indentOrder;
    const ONE_DAY = 86400;//1 Day = 24*60*60 = 86400;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Amasty\Stockstatus\Helper\Image $imageHelper,
        \Magento\ProductAlert\Helper\Data $helper,
        \Amasty\Stockstatus\Model\RangesFactory $rangesFactory,
        \Magento\Catalog\Model\Product\Attribute\OptionManagementFactory $optionManagementFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\App\Helper\Context $context,
        IndentOrders $indentOrder
    ) {
        parent::__construct($context);
        $this->_registry = $registry;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_objectManager = $objectManager;
        $this->_messageManager = $messageManager;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_localeDate = $localeDate;
        $this->_imageHelper = $imageHelper;
        $this->_rangesFactory = $rangesFactory;
        $this->_optionManagementFactory = $optionManagementFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->_helper = $helper;
        $this->indentOrder = $indentOrder;
    }

    public function getRulesEnabled()
    {
        return $this->getModuleConfig('general/use_range_rules');
    }

    public function getModuleConfig($path) {
        return $this->_scopeConfig->getValue('amstockstatus/' . $path);
    }

    public function getStockAlert(\Magento\Catalog\Model\Product $product)
    {
        $html = "";
        if ($this->getModuleConfig('general/stockalert')) {
            $tempCurrentProduct = $this->_registry->registry('current_product');
            $this->_registry->unregister('current_product');
            $this->_registry->register('current_product', $product);

            $pageResult = $this->_resultPageFactory->create();
            $alertBlock = $pageResult->getLayout()->createBlock(
                'Magento\ProductAlert\Block\Product\View', 'productalert.stock.' . $product->getId()
            );
            $alertBlock->setTemplate('Magento_ProductAlert::product/view.phtml');
            $alertBlock->setSignupUrl($this->_helper->getSaveUrl('stock'));
            $alertBlock->setHtmlClass('alert stock link-stock-alert');
            $alertBlock->setSignupLabel(__('Sign up to get notified when this configuration is back in stock'));

            $html = $alertBlock->toHtml();

            $this->_registry->unregister('current_product');
            $this->_registry->register('current_product', $tempCurrentProduct);
        }

        return $html;
    }

    public function showStockStatus(\Magento\Catalog\Model\Product $product, $addWrapper = false, $isProductList = false)
    {
        if (!$this->indentOrder->isIndent()) {
            $status = $this->_getCustomStockStatus($product);
            if (!$status) {
                return "";
            }
    
            if ($product->getIsSalable()) {
                $result = __('In stock');
            } else {
                $result = __('Out of stock');
            }
            if ($isProductList || $this->getModuleConfig('general/hide_default_status')) {
                $result = '';
            }
    
            $result = $result . ' ' . $status;
    
            if ($addWrapper) {
                $result = '<div class="stock">' . $result . '</div>';
            }
    
            return $result;
            
        } else {
            return "
            ";
        }
    }

    public function getStatusIconImage()
    {
        if ($iconUrl = $this->_imageHelper->getStatusIconUrl($this->getCustomStockStatusId()))
        {
            return '<img src="' . $iconUrl . '" class="amstockstatus_icon" alt="" title="">';
        }

        return "";
    }

    protected function _getCustomStockStatus(\Magento\Catalog\Model\Product $product, $qty=0)
    {
        $result = "";
        if ( !$this->getModuleConfig('general/displayforoutonly') || !$product->getIsSalable())
        {
            if ($status = $this->getCustomStockStatusText( $product, $qty )) {
                $result = '<span class="amstockstatus amsts_' . $this->getCustomStockStatusId() . '">' .
                    $status .
                    '</span>';

                if ( $this->getModuleConfig('general/icon_only') ) {
                    $result = $this->getStatusIconImage();
                }
                else {
                    $result = $this->getStatusIconImage() . $result;
                }
            }
        }
        return $result;
    }

    public function getCustomStockStatusText(\Magento\Catalog\Model\Product $product, $qty=0)
    {
        if (!$product || !$product->getId()) {
            return false;
        }

        $status          = '';
        $quantity        = null;
        $this->_statusId = null;

        if ( $product->getData('custom_stock_status_qty_based') )
        {
            $quantity = $this->_getProductQty($product);

            //load status from our model
            $rule = ( $this->getModuleConfig('general/use_range_rules') &&
                $product->getData('custom_stock_status_qty_rule') )?
                $product->getData('custom_stock_status_qty_rule'):
                null;
            $rangeModel = $this->_rangesFactory->create();
            $rangeModel->loadByQtyAndRule( $quantity + $qty, $rule);

            if ($rangeModel->hasData('status_id'))
            {
                $this->_statusId = $rangeModel->getData('status_id');

                // getting status for range
                $optionManagement = $this->_optionManagementFactory->create();
                foreach ( $optionManagement->getItems('custom_stock_status') as $option )
                {
                    if ($this->_statusId == $option['value'])
                    {
                        $status = $option['label'];
                        break;
                    }
                }
            }
        }

        if ( '' == $status && !$this->getModuleConfig('general/use_ranges_only') )
        {
            $status = $product->getAttributeText('custom_stock_status');
            $this->_statusId = $product->getData('custom_stock_status');
        }

        if (false !== strpos($status, '{qty}')) {
            if (!$quantity) {
                $quantity = $this->_getProductQty($product);
            }

            $status = str_replace('{qty}', intval($quantity  + $qty), $status);
        }

        if (!$status) {
            return '';
        }

        $status = $this->_replaceCustomDates($status, self::ONE_DAY, "tomorrow", 1);
        $status = $this->_replaceCustomDates($status, 2 * self::ONE_DAY, "day-after-tomorrow", 1);
        $status = $this->_replaceCustomDates($status, -self::ONE_DAY, "yesterday", 1);

        // search for atttribute entries
        preg_match_all('@\{(.+?)\}@', $status, $matches);
        if (isset($matches[1]) && !empty($matches[1]))
        {
            foreach ($matches[1] as $match)
            {
                if ($value = $product->getData($match))
                {
                    if (preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})/", $value))
                    {
                        $value = $this->_localeDate->formatDateTime(
                            new \DateTime($value),
                            \IntlDateFormatter::MEDIUM,
                            \IntlDateFormatter::NONE
                        );
                    }
                    $status = str_replace('{' . $match . '}', $value, $status);
                }
                else{
                    $status = str_replace('{' . $match . '}', "", $status);
                }
            }
        }

        return $status;
    }

    public function getCustomStockStatusId()
    {
        return $this->_statusId;
    }

    protected function _replaceCustomDates($status, $time, $name, $excludeSunday)
    {
        $pattern = '@\{' . $name . '\}@';
        preg_match_all($pattern, $status, $matches);
        if (isset($matches[0]) && !empty($matches[0]))
        {
            foreach ($matches[0] as $match)
            {
                if ($excludeSunday && date('w', time() + $time) == 0) {
                    $time += self::ONE_DAY;
                }
                $value = date("d-m-Y", time() + $time);

                $value = $this->_localeDate->formatDateTime(
                    new \DateTime($value),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::NONE
                );

                $status = str_replace( $match , $value, $status);
            }
        }

        return $status;
    }

    public function getCartStockStatus(\Magento\Catalog\Model\Product $product)
    {
        $productModel = $this->_objectManager->create('Magento\Catalog\Model\Product');
        $product = $productModel->load($product->getId());
        return $this->showStockStatus($product, 1 , 0);
    }

    protected function _getProductQty(\Magento\Catalog\Model\Product $product)
    {
        if ($product->getTypeId() == 'configurable' ) {
            //get total qty for configurable product as summ from simple
            $collection = $product->getTypeInstance(true)
                ->getUsedProducts($product);
            $quantity = 0;
            foreach($collection as $simple) {
                $stockItem = $this->_stockRegistry->getStockItem($simple->getId());

                $simpleQty = $stockItem->getQty();
                if ($simpleQty > 0) {
                    $quantity += $simpleQty;
                }
            }
        }
        else {
            $stockItem = $this->_stockRegistry->getStockItem($product->getId());

            $quantity = $stockItem->getQty();

        }

        return  intval($quantity);
    }
}
