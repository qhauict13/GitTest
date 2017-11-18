<?php
/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Model;

use Magento\Catalog\Model\Product\Type as ProductType;

/**
 * Class Validator
 * @package Amasty\Shiprules\Model
 */
class Validator extends \Magento\Framework\DataObject
{
    protected $adjustments = [];

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    private $couponModel;

    public function __construct(
        \Magento\SalesRule\Model\Coupon $couponModel,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    )
    {
        $this->objectManager = $objectManager;
        parent::__construct($data);
        $this->couponModel = $couponModel;
    }

    /**
     * @param $request
     * @return $this
     */
    public function init($request)
    {
        $this->setRequest($request);
        return $this;
    }

    /**
     * @param $rates
     * @return $this
     */
    public function applyRulesTo($rates)
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateRequest $request */
        $request = $this->getRequest();

        $affectedIds = array();

        foreach ($rates as $rate) {
            $this->adjustments[$this->getKey($rate)] = array(
                'fee' => 0,
                'totals' => $this->initTotals(),
                'ids' => array(),
            );
            $affectedIds[$this->getKey($rate)] = [];
        }

        /** @var \Amasty\Shiprules\Model\Rule $rule */
        foreach ($this->getValidRules() as $rule) {
            $rule->setFee(0);

            $validItems = [];

            /** We need to get all items passed by actions */
            foreach ($request->getAllItems() as $item) {
                if (!($rule->getActions()->validate($item))) {
                    if ($item->getProduct()->getTypeId() == 'configurable') {
                        foreach ($item->getChildren() as $child) {
                            if ($rule->getActions()->validate($child)) {
                                $validItems[$item->getItemId()] = $item;
                            }
                        }
                    }
                    continue;
                }
                $validItems[$item->getItemId()] = $item;
            }


            if (!$validItems) {
                continue;
            }

            $subTotals = $this->aggregateTotals($validItems, $request->getFreeShipping());
            if ($rule->validateTotals($subTotals)) {

                $rule->calculateFee($subTotals, $request->getFreeShipping());

                /** Get all rules for rates */
                foreach ($rates as $rate) {


                    $currentItemsIds = array_keys($validItems);
                    $oldIds = $affectedIds[$this->getKey($rate)];
                    if ($rule->match($rate) && !count(array_intersect($currentItemsIds, $oldIds))) {

                        $affectedIds[$this->getKey($rate)] = array_merge($currentItemsIds, $oldIds);

                        $currentAdjustment = $this->adjustments[$this->getKey($rate)];
                        $currentAdjustment['fee'] += $rule->getFee();


                        $handling = $rule->getHandling(); // new field
                        if (is_numeric($handling)) {
                            if ($rule->getCalc() == \Amasty\Shiprules\Model\Rule::CALC_DEDUCT) {
                                $currentAdjustment['fee'] -= $rate->getPrice() * $handling / 100;
                            } else {
                                $currentAdjustment['fee'] += $rate->getPrice() * $handling / 100;
                            }
                        }

                        if ($rule->removeFromRequest()) {
                            // remember removed group totals
                            foreach ($subTotals as $k => $value) {
                                if (isset($currentAdjustment['totals'][$k])) {
                                    $currentAdjustment['totals'][$k] += $value;
                                }
                            }
                            // remember removed group ids
                            $currentAdjustment['ids'] = array_merge($currentAdjustment['ids'], array_keys($validItems));
                        }//if remove

                        if ($rule->getRateMax() > 0) {
                            $currentAdjustment['fee'] = ($currentAdjustment['fee'] > 0 ? 1 : -1) * min(abs($currentAdjustment['fee']), $rule->getRateMax());
                        }

                        if ($rule->getRateMin() > 0) {
                            if ($rule->getCalc() == \Amasty\Shiprules\Model\Rule::CALC_DEDUCT) {
                                //add min rate change negative for discount action
                                $currentAdjustment['fee'] = ($currentAdjustment['fee'] <= 0 ? -1 : 1) * max(abs($currentAdjustment['fee']), $rule->getRateMin());
                            } else {
                                //add min rate change positive for other actions
                                $currentAdjustment['fee'] = ($currentAdjustment['fee'] >= 0 ? 1 : -1) * max(abs($currentAdjustment['fee']), $rule->getRateMin());
                            }
                        }
                        if ($rule->getShipMin() > 0) {
                            if ($rate->getCost() + $currentAdjustment['fee'] < $rule->getShipMin()) {
                                $currentAdjustment['fee'] = $rule->getShipMin() - $rate->getCost();
                            }
                        }

                        if ($rule->getShipMax() > 0) {
                            if ($rate->getCost() + $currentAdjustment['fee'] > $rule->getShipMax()) {
                                $currentAdjustment['fee'] = $rule->getShipMax() - $rate->getCost();
                            }
                        }

                        $this->adjustments[$this->getKey($rate)] = $currentAdjustment;

                    }
                }
            }// if group totals valid
        }// foreach rule

        //$newRequest = $this->getModifiedRequest($request, $idsToRemove, $totalsToDeduct);
        return $this;
    }

    /**
     * Does rate need update
     *
     * @param $rate
     * @return bool|int
     */
    public function needNewRequest($rate)
    {
        $k = $this->getKey($rate);
        if (empty($this->adjustments[$k])) {
            return false;
        }

        return (count($this->adjustments[$k]['ids']));
    }

    public function getNewRequest($rate)
    {
        $a = $this->adjustments[$this->getKey($rate)];

        $totalsToDeduct = $a['totals'];
        $idsToRemove = $a['ids'];

        $newRequest = clone $this->getRequest();

        $newItems = array();
        foreach ($newRequest->getAllItems() as $item) {
            $id = $item->getItemId();
            if (in_array($id, $idsToRemove)) {
                //continue;
            }
            $newItems[] = $item;
        }
        $newRequest->setAllItems($newItems);

        $newRequest->setPackageValue($newRequest->getPackageValue() - $totalsToDeduct['price']);
        $newRequest->setPackageWeight($newRequest->getPackageWeight() - $totalsToDeduct['weight']);
        $newRequest->setPackageQty($newRequest->getPackageQty() - $totalsToDeduct['qty']);
        $newRequest->setFreeMethodWeight($newRequest->getFreeMethodWeight() - $totalsToDeduct['not_free_weight']);

        //@todo - calculate discount?
        $newRequest->setPackageValueWithDiscount($newRequest->getPackageValue());
        $newRequest->setPackagePhysicalValue($newRequest->getPackageValue());

        return $newRequest;
    }

    /**
     * @param $rates
     * @return bool
     */
    public function canApplyFor($rates)
    {
        //@todo check for free shipping

        /** @var \Magento\Quote\Model\Quote\Address\RateRequest $request */
        $request = $this->getRequest();

        if (!count($request->getAllItems())) {
            return false;
        }

        /** Can't apply for virtual quote */
        $firstItem = current($request->getAllItems());
        if ($firstItem->getQuote()->isVirtual()) {
            return false;
        }
        
        $rules = $this->getAllRules();
        /** @var \Amasty\Shiprules\Model\Rule $rule */
        foreach ($rules as $rule) {
            foreach ($rates as $rate) {
                if ($rule->match($rate)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get valid rules for current request. Save valid rules to hash.
     *
     * @return array|mixed
     */
    public function getValidRules()
    {
        $request = $this->getRequest();

        $hash = $this->getAddressHash($request);
        if ($this->getData('rules_by_' . $hash)) {
            return $this->getData('rules_by_' . $hash);
        }

        $validRules = [];
        foreach ($this->getAllRules() as $rule) {
            /** @var $rule \Amasty\Shiprules\Model\Rule */
            $rule->afterLoad();
            /** Validate rule by coupon code and conditions */
            if ($this->isCouponValid($request, $rule) && $rule->validate($request)) {
                $validRules[] = $rule;
            }
        }

        $this->setData('rule_by_' . $hash, $validRules);

        return $validRules;
    }

    public function isCouponValid($request, $rule)
    {
        $actualCouponCode = trim(strtolower($rule->getCoupon()));
        $actualDiscountId = intVal($rule->getDiscountId());

        if (!$actualCouponCode && !$actualDiscountId)
            return true;

        $providedCouponCodes = $this->getCouponCodes($request);

        if ($actualCouponCode) {
            return (in_array($actualCouponCode, $providedCouponCodes));
        }

        if ($actualDiscountId) {
            foreach ($providedCouponCodes as $code) {
                $couponModel = $this->couponModel->load($code, 'code');
                $providedDiscountId = $couponModel->getRuleId();

                if ($providedDiscountId == $actualDiscountId) {
                    return true;
                }
                $couponModel = null;
            }

        }

        return false;
    }

    public function getCouponCodes($request)
    {
        if (!count($request->getAllItems()))
            return array();

        $firstItem = current($request->getAllItems());
        $codes = trim(strtolower($firstItem->getQuote()->getCouponCode()));

        if (!$codes)
            return array();

        $providedCouponCodes = explode(",", $codes);

        foreach ($providedCouponCodes as $key => $code) {
            $providedCouponCodes[$key] = trim($code);
        }

        return $providedCouponCodes;

    }


    public function getAllRules()
    {
        if (!$this->getData('rules_all')) {
            $request = $this->getRequest();

            $hasBackOrders = false;
            foreach ($request->getAllItems() as $item) {
                if ($item->getBackorders() > 0) {
                    $hasBackOrders = true;
                    break;
                }
            }
            $backOrdersCondition = array(Rule::ALL_ORDERS);
            if ($hasBackOrders) {
                $backOrdersCondition[] = Rule::BACKORDERS_ONLY;
            } else {
                $backOrdersCondition[] = Rule::NON_BACKORDERS;
            }
            $collection = $this->objectManager->create('Amasty\Shiprules\Model\Rule')
                ->getCollection()
                ->addFieldToFilter('is_active', 1)
                ->addStoreFilter($request->getStoreId())
                ->addCustomerGroupFilter($this->getCustomerGroupId())
                ->addDaysFilter()
                ->addFieldToFilter('out_of_stock', array('in' => $backOrdersCondition))
                ->setOrder('pos', 'asc')
                ->load();
            $this->setData('rules_all', $collection);
        }

        return $this->getData('rules_all');
    }

    public function getCustomerGroupId()
    {
        $request = $this->getRequest();
        $groupId = 0;

        $firstItem = current($request->getAllItems());
        if ($firstItem->getQuote()->getCustomerId()) {
            $groupId = $firstItem->getQuote()->getCustomer()->getGroupId();
        }

        return $groupId;
    }

    public function getAddressHash($request)
    {
        $addressCondition = $this->objectManager->create('Amasty\Shiprules\Model\Rule\Condition\Address');
        $addressAttributes = $addressCondition->loadAttributeOptions()->getAttributeOption();

        $hash = '';
        foreach ($addressAttributes as $code => $label) {
            $hash .= $request->getData($code) . $label;
        }

        return md5($hash);
    }

    public function aggregateTotals($validItems, $isFree)
    {
        $totals = $this->initTotals();

        foreach ($validItems as $item) {

            if (
                $item->getParentItem() && $item->getParentItem()->getProductType() == ProductType::TYPE_BUNDLE
                || $item->getProduct()->isVirtual()
            ) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getProduct()->isVirtual()) {
                        continue;
                    }

                    $qty = $item->getQty() * $child->getQty();
                    $notFreeQty = $item->getQty() * ($qty - $this->getFreeQty($child));

                    $totals['qty'] += $qty;
                    $totals['not_free_qty'] += $notFreeQty;

                    $totals['price'] += $child->getBaseRowTotal();
                    $totals['not_free_price'] += $child->getBasePrice() * $notFreeQty;

                    if (!$item->getProduct()->getWeightType()) {
                        $totals['weight'] += $child->getWeight() * $qty;
                        $totals['not_free_weight'] += $child->getWeight() * $notFreeQty;
                    }
                }
                if ($item->getProduct()->getWeightType()) {
                    $totals['weight'] += $item->getWeight() * $item->getQty();
                    $totals['not_free_weight'] += $item->getWeight() * ($item->getQty() - $this->getFreeQty($item));
                }
            } else { // normal product

                $qty = $item->getQty();
                $notFreeQty = ($qty - $this->getFreeQty($item));

                $totals['qty'] += $qty;
                $totals['not_free_qty'] += $notFreeQty;

                $totals['price'] += $item->getBaseRowTotal();
                $totals['not_free_price'] += $item->getBasePrice() * $notFreeQty;

                $totals['weight'] += $item->getWeight() * $qty;
                $totals['not_free_weight'] += $item->getWeight() * $notFreeQty;

            } // if normal products
        }// foreach

        if ($isFree) {
            $totals['not_free_price'] = $totals['not_free_weight'] = $totals['not_free_qty'] = 0;
        }

        return $totals;
    }

    public function getFreeQty($item)
    {
        $freeQty = 0;
        if ($item->getFreeShipping()) {
            $freeQty = (is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : $item->getQty());
        }
        return $freeQty;
    }

    public function initTotals()
    {
        $totals = array(
            'price' => 0,
            'not_free_price' => 0,
            'weight' => 0,
            'not_free_weight' => 0,
            'qty' => 0,
            'not_free_qty' => 0,
        );
        return $totals;
    }

    public function getKey($rate)
    {
        return $rate->getCarrier() . '~' . $rate->getMethod();
    }

    public function findRate($newRates, $rate)
    {
        foreach ($newRates as $r) {
            if ($this->getKey($r) == $this->getKey($rate)) {
                return $r;
            }
        }
        // @todo return error?
        return $rate;
    }

    public function getFee($rate)
    {
        $k = $this->getKey($rate);
        if (empty($this->adjustments[$k]))
            return 0;

        return $this->adjustments[$k]['fee'];
    }
}
