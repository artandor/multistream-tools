<?php

namespace App\DataFixtures;

use App\Entity\Platform;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PlatformFixtures extends Fixture
{
    public const PLATFORM_TWITCH_REFERENCE = 'twitch-platform';

    public function load(ObjectManager $manager)
    {
        $platform = new Platform();

        $platform
            ->setName('Twitch')
            ->setPath('connect_twitch_start')
            ->setProvider('App\Provider\TwitchProvider')
            ->setEnabled(true)
            ->setImage('https://images-eu.ssl-images-amazon.com/images/I/21kRx-CJsUL.png');
        $this->addReference(self::PLATFORM_TWITCH_REFERENCE, $platform);

        $manager->persist($platform);

        $manager->flush();
    }
}
