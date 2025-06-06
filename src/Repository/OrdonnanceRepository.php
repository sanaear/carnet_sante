<?php

namespace App\Repository;

use App\Entity\Ordonnance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ordonnance>
 *
 * @method Ordonnance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ordonnance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ordonnance[]    findAll()
 * @method Ordonnance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrdonnanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ordonnance::class);
    }

    /**
     * @return Ordonnance[] Returns an array of Ordonnance objects
     */
    public function findByConsultation($consultation): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.consultation = :consultation')
            ->setParameter('consultation', $consultation)
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
} 