<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryFixtures extends Fixture
{
    private $counter = 1;
    public function __construct(private SluggerInterface $slugger){}

    public function load(ObjectManager $manager): void
    {

        $parent = $this->createCategory('Informatique', null, $manager);
        $this->createCategory('Ordinateur-portable', $parent, $manager);
        $this->createCategory('Ecrant', $parent, $manager);
        $this->createCategory('Souris', $parent, $manager);
        $this->createCategory('Clavier', $parent, $manager);

        $parent = $this->createCategory('Mode', null, $manager);
        $this->createCategory('Homme', $parent, $manager);
        $this->createCategory('Femme', $parent, $manager);
        $this->createCategory('Enfant', $parent, $manager);

        $manager->flush();
    }
    public function createCategory(string $name, Category $parent = null, ObjectManager $manager) : Category 
    {
        $category = new Category();
        $category->setName($name)
        ->setSlug($this->slugger->slug($category->getName())->lower())
            ->setParent($parent);
        $manager->persist($category);
        $this->addReference('cat-'.$this->counter,$category);
        $this->counter++;
        return $category;
    }
}
