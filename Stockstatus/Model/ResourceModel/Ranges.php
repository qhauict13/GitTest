<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Model\ResourceModel;

class Ranges extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('amasty_stockstatus_quantityranges', 'entity_id');
    }

    /**
     * Load an object by qty and rule
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param mixed $qty
     * @param mixed $rule
     * @return $this
     */
    public function loadByQtyAndRule(\Magento\Framework\Model\AbstractModel $object, $qty, $rule)
    {
        $connection = $this->getConnection();
        if ($connection && $qty !== null) {
            $select = $this->getConnection()->select()->from(
                    $this->getMainTable(),
                    '*'
                )
                ->where($this->getMainTable() . '.' . 'qty_from' . '<= ?', $qty)
                ->where($this->getMainTable() . '.' . 'qty_to' .   '>= ?', $qty);
            if ($rule !== null) {
                $select->where($this->getMainTable() . '.'  .'rule' . '= ?', $rule);
            }

            $data = $connection->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    public function deleteAll()
    {
        $connection = $this->getConnection();
        $connection->delete($this->getMainTable());
    }
}
