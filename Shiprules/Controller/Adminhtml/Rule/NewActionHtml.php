<?php
/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Controller\Adminhtml\Rule;


class NewActionHtml extends \Amasty\Shiprules\Controller\Adminhtml\Rule
{
    public function execute()
    {
        $this->newConditions('actions');
    }
}