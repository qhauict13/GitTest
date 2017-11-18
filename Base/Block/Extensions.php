<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Base
 */

namespace Amasty\Base\Block;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Json\DecoderInterface;

class Extensions extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $_layoutFactory;
    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $_moduleReader;
    /**
     * @var DecoderInterface
     */
    protected $_jsonDecoder;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_filesystem;
    /**
     * @var \Amasty\Base\Helper\Module
     */
    protected $_moduleHelper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Filesystem\Driver\File $filesystem,
        \Amasty\Base\Helper\Module $moduleHelper,
        DecoderInterface $jsonDecoder,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);

        $this->_moduleList    = $moduleList;
        $this->_layoutFactory = $layoutFactory;
        $this->_moduleReader  = $moduleReader;
        $this->_jsonDecoder   = $jsonDecoder;
        $this->_filesystem    = $filesystem;
        $this->_moduleHelper  = $moduleHelper;
        $this->_scopeConfig   = $context->getScopeConfig();
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $modules = $this->_moduleList->getNames();

        $dispatchResult = new \Magento\Framework\DataObject($modules);
        $modules = $dispatchResult->toArray();

        sort($modules);
        foreach ($modules as $moduleName) {
            if (strstr($moduleName, 'Amasty_') === false
                || $moduleName === 'Amasty_Base'
            ) {
                continue;
            }

            $html .= $this->_getFieldHtml($element, $moduleName);
        }

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $layout = $this->_layoutFactory->create();

            $this->_fieldRenderer = $layout->createBlock(
                'Magento\Config\Block\System\Config\Form\Field'
            );
        }

        return $this->_fieldRenderer;
    }



    /**
     * @param $fieldset
     * @param $moduleCode
     * @return string
     */

        // in case if module output disabled
        if ($this->_scopeConfig->getValue('advanced/modules_disable_output/' . $moduleCode)) {
            $status =
                '<a href="' . $url . '" target="_blank">
                        <img src="' . $this->getViewFileUrl('Amasty_Base::images/bad.gif') .
                            '" alt="' . __("Output disabled") . '" title="'. __("Output disabled")
                .'"/></a>';
        }

        $moduleName = $status . ' ' . $moduleName;

        $field = $fieldset->addField($moduleCode, 'label', array(
            'name'  => 'dummy',
            'label' => $moduleName,
            'value' => $currentVer,
        ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * @param $moduleName
     * @return mixed
     */
    protected function _replaceAmastyText($moduleName)
    {
        $moduleName = str_replace('for Magento 2', '', $moduleName);
        $moduleName = str_replace('by Amasty', '', $moduleName);

        return $moduleName;
    }
}
