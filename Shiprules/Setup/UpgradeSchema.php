<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Amasty\Shiprules\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;


class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addBackordersField($setup);
        }

        if (version_compare($context->getVersion(), '1.1.1', '<')) {
            $this->updateFieldToDecimal($setup, 'rate_percent');
            $this->updateFieldToDecimal($setup, 'handling');
        }

        $setup->endSetup();
    }

    protected function addBackordersField(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('amasty_shiprules_rule'),
            'out_of_stock',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Apply to backoerders'
            ]
        );
    }

    private function updateFieldToDecimal($setup, $field) {
        $setup->getConnection()->changeColumn(
            $setup->getTable('amasty_shiprules_rule'),
            $field,
            $field,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,2',
                'nullable' => false,
                'unsigned' => true,
                'default' => '0.00'
            ]
        );
    }
}
