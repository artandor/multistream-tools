<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public const REGULAR_USER_REFERENCE = 'regular-user';

    public function load(ObjectManager $manager): void
    {
        $userAdmin = new User();
        $userAdmin
            ->setEmail('admin@example.com')
            ->setPassword('seCrEt')
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($userAdmin);

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setPassword('seCrEt');
        $this->addReference(self::REGULAR_USER_REFERENCE, $user);
        $manager->persist($user);

        $manager->flush();
    }
}
