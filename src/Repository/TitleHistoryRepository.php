<?php

namespace App\Repository;

use App\Entity\TitleHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TitleHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method TitleHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method TitleHistory[]    findAll()
 * @method TitleHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TitleHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TitleHistory::class);
    }
}
