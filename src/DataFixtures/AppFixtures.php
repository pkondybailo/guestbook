<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $amsterdam = new Conference();
        $amsterdam
            ->setCity('Amsterdam')
            ->setSlug('amsterdam-2020')
            ->setYear('2020')
            ->setIsInternational(false);
        $manager->persist($amsterdam);

        $paris = new Conference();
        $paris
            ->setCity('Paris')
            ->setYear('2023')
            ->setSlug('paris-2023-international')
            ->setIsInternational(true);
        $manager->persist($paris);

        $comment1 = new Comment();
        $comment1
            ->setEmail('johndoe@mail.com')
            ->setAuthor('John Doe')
            ->setText('That was the great conference!')
            ->setConference($amsterdam)
            ->setCreatedAtValue();
        $manager->persist($comment1);

        $admin = new Admin();
        $admin
            ->setUsername('admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasherFactory->getPasswordHasher(Admin::class)->hash('hooray'));
        $manager->persist($admin);

        $manager->flush();
    }
}
