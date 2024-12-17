<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private SluggerInterface $slugger
        ){}
    public function load(ObjectManager $manager): void
    {
        $admin = new User;
        $admin->setEmail('admin@admin.com')
        ->setLastName('Rodriguez')
        ->setFirstname('Nicolas')
        ->setAddress('résidence Itylon')
        ->setZipcode('20130')
        ->setCity('Cargèse')
        ->setPassword($this->hasher->hashPassword($admin,'admin'))
        ->setRoles(['ROLE_ADMIN']);

        $manager->persist($admin);

        $faker = Faker\Factory::create('fr_FR');

        for($usr = 0; $usr < 5 ; $usr++)
        {
            $user = new User;
        $user->setEmail($faker->email)
        ->setLastName($faker->lastName)
        ->setFirstname($faker->firstName)
        ->setAddress($faker->address)
        ->setZipcode(str_replace(' ','',$faker->postcode))
        ->setCity($faker->city)
        ->setPassword($this->hasher->hashPassword($user,'user'));

        $manager->persist($user);
        }

        $manager->flush();
    }
}
