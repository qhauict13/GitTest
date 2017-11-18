<?php
/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Plugin;


class ImportShippingRate
{
    public function aroundImportShippingRate(
        \Magento\Quote\Model\Quote\Address\Rate $subject,
        \Closure $closure,
        \Magento\Quote\Model\Quote\Address\RateResult\AbstractResult $rate
    )
    {
        $rateData = $closure($rate);;
        $rateData->setOldPrice($rate->getOldPrice());
        return $rateData;
    }
}