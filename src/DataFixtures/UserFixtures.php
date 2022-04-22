<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user
            ->setEmail('admin@example.com')
            ->setPassword('seCrEt')
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setPassword('seCrEt');
        $manager->persist($user);

        $manager->flush();
    }
}
