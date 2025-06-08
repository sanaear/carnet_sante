<?php
namespace App\Controller\Patient;

use App\Entity\Ordonnance;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/patient/ordonnance')]
class OrdonnanceController extends AbstractController
{
    #[Route('/list', name: 'patient_ordonnance_list')]
    public function list(): Response
    {
        $ordonnances = $this->getUser()->getOrdonnances();

        return $this->render('patient/ordonnance/list.html.twig', [
            'ordonnances' => $ordonnances,
        ]);
    }

    #[Route('/download/{id}', name: 'patient_ordonnance_download')]
    public function download(Ordonnance $ordonnance): BinaryFileResponse
    {
        // Verify patient owns this ordonnance
        if ($this->getUser() !== $ordonnance->getConsultation()->getPatient()) {
            throw new AccessDeniedException();
        }

        $filePath = $this->getParameter('kernel.project_dir').'/public/uploads/ordonnances/'.$ordonnance->getFilePath();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'prescription-'.$ordonnance->getId().'.pdf'
        );

        return $response;
    }
}