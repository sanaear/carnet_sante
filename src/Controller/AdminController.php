<?php
// src/Controller/AdminController.php
namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Doctor;
use App\Form\AdminProfileFormType;
use App\Form\ChangePasswordType;
use App\Form\PatientRegistrationTypeForm;
use App\Form\DoctorRegistrationTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\PatientRepository;
use App\Repository\DoctorRepository;



#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
#[Route('/profil', name: 'admin_profile')]
#[IsGranted('ROLE_ADMIN')]
public function profile(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher
): Response {
    /** @var \App\Entity\User $admin */
    $admin = $this->getUser();

    // Formulaire profil
    $profileForm = $this->createForm(AdminProfileFormType::class, $admin);
    $profileForm->handleRequest($request);

    if ($profileForm->isSubmitted() && $profileForm->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Profil mis à jour avec succès.');
        return $this->redirectToRoute('admin_profile');
    }

    // Formulaire mot de passe
    $passwordForm = $this->createForm(ChangePasswordType::class);
    $passwordForm->handleRequest($request);

    if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
        $plainPassword = $passwordForm->get('newPassword')->getData();
        $admin->setPassword($passwordHasher->hashPassword($admin, $plainPassword));
        $em->flush();
        $this->addFlash('password_success', 'Mot de passe mis à jour avec succès.');
        return $this->redirectToRoute('admin_profile');
    }

    return $this->render('admin/profile.html.twig', [
        'profileForm' => $profileForm->createView(),
        'passwordForm' => $passwordForm->createView(),
    ]);
}

    #[Route('/dashboard', name: 'app_admin_dashboard')]
  public function dashboard(Request $request, PatientRepository $patientRepo, DoctorRepository $doctorRepo): Response
{
    $type = $request->query->get('type');

    $patients = $type === 'doctor' ? [] : $patientRepo->findAll();
    $doctors = $type === 'patient' ? [] : $doctorRepo->findAll();

    $totalUsers = count($patients) + count($doctors);

    return $this->render('admin/dashboard.html.twig', [
        'patients' => $patients,
        'doctors' => $doctors,
        'totalUsers' => $totalUsers,
        'totalPatients' => count($patients),
        'totalDoctors' => count($doctors),
    ]);
}

    #[Route('/patient/add', name: 'admin_patient_add')]
public function addPatient(Request $request,EntityManagerInterface $em,UserPasswordHasherInterface $passwordHasher
): Response {
    $patient = new Patient();
    $form = $this->createForm(PatientRegistrationTypeForm::class, $patient);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        // Récupère le mot de passe en clair du formulaire
        $plainPassword = $form->get('plainPassword')->getData();

        // Encode le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($patient, $plainPassword);
        $patient->setPassword($hashedPassword);

        // Définit le rôle
        $patient->setRoles(['ROLE_PATIENT']);

        // Sauvegarde en base
        $em->persist($patient);
        $em->flush();

        return $this->redirectToRoute('app_admin_dashboard');
    }

    return $this->render('admin/user_form.html.twig', [
        'form' => $form->createView(),
        'type' => 'patient'
    ]);
}

    #[Route('/doctor/add', name: 'admin_doctor_add')]
   public function addDoctor(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher
): Response {
    $doctor = new Doctor();
    $form = $this->createForm(DoctorRegistrationTypeForm::class, $doctor);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        // Récupère le mot de passe en clair du formulaire
        $plainPassword = $form->get('plainPassword')->getData();

        // Encode le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($doctor, $plainPassword);
        $doctor->setPassword($hashedPassword);

        // Définit le rôle
        $doctor->setRoles(['ROLE_DOCTOR']);

        // Sauvegarde en base
        $em->persist($doctor);
        $em->flush();

        return $this->redirectToRoute('app_admin_dashboard');
    }

    return $this->render('admin/user_form.html.twig', [
        'form' => $form->createView(),
        'type' => 'doctor'
    ]);
}

    #[Route('/patient/edit/{id}', name: 'admin_patient_edit')]
    public function editPatient(Patient $patient, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PatientRegistrationTypeForm::class, $patient);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'type' => 'patient'
        ]);
    }

    #[Route('/doctor/edit/{id}', name: 'admin_doctor_edit')]
    public function editDoctor(Doctor $doctor, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DoctorRegistrationTypeForm::class, $doctor);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'type' => 'doctor'
        ]);
    }

    #[Route('/patient/delete/{id}', name: 'admin_patient_delete')]
    public function deletePatient(Patient $patient, EntityManagerInterface $em): Response
    {
        // Remove all related consultations
        foreach ($patient->getConsultations() as $consultation) {
            $em->remove($consultation);
        }
        
        // Remove all related medical files
        foreach ($patient->getMedicalFiles() as $medicalFile) {
            $em->remove($medicalFile);
        }
        
        // Now it's safe to remove the patient
        $em->remove($patient);
        $em->flush();
        
        $this->addFlash('success', 'Le patient a été supprimé avec succès.');
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/doctor/delete/{id}', name: 'admin_doctor_delete')]
    public function deleteDoctor(Doctor $doctor, EntityManagerInterface $em): Response
    {
        // First, handle or remove related consultations
        foreach ($doctor->getConsultations() as $consultation) {
            // Option 1: Set doctor to null if your business logic allows it
            // $consultation->setDoctor(null);
            // $em->persist($consultation);
            
            // Option 2: Or remove the consultation completely
            $em->remove($consultation);
        }
        
        // Handle or remove related medical files
        foreach ($doctor->getMedicalFiles() as $medicalFile) {
            // Option 1: Set doctor to null if your business logic allows it
            // $medicalFile->setDoctor(null);
            // $em->persist($medicalFile);
            
            // Option 2: Or remove the medical file completely
            $em->remove($medicalFile);
        }
        
        // Now it's safe to remove the doctor
        $em->remove($doctor);
        $em->flush();
        
        $this->addFlash('success', 'Le médecin a été supprimé avec succès.');
        return $this->redirectToRoute('app_admin_dashboard');
    }
}
