<?php
namespace App\Controller\Medecin;

use App\Entity\Ordonnance;
use App\Entity\Consultation;
use App\Form\OrdonnanceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/medecin/ordonnance')]
class OrdonnanceController extends AbstractController
{
    #[Route('/upload/{id}', name: 'medecin_ordonnance_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, Consultation $consultation, EntityManagerInterface $em)
    {
        // Verify doctor owns this consultation
        if ($this->getUser() !== $consultation->getMedecin()) {
            throw new AccessDeniedException();
        }

        // Check if ordonnance exists
        if ($consultation->getOrdonnance()) {
            $this->addFlash('warning', 'Prescription already exists');
            return $this->redirectToRoute('app_medecin_consultation_show', ['id' => $consultation->getId()]);
        }

        $ordonnance = new Ordonnance();
        $ordonnance->setConsultation($consultation);
        $form = $this->createForm(OrdonnanceType::class, $ordonnance);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ordonnance);
            $em->flush();

            $this->addFlash('success', 'Prescription uploaded successfully');
            return $this->redirectToRoute('app_medecin_consultation_show', ['id' => $consultation->getId()]);
        }

        return $this->render('medecin/ordonnance/upload.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation,
        ]);
    }
}