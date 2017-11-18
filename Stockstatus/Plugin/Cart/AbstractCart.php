<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Plugin\Cart;

class AbstractCart
{
    /**
     * @var \Amasty\Stockstatus\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Amasty\Stockstatus\Helper\Data $helper
    ) {
        $this->_helper = $helper;
    }

    public function aroundGetItemHtml(
        \Magento\Checkout\Block\Cart\AbstractCart $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $result = $proceed($item);
        if ($this->_helper->getModuleConfig('display/display_in_cart')) {
            $find   = '</strong>';
            $product = $item->getProduct();
            if ($product->getTypeId() == 'configurable' ) {
                $product = $item->getOptionByCode('simple_product')->getProduct();
            }
            $status = $this->_helper->getCartStockStatus($product);
            if ( $status) {
                $status = '<div style="background: #fdf0d5 none repeat scroll 0 0;padding: 12px;">' .
                        $status .
                    '</div>' ;
                $result = str_replace($find, $find . $status, $result);
            }
        }

        return $result;
    }
}

