<?php
/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Block\Adminhtml;

class Rule extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'rule';
        $this->_headerText = __('Shipping Rules');
        $this->_addButtonLabel = __('Add Rule');
        parent::_construct();
    }
}