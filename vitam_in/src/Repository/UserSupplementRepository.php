<?php

namespace App\Repository;

use App\Entity\UserSupplement;
use app\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSupplement>
 */
class UserSupplementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSupplement::class);
    }

       /**
        * @return UserSupplement[] Returns an array of UserSupplement objects 
        */
        public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('us')
            ->andWhere('us.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('us.reminders', 'r', 'WITH', 'r.is_active = true')   //filtramos solo activos 
            ->addSelect('r')                  // Cargamos los reminders en la misma consulta
            ->getQuery()
            ->getResult();
    }

    public function findForHistory(User $user): array
    {
        $qb = $this->createQueryBuilder('us')
            ->andWhere('us.user = :user')
            ->setParameter('user', $user)
            ->orderBy('us.start_date', 'DESC');

        return $qb->getQuery()->getResult();
    }

}
