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

final class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration')]
    public function index(): Response
    {
        // Page de choix du rôle (patient ou médecin)
        return $this->render('registration/choose_role.html.twig');
    }

    #[Route('/registration/patient', name: 'app_registration_patient')]
    public function registerPatient(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $patient = new Patient();
        $form = $this->createForm(PatientRegistrationTypeForm::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le plainPassword du formulaire non mappé
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $hasher->hashPassword($patient, $plainPassword);
            $patient->setPassword($hashedPassword);
            
            // Par défaut, définir le rôle patient
            $patient->setRoles(['ROLE_PATIENT']);

            $em->persist($patient);
            $em->flush();

            return $this->redirectToRoute('app_login');
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
