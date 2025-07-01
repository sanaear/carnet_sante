<?php

namespace App\Repository;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Entity\Ordonnance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 *
 * @method Consultation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Consultation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Consultation[]    findAll()
 * @method Consultation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }

    /**
     * @return Consultation[] Returns an array of Consultation objects
     */
    public function findByPatientOrderedByDate(Patient $patient): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('c.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Consultation[]
     */
    public function findOrdonnanceByIdAndPatient(int $id, Patient $patient): ?array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'o')
            ->leftJoin('c.ordonnance', 'o')
            ->where('c.id = :id')
            ->andWhere('c.patient = :patient')
            ->andWhere('o.id IS NOT NULL')
            ->setParameter('id', $id)
            ->setParameter('patient', $patient)
            ->getQuery()
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function findByFilters(Patient $patient, string $year = 'all', string $type = 'all', string $doctor = 'all'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.ordonnance', 'o')
            ->addSelect('o')
            ->andWhere('c.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('c.date', 'DESC');

        if ($year !== 'all') {
            $qb->andWhere('YEAR(c.date) = :year')
               ->setParameter('year', $year);
        }

        if ($type === 'with_ordonnance') {
            $qb->andWhere('c.ordonnance IS NOT NULL');
        } elseif ($type === 'without_ordonnance') {
            $qb->andWhere('c.ordonnance IS NULL');
        }

        if ($doctor !== 'all') {
            $qb->join('c.doctor', 'd')
               ->andWhere('d.lastName LIKE :doctor')
               ->setParameter('doctor', '%' . $doctor . '%');
        }

        return $qb->getQuery()->getResult();
    }
} 