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

        // Use the entity *code* everywhere: 'catalog_product'
        $entityType    = Product::ENTITY;

        // Default product attribute set & group (e.g. "Default" set, "General" group)
        $attributeSetId = (int) $categorySetup->getDefaultAttributeSetId($entityType);
        $groupId        = (int) $categorySetup->getDefaultAttributeGroupId($entityType, $attributeSetId);

        $attrs = [
            'km_skill_level' => [
                'type'   => 'varchar',
                'label'  => 'Skill Level',
                'input'  => 'select',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                'option' => ['values' => ['Beginner', 'Intermediate', 'Advanced', 'Expert']],
            ],
            'km_years_experience' => [
                'type'  => 'int',
                'label' => 'Years of Experience',
                'input' => 'text',
            ],
            'km_tech_stack' => [
                'type'  => 'text',
                'label' => 'Tech Stack (comma separated)',
                'input' => 'textarea',
            ],
        ];

        foreach ($attrs as $code => $cfg) {
            // Create if missing
            if (!$eavSetup->getAttributeId($entityType, $code)) {
                $eavSetup->addAttribute(
                    $entityType,
                    $code,
                    array_merge([
                        'group'            => 'General', // or create your own "Skill Attributes" group later
                        'global'           => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible'          => 1,
                        'required'         => 0,
                        'user_defined'     => 1,
                        'searchable'       => 1,
                        'filterable'       => 0,
                        'comparable'       => 0,
                        'visible_on_front' => 1,
                        'unique'           => 0,
                        'apply_to'         => '',
                    ], $cfg)
                );
            }

            // Assign to default set & group
            $attrId = (int) $eavSetup->getAttributeId($entityType, $code);
            if ($attrId) {
                $categorySetup->addAttributeToSet($entityType, $attributeSetId, $groupId, $attrId);
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
