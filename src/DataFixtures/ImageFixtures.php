<?php

namespace App\DataFixtures;

use App\Entity\Image;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class ImageFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private SluggerInterface $slugger) {}
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($img = 0; $img < 100; $img++) {
            $image = new Image;
            $product= $this->getReference('prod-' . rand(1, 20), Product::class);
            $image->setName($faker->imageUrl())                
                ->setProduct($product);

            $manager->persist($image);
        }

        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [
            ProductFixtures::class
        ];
    }
}
