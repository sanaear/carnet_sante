<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Form\ChangePasswordType;
use App\Form\PatientProfileType;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
#[Route('/patient')]
class PatientController extends AbstractController
{
    #[Route('/dashboard', name: 'app_patient_dashboard')]
    public function dashboard(): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();

        return $this->render('patient/dashboard.html.twig', [
            'patient' => $patient,
        ]);
    }

    #[Route('/history', name: 'app_patient_history')]
    public function history(Request $request, ConsultationRepository $consultationRepository): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();

        $year = $request->query->get('year', 'all');
        $type = $request->query->get('type', 'all');
        $doctor = $request->query->get('doctor', 'all');

        $consultations = $consultationRepository->findByFilters($patient, $year, $type, $doctor);

        return $this->render('patient/history.html.twig', [
            'patient' => $patient,
            'consultations' => $consultations,
            'currentYear' => (new \DateTime())->format('Y'),
            'selectedYear' => $year,
            'selectedType' => $type,
            'selectedDoctor' => $doctor,
        ]);
    }

    #[Route('/profile', name: 'app_patient_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        
        // Profile form
        $profileForm = $this->createForm(PatientProfileType::class, $patient);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_patient_profile');
        }

        // Password form
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $data = $passwordForm->getData();
            
            // Verify current password
            if (!$passwordHasher->isPasswordValid($patient, $data['currentPassword'])) {
                $this->addFlash('password_error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_patient_profile');
            }

            // Update password
            $patient->setPassword(
                $passwordHasher->hashPassword($patient, $data['newPassword'])
            );
            
            $entityManager->flush();
            $this->addFlash('password_success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_patient_profile');
        }

        return $this->render('patient/profile.html.twig', [
            'patient' => $patient,
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route('/ordonnance/{id}/download', name: 'app_patient_ordonnance_download')]
    public function downloadOrdonnance(int $id, ConsultationRepository $consultationRepository): Response
    {
        /** @var Patient $patient */
        $patient = $this->getUser();
        $consultation = $consultationRepository->find($id);

        if (!$consultation || $consultation->getPatient() !== $patient || !$consultation->getOrdonnance()) {
            throw $this->createNotFoundException('Ordonnance not found');
        }

        $filePath = $consultation->getOrdonnance()->getFilePath();
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'ordonnance_' . $consultation->getDate()->format('Y-m-d') . '.pdf'
        );

        return $response;
    }
} 