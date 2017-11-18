<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Plugin\Product;

use Pivotal\IndentOrders\Model\IndentOrders;

class ListProduct
{
    /**
     * @var \Amasty\Stockstatus\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
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
        $this->_helper = $helper;
        $this->_scopeConfig = $scopeConfig;
        
        $this->indentOrders = $indentOrders;
    }

    public function aroundGetProductDetailsHtml(
        $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product
    ) {
    
            $html = $proceed($product);
            if (!$this->indentOrders->isIndent()) {
                if ($this->_helper->getModuleConfig('display/display_on_category')) {
                    $html .= $this->_helper->showStockStatus($product, 1, 1);
                }
            }
    
            return $html;
    }

    public function afterToHtml(
        $subject,
        $result
    ) {
        if ($this->_helper->getModuleConfig('display/display_on_category'))
        {
            $result .= '
                <script type="text/javascript">
                    require([
                        "jquery"
                    ], function($) {
                        $(".amstockstatus").each(function(i, item) {
                            var parent = $(item).parents(".item").first();
                            parent.find(".actions .stock").remove();
                        })
                    });
                </script>
            ';
        }

        return $result;
    }
}

