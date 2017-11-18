<?php
/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Controller\Adminhtml\Rule;


use Magento\Framework\App\ResponseInterface;

class NewAction extends \Amasty\Shiprules\Controller\Adminhtml\Rule
{

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}