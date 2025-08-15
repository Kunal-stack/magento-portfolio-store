<?php
declare(strict_types=1);

namespace KunalMagento\SkillCatalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InstallSkillAttributes implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private CategorySetupFactory $categorySetupFactory
    ) {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup      = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Always use an existing, valid set to avoid "incorrect set id" errors
        $entityTypeId   = (int)$categorySetup->getEntityTypeId(Product::ENTITY);
        $attributeSetId = (int)$categorySetup->getDefaultAttributeSetId($entityTypeId); // e.g., 4 "Default"
        $groupId        = (int)$categorySetup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

        $attrs = [
            'km_skill_level' => [
                'type'   => 'varchar',
                'label'  => 'Skill Level',
                'input'  => 'select',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                'option' => ['values' => ['Beginner','Intermediate','Advanced','Expert']]
            ],
            'km_years_experience' => [
                'type'  => 'int',
                'label' => 'Years of Experience',
                'input' => 'text'
            ],
            'km_tech_stack' => [
                'type'  => 'text',
                'label' => 'Tech Stack (comma separated)',
                'input' => 'textarea'
            ],
        ];

        foreach ($attrs as $code => $cfg) {
            // Create if missing
            if (!$eavSetup->getAttributeId($entityTypeId, $code)) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    $code,
                    array_merge([
                        'group'            => 'General',
                        'global'           => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible'          => 1,
                        'required'         => 0,
                        'user_defined'     => 1,
                        'searchable'       => 1,
                        'filterable'       => 0,
                        'comparable'       => 0,
                        'visible_on_front' => 1,
                        'unique'           => 0,
                        'apply_to'         => 'virtual'
                    ], $cfg)
                );
            }

            // Assign to an actually existing set+group (avoid name strings here; use IDs)
            $attrId = (int)$eavSetup->getAttributeId($entityTypeId, $code);
            if ($attrId) {
                $categorySetup->addAttributeToSet($entityTypeId, $attributeSetId, $groupId, $attrId);
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }
    public function getAliases(): array
    {
        return [];
    }
}
