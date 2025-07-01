<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Doctor;
use App\Form\ConsultationType;
use App\Repository\ConsultationRepository;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin')]
#[IsGranted('ROLE_DOCTOR')]
class MedecinController extends AbstractController
{
    #[Route('/dashboard', name: 'medecin_dashboard')]
    public function dashboard(
        Request $request,
        ConsultationRepository $consultationRepository
    ): Response {
        /** @var Doctor $user */
        $user = $this->getUser();
        
        if (!$user instanceof Doctor) {
            throw $this->createAccessDeniedException('Access denied. Doctor role required.');
        }
        $searchTerm = $request->query->get('search');
        $dateFilter = $request->query->get('date');
        
        $qb = $consultationRepository->createQueryBuilder('c')
            ->leftJoin('c.patient', 'p')
            ->where('c.doctor = :doctor')
            ->setParameter('doctor', $user)
            ->orderBy('c.date', 'DESC');

        if ($searchTerm) {
            $qb->andWhere('p.firstName LIKE :searchTerm OR p.lastName LIKE :searchTerm OR p.email LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if ($dateFilter) {
            $date = \DateTime::createFromFormat('Y-m-d', $dateFilter);
            if ($date) {
                $startDate = clone $date;
                $endDate = clone $date;
                $startDate->setTime(0, 0, 0);
                $endDate->setTime(23, 59, 59);
                
                $qb->andWhere('c.date BETWEEN :startDate AND :endDate')
                   ->setParameter('startDate', $startDate)
                   ->setParameter('endDate', $endDate);
            }
        }

        $consultations = $qb->getQuery()->getResult();

        return $this->render('medecin/dashboard.html.twig', [
            'consultations' => $consultations,
            'searchTerm' => $searchTerm,
            'selectedDate' => $dateFilter,
        ]);
    }

    #[Route('/consultation/new', name: 'medecin_consultation_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        PatientRepository $patientRepository
    ): Response {
        /** @var Doctor $user */
        $user = $this->getUser();
        
        if (!$user instanceof Doctor) {
            throw $this->createAccessDeniedException('Access denied. Doctor role required.');
        }
        $now = new \DateTimeImmutable();
        $consultation = new Consultation();
        $consultation->setDoctor($user);
        $consultation->setStatus(Consultation::STATUS_SCHEDULED);
        $consultation->setStartTime($now);
        $consultation->setEndTime($now->modify('+30 minutes'));
        $consultation->setDate($now);
        $consultation->setNotes('');

        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($consultation);
            $entityManager->flush();

            // Send email notification to patient
            $patient = $consultation->getPatient();
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@carnetsante.com', 'Carnet Santé'))
                ->to($patient->getEmail())
                ->subject('Nouvelle consultation programmée')
                ->htmlTemplate('emails/new_consultation.html.twig')
                ->context([
                    'patient' => $patient,
                    'consultation' => $consultation,
                    'doctor' => $user,
                ]);

            try {
                $mailer->send($email);
                $this->addFlash('success', 'La consultation a été créée et le patient a été notifié par email.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'La consultation a été créée mais l\'email de notification n\'a pas pu être envoyé.');
            }

            return $this->redirectToRoute('medecin_dashboard');
        }

        return $this->render('medecin/consultation/new.html.twig', [
            'consultation' => $consultation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/consultation/{id}/edit', name: 'medecin_consultation_edit')]
    public function edit(Request $request, Consultation $consultation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('medecin_dashboard');
        }

        return $this->render('medecin/consultation/edit.html.twig', [
            'consultation' => $consultation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/consultation/{id}', name: 'medecin_consultation_show', methods: ['GET'])]
    public function show(Consultation $consultation): Response
    {
        /** @var \App\Entity\Doctor $currentUser */
        $currentUser = $this->getUser();
        
        // Check if the current user is the doctor who owns this consultation
        if ($currentUser->getId() !== $consultation->getDoctor()->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette consultation.');
        }
        
        return $this->render('medecin/consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/consultation/{id}/delete', name: 'medecin_consultation_delete', methods: ['POST'])]
    public function delete(Request $request, Consultation $consultation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$consultation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($consultation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('medecin_dashboard');
    }
}
