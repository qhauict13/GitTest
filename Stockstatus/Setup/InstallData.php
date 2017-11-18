<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */

namespace Amasty\Stockstatus\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    protected $_eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'custom_stock_status',
            [
                'type'                      => 'int',
                'backend'                   => '',
                'frontend'                  => '',
                'label'                     => 'Custom Stock Status',
                'input'                     => 'select',
                'class'                     => '',
                'source'                    => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'global'                    => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
                'visible'                   => true,
                'used_in_product_listing'   => true,
                'required'                  => false,
                'user_defined'              => true,
                'default'                   => '',
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'unique'                    => false,
                'apply_to'                  => ''
            ]
        );
        $attributeIdStockStatus = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Product::ENTITY,
            'custom_stock_status'
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'custom_stock_status_qty_based',
            [
                'type'                      => 'int',
                'backend'                   => '',
                'frontend'                  => '',
                'label'                     => 'Use Quantity Ranges Based Stock Status',
                'input'                     => 'select',
                'class'                     => '',
                'source'                    => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'                    => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
                'visible'                   => true,
                'used_in_product_listing'   => true,
                'required'                  => false,
                'user_defined'              => false,
                'default'                   => '',
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'unique'                    => false,
                'apply_to'                  => ''
            ]
        );
        $attributeIdQtyBased = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Product::ENTITY,
            'custom_stock_status_qty_based'
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'custom_stock_status_qty_rule',
            [
                'type'                      => 'int',
                'backend'                   => '',
                'frontend'                  => '',
                'label'                     => 'Custom Stock Status Qty Rule',
                'input'                     => 'select',
                'class'                     => '',
                'source'                    => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'global'                    => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
                'visible'                   => true,
                'used_in_product_listing'   => true,
                'required'                  => false,
                'user_defined'              => true,
                'default'                   => '',
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'unique'                    => false,
                'apply_to'                  => ''
            ]
        );
        $attributeIdQtyRule = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Product::ENTITY,
            'custom_stock_status_qty_rule'
        );

        foreach (
            $eavSetup->getAllAttributeSetIds(
                \Magento\Catalog\Model\Product::ENTITY
            ) as $attributeSetId
        ) {
            try {
                $attributeGroupId = $eavSetup->getAttributeGroupId(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeSetId,
                    'General'
                );
            } catch (\Exception $e) {
                $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeSetId
                );
            }
            /*add custom_stock_status attribute to attribute set*/
            $eavSetup->addAttributeToSet(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeSetId,
                $attributeGroupId,
                $attributeIdStockStatus
            );
            /*add custom_stock_status_qty_based attribute to attribute set*/
            $eavSetup->addAttributeToSet(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeSetId,
                $attributeGroupId,
                $attributeIdQtyBased
            );
            /*add custom_stock_status_qty_rule attribute to attribute set*/
            $eavSetup->addAttributeToSet(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeSetId,
                $attributeGroupId,
                $attributeIdQtyRule
            );
        }

        $tableName = $setup->getTable('amasty_stockstatus_quantityranges');
        $setup->run("
            CREATE TABLE IF NOT EXISTS `{$tableName}`  (
                `entity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `qty_from` INT NOT NULL ,
                `qty_to` INT NOT NULL ,
                `rule` INT NULL,
                `status_id` INT UNSIGNED NOT NULL
            ) ENGINE = InnoDB ;
        ");

    }
}

