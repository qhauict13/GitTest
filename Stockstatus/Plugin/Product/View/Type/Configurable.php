<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Plugin\Product\View\Type;

use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Pivotal\IndentOrders\Model\IndentOrders;

class Configurable
{
    protected $_objectManager;
    protected $_helper;
    protected $_jsonEncoder;
    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $catalogProduct;
    
    /**
     * Added by Pivotal
     *
     * @var IndentOrders $indentOrders
     */
    protected $indentOrders;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Amasty\Stockstatus\Helper\Data $_helper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        IndentOrders $indentOrders
    ) {
        $this->_objectManager = $objectManager;
        $this->_helper = $_helper;
        $this->_jsonEncoder = $jsonEncoder;
        $this->catalogProduct = $catalogProduct;
        
        $this->indentOrders = $indentOrders;
    }

    public function beforeGetAllowProducts(
        $subject
    ) {
        if (!$this->indentOrders->isIndent()) {
    
            if (!$subject->hasAllowProducts()) {
                $products          = [];
                $skipSaleableCheck = $this->catalogProduct->getSkipSaleableCheck();
                if ($this->_helper->getModuleConfig('general/outofstock')) {
                    $skipSaleableCheck = true;
                }
                $allProducts = $subject->getProduct()->getTypeInstance()->getUsedProducts($subject->getProduct(), null);
                foreach ($allProducts as $product) {
                    if ($product->isSaleable() || $skipSaleableCheck) {
                        $products[] = $product;
                    }
                }
                $subject->setAllowProducts($products);
            }
        }

        return [];
    }

    public function afterToHtml(
        $subject,
        $html
    ) {
        if (!$this->indentOrders->isIndent()) {
    
            if (in_array(
                    $subject->getNameInLayout(),
                    ['product.info.options.configurable', 'product.info.options.swatches']
                )
                && strpos($html, 'amstockstatusRenderer.init') === false
            ) {
                $instance    = $subject->getProduct()->getTypeInstance(true);
                $allProducts = $instance->getUsedProducts($subject->getProduct());
                $_attributes = $instance->getConfigurableAttributes($subject->getProduct());
        
                $aStockStatus = [];
                foreach ($allProducts as $product) {
            
                    $key = [];
                    foreach ($_attributes as $attribute) {
                        $key[] = $product->getData(
                            $attribute->getData('product_attribute')->getData(
                                'attribute_code'
                            )
                        );
                    }
            
                    if ($key) {
                        $saleable = $product->isSaleable();
                        $key      = implode(',', $key);
                
                        $aStockStatus[$key] = [
                            'is_in_stock'             => intval($saleable),
                            'custom_status'           => $this->_helper->getCustomStockStatusText($product),
                            'custom_status_icon'      => $this->_helper->getStatusIconImage($product),
                            'custom_status_icon_only' =>
                                intval($this->_helper->getModuleConfig('amstockstatus/general/icon_only')),
                            'product_id'              => $product->getId()
                        ];
                        if (!$saleable) {
                            $aStockStatus[$key]['stockalert'] =
                                $this->_helper->getStockAlert($product);
                        }
                
                        if (!$aStockStatus[$key]['is_in_stock'] && !$aStockStatus[$key]['custom_status']) {
                            $aStockStatus[$key]['custom_status'] = __('Out of Stock');
                        }
                        $pos = strrpos($key, ",");
                
                        if ($pos) {
                            $newKey = substr($key, 0, $pos);
                            if (array_key_exists($newKey, $aStockStatus)) {
                                if ($aStockStatus[$newKey]['custom_status'] != $aStockStatus[$key]['custom_status']) {
                                    $aStockStatus[$newKey] = null;
                                }
                            } else {
                                $aStockStatus[$newKey] = $aStockStatus[$key];
                            }
                        }
                    }
                }
        
                $aStockStatus['changeConfigurableStatus'] =
                    intval($this->_helper->getModuleConfig("general/change_custom_configurable_status"));
                $aStockStatus['type']                     = $subject->getNameInLayout();
                $data                                     = $this->_jsonEncoder->encode($aStockStatus);
        
                $html .=
                    '<script>
                    require(["jquery", "jquery/ui", "amstockstatusRenderer"],
                    function ($, ui, amstockstatusRenderer) {
                        amstockstatusRenderer.init(' . $data . ');
                    });
                </script>';
        
            }
        }
        return $html;
    }
}
