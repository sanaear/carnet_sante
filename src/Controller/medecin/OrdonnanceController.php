<?php

namespace App\Controller\medecin;

use App\Entity\Ordonnance;
use App\Entity\Consultation;
use App\Form\OrdonnanceType;
use App\Form\GenerateOrdonnanceType;
use App\Service\OrdonnancePdfGenerator;
use Knp\Snappy\Pdf as KnpSnappyPdf;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Entity\Patient;
use App\Entity\Doctor;
use Psr\Log\LoggerInterface;

#[Route('/medecin/ordonnance')]
class OrdonnanceController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/test-pdf/{id}', name: 'test_pdf_generation')]
    public function testPdfGeneration(
        int $id,
        EntityManagerInterface $entityManager,
        OrdonnancePdfGenerator $pdfGenerator
    ): Response {
        // Find an existing ordonnance or create a test one
        $ordonnance = $entityManager->getRepository(Ordonnance::class)->find($id);

        if (!$ordonnance) {
            // Create a test ordonnance if none exists
            $consultation = $entityManager->getRepository(Consultation::class)->findOneBy([]);

            if (!$consultation) {
                // Create a minimal test consultation if none exists
                $patient = $entityManager->getRepository(Patient::class)->findOneBy([]);
                if (!$patient) {
                    $patient = new Patient();
                    $patient->setFirstName('Test');
                    $patient->setLastName('Patient');
                    $patient->setEmail('test.patient@example.com');
                    $patient->setPassword('$2y$13$yourhashedpassword'); // You should use a proper password hasher in production
                    $entityManager->persist($patient);
                }

                $consultation = new Consultation();
                $consultation->setPatient($patient);
                $consultation->setDate(new \DateTime());
                $consultation->setDescription('Consultation de test');
                $entityManager->persist($consultation);
            }

            $ordonnance = new Ordonnance();
            $ordonnance->setConsultation($consultation);
            $ordonnance->setContent('Contenu de test pour l\'ordonnance');
            $entityManager->persist($ordonnance);
            $entityManager->flush();
        }

        // Generate and return the PDF
        return $pdfGenerator->generatePdf($ordonnance);
    }
    #[Route('/generate/{id}', name: 'medecin_ordonnance_generate', methods: ['GET', 'POST'])]
    public function generate(
        Request $request,
        Consultation $consultation,
        EntityManagerInterface $em,
        KnpSnappyPdf $pdf
    ): Response {
        // Check if the doctor is the owner of the consultation
        $user = $this->getUser();
        if (!$user || $consultation->getDoctor()->getUserIdentifier() !== $user->getUserIdentifier()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à créer une ordonnance pour cette consultation.');
        }

        // Check if an ordonnance already exists
        if ($consultation->getOrdonnance()) {
            $this->addFlash('warning', 'Une ordonnance existe déjà pour cette consultation.');
            return $this->redirectToRoute('medecin_consultation_show', ['id' => $consultation->getId()]);
        }

        $ordonnance = new Ordonnance();
        $ordonnance->setConsultation($consultation);
        $form = $this->createForm(GenerateOrdonnanceType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $ordonnance->setIsGenerated(true);
                $em->persist($ordonnance);
                $em->flush();

                $this->addFlash('success', 'L\'ordonnance a été générée avec succès.');
                return $this->redirectToRoute('medecin_consultation_show', ['id' => $consultation->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la génération de l\'ordonnance.');
            }
        }

        return $this->render('medecin/ordonnance/generate.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation,
        ]);
    }

    #[Route('/delete/{id}', name: 'medecin_ordonnance_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Ordonnance $ordonnance,
        EntityManagerInterface $em,
        UploaderHelper $uploaderHelper
    ): Response {
        $consultation = $ordonnance->getConsultation();
        // Vérifier que l'utilisateur actuel est le médecin qui a créé la consultation
        $user = $this->getUser();
        if (!$user || $consultation->getDoctor()->getUserIdentifier() !== $user->getUserIdentifier()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette ordonnance.');
        }
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete' . $ordonnance->getId(), $request->request->get('_token'))) {
            try {
                // Just remove the entity, no file deletion logic
                $em->remove($ordonnance);
                $em->flush();
                $this->addFlash('success', 'L\'ordonnance a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'ordonnance.');
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }
        return $this->redirectToRoute('medecin_consultation_show', ['id' => $consultation->getId()]);
    }

    #[Route('/{id}/view', name: 'medecin_ordonnance_view', methods: ['GET'])]
    public function view(
        Ordonnance $ordonnance
    ): Response {
        $this->denyAccessUnlessGranted('VIEW', $ordonnance);
        return $this->render('medecin/ordonnance/view.html.twig', [
            'ordonnance' => $ordonnance,
            'consultation' => $ordonnance->getConsultation(),
        ]);
    }

    #[Route('/{id}/download', name: 'medecin_ordonnance_download', methods: ['GET'])]
    public function download(
        Ordonnance $ordonnance,
        OrdonnancePdfGenerator $pdfGenerator,
        UploaderHelper $uploaderHelper
    ): Response {
        // Check if user is authenticated and has the right to view this ordonnance
        $this->denyAccessUnlessGranted('VIEW', $ordonnance);

        // Get current user and verify they are a doctor
        $currentUser = $this->getUser();
        $currentDoctor = $currentUser instanceof Doctor ? $currentUser : null;

        if (!$currentDoctor) {
            throw $this->createAccessDeniedException('Seuls les médecins peuvent accéder à cette fonctionnalité.');
        }

        // Verify the ordonnance is associated with the current doctor
        if ($ordonnance->getConsultation()->getDoctor() !== $currentDoctor) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette ordonnance.');
        }

        try {
            // Handle generated ordonnances (PDFs generated by the system)
            if ($ordonnance->isGenerated()) {
                return $pdfGenerator->generatePdf($ordonnance);
            }

            // Handle uploaded ordonnances (PDFs uploaded by the doctor)
            $filePath = $this->getParameter('kernel.project_dir') . '/public' . $uploaderHelper->asset($ordonnance, 'file');

            if (!file_exists($filePath)) {
                $this->logger->error(sprintf('Ordonnance file not found: %s', $filePath));
                throw $this->createNotFoundException('Le fichier de l\'ordonnance est introuvable.');
            }

            // Generate a safe filename
            $patient = $ordonnance->getConsultation()->getPatient();
            $filename = sprintf(
                'ordonnance-%s-%s-%d.pdf',
                $patient->getLastName(),
                $patient->getFirstName(),
                $ordonnance->getId()
            );

            // Sanitize filename
            $filename = preg_replace('/[^a-zA-Z0-9-_.]/', '_', $filename);

            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );

            // Set appropriate content type based on file extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            $response->headers->set('Content-Type', $mimeTypes[$extension] ?? 'application/octet-stream');

            return $response;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error downloading ordonnance %d: %s', $ordonnance->getId(), $e->getMessage()));
            $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'ordonnance.');

            // Redirect back to the consultation page
            return $this->redirectToRoute('medecin_consultation_show', [
                'id' => $ordonnance->getConsultation()->getId()
            ], Response::HTTP_SEE_OTHER);
        }
    }
}
