<?php

namespace App\Service;

use App\Entity\Ordonnance;
use Knp\Snappy\Pdf;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class OrdonnancePdfGenerator
{
    /** @var Pdf */
    private $pdf;

    /** @var Environment */
    private $twig;

    /** @var KernelInterface */
    private $kernel;

    /** @var string */
    private $projectDir;

    public function __construct(Pdf $pdf, Environment $twig, KernelInterface $kernel)
    {
        $this->pdf = $pdf;
        $this->twig = $twig;
        $this->kernel = $kernel;
        $this->projectDir = $kernel->getProjectDir();
    }

    public function generatePdf(Ordonnance $ordonnance): Response
    {
        if (!$ordonnance->isGenerated()) {
            throw new \RuntimeException('Cannot generate PDF for non-generated ordonnance');
        }

        $consultation = $ordonnance->getConsultation();
        $patient = $consultation->getPatient();
        $doctor = $consultation->getDoctor();

        $html = $this->twig->render('pdf/ordonnance.html.twig', [
            'ordonnance' => $ordonnance,
            'consultation' => $consultation,
            'patient' => $patient,
            'doctor' => $doctor,
        ]);

        // Safely get patient identifier
        $patientIdentifier = 'patient';

        // Try to get the patient's full name
        if (method_exists($patient, 'getFullName')) {
            $patientIdentifier = $patient->getFullName();
        } elseif (method_exists($patient, 'getFirstName') && method_exists($patient, 'getLastName')) {
            $firstName = $patient->getFirstName() ?? '';
            $lastName = $patient->getLastName() ?? '';
            $patientIdentifier = trim("$firstName $lastName") ?: 'patient';
        }

        $filename = sprintf(
            'ordonnance_%s_%s.pdf',
            $patientIdentifier,
            $ordonnance->getCreatedAt()->format('Y-m-d')
        );

        // Ensure filename is URL-safe
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);

        $options = [
            'encoding' => 'utf-8',
            'enable-javascript' => true,
            'javascript-delay' => 1000,
            'no-stop-slow-scripts' => true,
            'no-background' => false,
            'lowquality' => false,
            'page-size' => 'A4',
            'margin-top' => 20,
            'margin-right' => 15,
            'margin-bottom' => 20,
            'margin-left' => 15,
            'dpi' => 300,
            'image-dpi' => 300,
            'image-quality' => 94,
        ];

        // Add title if available
        $title = 'Ordonnance médicale';
        if (method_exists($ordonnance, 'getTitle')) {
            $title = $ordonnance->getTitle() ?: $title;
        }
        $options['title'] = $title;

        $pdfContent = $this->pdf->getOutputFromHtml($html, $options);

        $response = new Response($pdfContent);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }
}
