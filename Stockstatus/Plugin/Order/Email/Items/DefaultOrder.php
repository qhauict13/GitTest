<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Plugin\Order\Email\Items;

class DefaultOrder
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

    public function afterToHtml(
        \Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder $subject,
        $result
    ) {
        if ($this->_helper->getModuleConfig('display/display_in_email'))
        {
            $find   = '<p class="sku">';
            $status = $this->_helper->getCartStockStatus($subject->getItem()->getProduct());
            if ( $status) {
                $status = '<p>' . $status . '</p>' ;
                $result = str_replace($find, $status . $find, $result);
            }

        }

        return $result;
    }
}

