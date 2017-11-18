<?php
/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Plugin;

use Amasty\Shiprules\Model\Validator;

class Shipping
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    
    /**
     * @var Validator
     */

    public function aroundCollectRates(
        \Magento\Shipping\Model\Shipping $subject,
        \Closure $closure,
        \Magento\Quote\Model\Quote\Address\RateRequest $request
    )
    {
        $closure($request);

        $result   = $subject->getResult();

        $oldRates = $result->getAllRates();
        $oldPrices = $this->_getPrices($oldRates);
        $newRates = [];
        
        $this->validator->init($request);
        
        if (!$this->validator->canApplyFor($oldRates)){
            return $subject;
        }
        
        $this->validator->applyRulesTo($oldRates);
        foreach ($oldRates as $rate){
            if ($this->validator->needNewRequest($rate)){

                $newRequest = $this->validator->getNewRequest($rate);
                if (count($newRequest->getAllItems())){

                    $result->reset();
                    $closure($newRequest);

                    $rate = $this->validator->findRate($result->getAllRates(), $rate);
                }
                else {
                    $rate->setPrice(0);
                }
            }
            $rate->setPrice($rate->getPrice() + $this->validator->getFee($rate));
            $newRates[] = $rate;
        }

        $result->reset();
        foreach ($newRates as $rate) {
            $rate->setOldPrice($oldPrices[$rate->getMethod()]);
            $rate->setPrice(max(0, $rate->getPrice()));
            $result->append($rate);
        }
        return $subject;
    }

    /**
     * Get All Rates Prices
     *
     * @param $rates
     * @return array
     */
    protected function _getPrices($rates)
    {
        $prices = [];
        foreach ($rates as $rate) {
            $prices[$rate->getMethod()] = $rate->getPrice();
        }
        return $prices;
    }
}
