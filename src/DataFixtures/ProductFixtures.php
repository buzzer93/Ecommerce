<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class ProductFixtures extends Fixture
{
    private $counter = 1;
    public function __construct(private SluggerInterface $slugger) {}
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($prdct = 0; $prdct < 20; $prdct++) {
            $product = new Product;
            $category = $this->getReference('cat-' . rand(1, 9), Category::class);
            $product->setName($faker->text(10))
                ->setSlug($this->slugger->slug($product->getName())->lower())
                ->setPrice($faker->numberBetween(900, 150000))
                ->setStock($faker->numberBetween(0, 10))
                ->setDescription($faker->text(200))
                ->setCategory($category);

            $this->addReference('prod-' . $this->counter, $product);
            $this->counter++;
            $manager->persist($product);
        }

        $manager->flush();
    }
}
