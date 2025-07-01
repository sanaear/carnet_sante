<?php

namespace App\Controller\Patient;

use App\Entity\Ordonnance;
use App\Repository\OrdonnanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/patient/ordonnance')]
class OrdonnanceController extends AbstractController
{
    #[Route('/list', name: 'patient_ordonnance_list')]
    public function list(OrdonnanceRepository $ordonnanceRepository): Response
    {
        $patient = $this->getUser();
        $ordonnances = $ordonnanceRepository->findByPatientOrderedByDate($patient);

        return $this->render('patient/ordonnance/list.html.twig', [
            'ordonnances' => $ordonnances,
        ]);
    }

    #[Route('/download/{id}', name: 'patient_ordonnance_download')]
    public function download(Ordonnance $ordonnance): BinaryFileResponse
    {
        // Vérifier que le patient est bien le propriétaire de l'ordonnance
        $this->denyAccessUnlessGranted('VIEW', $ordonnance);

        $filePath = $this->getParameter('kernel.project_dir').'/public/uploads/ordonnances/'.$ordonnance->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier de l\'ordonnance est introuvable.');
            throw $this->createNotFoundException('Fichier non trouvé');
        }

        try {
            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('ordonnance-%d-%s.pdf', 
                    $ordonnance->getId(),
                    (new \DateTime())->format('Y-m-d')
                )
            );
            $response->headers->set('Content-Type', 'application/pdf');
            
            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du téléchargement du fichier.');
            throw $this->createNotFoundException('Impossible de télécharger le fichier');
        }
    }
}