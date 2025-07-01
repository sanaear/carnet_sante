<?php

namespace App\Repository;

use App\Entity\Ordonnance;
use App\Entity\Consultation;
use App\Entity\Patient;
use App\Entity\Doctor;
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
     * Find ordonnances for a specific consultation
     */
    public function findByConsultation(Consultation $consultation): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.consultation = :consultation')
            ->setParameter('consultation', $consultation)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ordonnances for a specific patient
     */
    public function findByPatient(Patient $patient): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.consultation', 'c')
            ->andWhere('c.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ordonnances created by a specific doctor
     */
    public function findByDoctor(Doctor $doctor): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.consultation', 'c')
            ->andWhere('c.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the most recent ordonnances (for dashboard)
     */
    public function findRecent(int $maxResults = 5): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count ordonnances for statistics
     */
    public function countOrdonnances(): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find ordonnances for a specific patient ordered by consultation date
     */
    public function findByPatientOrderedByDate(Patient $patient): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.consultation', 'c')
            ->where('c.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('c.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Ordonnance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ordonnance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}