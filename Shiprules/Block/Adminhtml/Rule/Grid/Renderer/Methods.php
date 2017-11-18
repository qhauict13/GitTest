<?php
/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Block\Adminhtml\Rule\Grid\Renderer;

class Methods extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    public function render(\Magento\Framework\DataObject $row)
    {
        $methods = $row->getData('methods');
        if (!$methods) {
            return __('Any');
        }
        return nl2br($methods);
    }

}