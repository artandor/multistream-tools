<?php

namespace App\Tests\Entity;

use App\Entity\TitleHistory;
use PHPUnit\Framework\TestCase;

class TitleHistoryTest extends TestCase
{
    public function testTitleHistoryToString()
    {
        $title = new TitleHistory();
        $title->setTitle('Hello there');
        $title->setCategory('General Kenobi: The game');
        $title->setCreatedAt(new \DateTimeImmutable('2022-04-03 19:30:00'));

        $this->assertEquals('Sunday 03/Apr | Hello there / General Kenobi: The game', $title);
    }
}
