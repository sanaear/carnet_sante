<?php

namespace App\Tests\Controller\Medecin;

use App\Entity\Consultation;
use App\Entity\Doctor;
use App\Entity\Patient;
use App\Entity\Ordonnance;
use App\Repository\ConsultationRepository;
use App\Repository\OrdonnanceRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OrdonnanceControllerTest extends WebTestCase
{
    private $client;
    private $testDoctor;
    private $testPatient;
    private $testConsultation;
    private $testOrdonnance;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        
        // Create test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Create a test doctor
        $this->testDoctor = new Doctor();
        $this->testDoctor->setEmail('testdoctor@example.com');
        $this->testDoctor->setPassword('$2y$13$S8Yv5q5Xv5v5v5v5v5v5v.5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v');
        $this->testDoctor->setFirstName('Test');
        $this->testDoctor->setLastName('Doctor');
        $this->testDoctor->setRoles(['ROLE_DOCTOR']);
        
        // Create a test patient
        $this->testPatient = new Patient();
        $this->testPatient->setEmail('testpatient@example.com');
        $this->testPatient->setPassword('$2y$13$S8Yv5q5Xv5v5v5v5v5v5v.5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v');
        $this->testPatient->setFirstName('Test');
        $this->testPatient->setLastName('Patient');
        
        // Create a test consultation
        $this->testConsultation = new Consultation();
        $this->testConsultation->setDoctor($this->testDoctor);
        $this->testConsultation->setPatient($this->testPatient);
        $this->testConsultation->setDate(new \DateTimeImmutable('tomorrow'));
        $this->testConsultation->setStatus(Consultation::STATUS_COMPLETED);
        $this->testConsultation->setDescription('Test consultation');
        $this->testConsultation->setStartTime(new \DateTimeImmutable('tomorrow 10:00'));
        $this->testConsultation->setEndTime(new \DateTimeImmutable('tomorrow 10:30'));
        
        // Create a test ordonnance
        $this->testOrdonnance = new Ordonnance();
        $this->testOrdonnance->setConsultation($this->testConsultation);
        $this->testConsultation->setOrdonnance($this->testOrdonnance);
        
        // Persist all entities
        $this->entityManager->persist($this->testDoctor);
        $this->entityManager->persist($this->testPatient);
        $this->entityManager->persist($this->testConsultation);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up the database after each test
        if ($this->testOrdonnance) {
            $this->entityManager->remove($this->testOrdonnance);
        }
        $this->entityManager->remove($this->testConsultation);
        $this->entityManager->remove($this->testDoctor);
        $this->entityManager->remove($this->testPatient);
        $this->entityManager->flush();
        
        parent::tearDown();
    }

    public function testUploadOrdonnance(): void
    {
        // First, remove any existing ordonnance from the test consultation
        if ($this->testConsultation->getOrdonnance() !== null) {
            $existingOrdonnance = $this->testConsultation->getOrdonnance();
            $this->testConsultation->setOrdonnance(null);
            $this->entityManager->remove($existingOrdonnance);
            $this->entityManager->flush();
        }

        // Login as the test doctor
        $this->client->loginUser($this->testDoctor);
        
        // Create a test PDF file
        $testPdf = tempnam(sys_get_temp_dir(), 'test_ordonnance');
        file_put_contents($testPdf, 'Test PDF content');
        $uploadedFile = new UploadedFile(
            $testPdf,
            'test_ordonnance.pdf',
            'application/pdf',
            null,
            true // Mark as test file
        );
        
        // Submit the form
        $crawler = $this->client->request('GET', '/medecin/ordonnance/upload/' . $this->testConsultation->getId());
        $this->assertResponseIsSuccessful();
        
        $form = $crawler->selectButton('Enregistrer')->form([
            'ordonnance[file][file]' => $uploadedFile,
        ]);
        
        $this->client->submit($form);
        
        // Check if the response is a redirect to the consultation show page
        $this->assertResponseRedirects('/medecin/consultation/' . $this->testConsultation->getId());
        
        // Follow the redirect
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success', 'L\'ordonnance a été enregistrée avec succès');
        
        // Verify the ordonnance was created
        $ordonnance = $this->entityManager->getRepository(Ordonnance::class)
            ->findOneBy(['consultation' => $this->testConsultation]);
            
        $this->assertNotNull($ordonnance);
        $this->assertNotNull($ordonnance->getFileName());
        $this->assertNotNull($ordonnance->getOriginalName());
        $this->assertNotNull($ordonnance->getMimeType());
        $this->assertNotNull($ordonnance->getSize());
        $this->assertNotNull($ordonnance->getCreatedAt());
        $this->assertNotNull($ordonnance->getUpdatedAt());
        $this->assertEquals($this->testConsultation->getId(), $ordonnance->getConsultation()->getId());
        
        $this->testOrdonnance = $ordonnance;
    }
    
    public function testUploadOrdonnanceUnauthorized(): void
    {
        // Login as a different doctor
        $otherDoctor = new Doctor();
        $otherDoctor->setEmail('otherdoctor@example.com');
        $otherDoctor->setPassword('$2y$13$S8Yv5q5Xv5v5v5v5v5v5v.5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v');
        $otherDoctor->setFirstName('Other');
        $otherDoctor->setLastName('Doctor');
        $otherDoctor->setRoles(['ROLE_DOCTOR']);
        
        $this->entityManager->persist($otherDoctor);
        $this->entityManager->flush();
        
        $this->client->loginUser($otherDoctor);
        
        // Try to access the upload page for a consultation that doesn't belong to this doctor
        $this->client->request('GET', '/medecin/ordonnance/upload/' . $this->testConsultation->getId());
        
        // Should be denied access
        $this->assertResponseStatusCodeSame(403);
    }
    
    public function testUploadOrdonnanceAlreadyExists(): void
    {
        // Login as the test doctor
        $this->client->loginUser($this->testDoctor);
        
        // The test consultation already has an ordonnance from setup
        $existingOrdonnance = $this->testConsultation->getOrdonnance();
        $this->assertNotNull($existingOrdonnance);
        
        // Try to access the upload page
        $this->client->request('GET', '/medecin/ordonnance/upload/' . $this->testConsultation->getId());
        
        // Should be redirected to the consultation page with a warning
        $this->assertResponseRedirects('/medecin/consultation/' . $this->testConsultation->getId());
        
        // Follow the redirect
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-warning', 'Une ordonnance existe déjà pour cette consultation');
        
        // Verify the existing ordonnance is still there
        $this->entityManager->refresh($this->testConsultation);
        $this->assertNotNull($this->testConsultation->getOrdonnance());
        $this->assertEquals($existingOrdonnance->getId(), $this->testConsultation->getOrdonnance()->getId());
    }
}
