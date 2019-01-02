<?php

namespace App\Repository;

use App\Entity\Gigel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Gigel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gigel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gigel[]    findAll()
 * @method Gigel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GigelRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Gigel::class);
    }

    // /**
    //  * @return Gigel[] Returns an array of Gigel objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Gigel
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
