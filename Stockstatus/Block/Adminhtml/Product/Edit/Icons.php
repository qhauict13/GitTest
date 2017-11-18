<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Block\Adminhtml\Product\Edit;

class Icons  extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Amasty\Stockstatus\Helper\Image
     */
    public $_imageHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

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
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Amasty\Stockstatus\Helper\Image $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_coreRegistry                = $registry;
        $this->_imageHelper                 = $helper;
        $this->_objectManager               = $objectManager;
        $this->_jsonEncoder                 = $jsonEncoder;

        $this->_attributeObject = $this->_coreRegistry->registry('entity_attribute');
    }

    /**
     * Retrieve option values collection
     * It is represented by an array in case of system attribute
     *
     * @return array|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection
     */
    public function getOptionValuesCollection()
    {
        if (!$this->_optionsCollection) {
            $model = $this->_objectManager->create('Magento\Catalog\Model\Product\Attribute\OptionManagement');
            $this->_optionsCollection = $model->getItems('custom_stock_status');
        }

        return $this->_optionsCollection;
    }

    public function getIcon($optionId)
    {
        return $this->_imageHelper->getStatusIconUrl($optionId);
    }
}
