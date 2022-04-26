<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Platform;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AccountFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(UserFixtures::REGULAR_USER_REFERENCE);

        /** @var Platform $platform */
        $platform = $this->getReference(PlatformFixtures::PLATFORM_TWITCH_REFERENCE);

        $account = new Account();

        $account
            ->setEmail('admin@example.com')
            ->setAccessToken('abcde123')
            ->setLinkedTo($user)
            ->setExternalId('123598')
            ->setPlatform($platform);
        $manager->persist($account);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PlatformFixtures::class,
        ];
    }
}
