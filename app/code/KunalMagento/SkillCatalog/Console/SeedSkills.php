<?php
declare(strict_types=1);

namespace KunalMagento\SkillCatalog\Console;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedSkills extends Command
{
    public function __construct(
        private State $state,
        private ProductFactory $productFactory,
        private ProductRepositoryInterface $productRepo
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('kunalmagento:skills:seed')
            ->setDescription('Seed virtual products that represent skills');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode('adminhtml');

        $skills = [
            ['sku' => 'SKILL-M2',      'name' => 'Magento 2 (Adobe Commerce)'],
            ['sku' => 'SKILL-KAFKA',   'name' => 'Kafka / Event-Driven Commerce'],
            ['sku' => 'SKILL-AWS',     'name' => 'AWS for E-commerce'],
            ['sku' => 'SKILL-SECURE',  'name' => '2FA / Secure Login'],
            ['sku' => 'SKILL-RECS',    'name' => 'Product Recommendation AI'],
        ];

        foreach ($skills as $s) {
            $product = $this->productFactory->create();
            $product->setSku($s['sku'])
                ->setName($s['name'])
                ->setAttributeSetId(4) // or your 'Skill' set id if different
                ->setStatus(1)
                ->setVisibility(4)
                ->setTypeId('virtual')
                ->setPrice(0) // free “inquiry”
                ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1, 'qty' => 9999])
                ->setCustomAttribute('km_skill_level', 'Expert')
                ->setCustomAttribute('km_years_experience', 4)
                ->setCustomAttribute('km_tech_stack', 'PHP, Magento, Kafka, AWS, Python');
            $this->productRepo->save($product);
            $output->writeln("Seeded: {$s['sku']}");
        }

        return Cli::RETURN_SUCCESS;
    }
}
