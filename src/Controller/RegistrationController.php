<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Doctor;
use App\Form\PatientRegistrationTypeForm;
use App\Form\DoctorRegistrationTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;

final class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration')]
    public function index(): Response
    {
        // Page de choix du rôle (patient ou médecin)
        return $this->render('registration/acceuil.html.twig');
    }

    #[Route('/registration/patient', name: 'app_registration_patient')]
    public function registerPatient(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, UserRepository $userRepository): Response
    {
        $patient = new Patient();
        $form = $this->createForm(PatientRegistrationTypeForm::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $email = $form->get('email')->getData();
                    
                    // Check if email already exists
                    $existingUser = $userRepository->findOneBy(['email' => $email]);
                    if ($existingUser) {
                        $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez en utiliser une autre.');
                        return $this->redirectToRoute('app_registration_patient');
                    }
                    
                    // Get form data
                    $plainPassword = $form->get('plainPassword')->getData();
                    
                    // Set patient data
                    $hashedPassword = $hasher->hashPassword($patient, $plainPassword);
                    $patient->setPassword($hashedPassword);
                    $patient->setRoles(['ROLE_PATIENT']);
                    $patient->setCreatedAt(new \DateTimeImmutable());
                    
                    // Persist and flush
                    $em->persist($patient);
                    $em->flush();

                    $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_login');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du compte : ' . $e->getMessage());
                }
            } else {
                // Form is not valid, collect all errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                if (!empty($errors)) {
                    $this->addFlash('error', 'Veuillez corriger les erreurs suivantes : ' . implode(' ', $errors));
                }
            }
        }

        return $this->render('registration/patient.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/registration/doctor', name: 'app_registration_doctor')]
    public function registerDoctor(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $doctor = new Doctor();
        $form = $this->createForm(DoctorRegistrationTypeForm::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $hasher->hashPassword($doctor, $plainPassword);
            $doctor->setPassword($hashedPassword);

            // Définir le rôle médecin
            $doctor->setRoles(['ROLE_MEDECIN']);

            $em->persist($doctor);
            $em->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/doctor.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
