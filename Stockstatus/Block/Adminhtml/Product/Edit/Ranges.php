<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Block\Adminhtml\Product\Edit;

class Ranges  extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Amasty\Stockstatus\Helper\Data
     */
    public $_helper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Amasty\Stockstatus\Helper\Data
     */

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    protected $_attrOptionCollectionFactory;

    /**
     * @var \Magento\Framework\Validator\UniversalFactory $universalFactory
     */
    protected $_universalFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection
     */
    protected $_ruleCollection;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection
     */
    protected $_optionsCollection;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    protected $_attributeObject;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var  \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Amasty\Stockstatus\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_coreRegistry                = $registry;
        $this->_attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->_universalFactory            = $universalFactory;
        $this->_helper                      = $helper;
        $this->_objectManager               = $objectManager;
        $this->scopeConfig                  = $context->getScopeConfig();
        $this->_jsonEncoder                 = $jsonEncoder;

        $this->_attributeObject = $this->_coreRegistry->registry('entity_attribute');
    }

    /**
     * Retrieve option values collection
     * It is represented by an array in case of system attribute
     *
     * @return array|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection
     */
    protected function _getOptionValuesCollection()
    {
        if (!$this->_optionsCollection) {
            $model = $this->_objectManager
                ->create('Magento\Catalog\Model\Product\Attribute\OptionManagement');

            $this->_optionsCollection = $model->getItems('custom_stock_status');
        }

        return $this->_optionsCollection;
    }

    public function getOptionValuesJson() {
        $data = [];
        $collection = $this-> _getOptionValuesCollection();
        foreach ($collection as $item) {
            $data[] = [
                'option_id' => $item['value'],
                'value'     => $item['label']
            ];
        };
        return $this->_jsonEncoder->encode($data);
    }

    public function getRuleValuesJson() {
        $data = [];
        $collection = $this-> _getRuleValuesCollection();
        foreach ($collection as $item) {
            $data[] = [
                'option_id' => $item['value'],
                'value'     => $item['label']
            ];
        }
        return $this->_jsonEncoder->encode($data);
    }

    /**
     * @return array|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection
     */
    protected function _getRuleValuesCollection()
    {
        if (!$this->_ruleCollection) {
            $model = $this->_objectManager
                ->create('Magento\Catalog\Model\Product\Attribute\OptionManagement');

            $this->_ruleCollection = $model->getItems('custom_stock_status_qty_rule');
        }

        return $this->_ruleCollection;
    }

    /**
     * @return array|\Amasty\Stockstatus\Model\ResourceModel\Ranges\Collection
     */
    public function getRanges()
    {
        $model = $this->_objectManager->create('Amasty\Stockstatus\Model\Ranges');
        $collection = $model->getCollection();
        $collection->getSelect()->order('qty_from');

        return $collection;
    }
}

