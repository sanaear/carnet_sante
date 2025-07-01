<?php

namespace App\Tests\Controller\Patient;

use App\Entity\Consultation;
use App\Entity\Doctor;
use App\Entity\Patient;
use App\Entity\Ordonnance;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class OrdonnanceControllerTest extends WebTestCase
{
    private $client;
    private $testDoctor;
    private $testPatient;
    private $testConsultation;
    private $testOrdonnance;
    private $entityManager;
    private $uploadDir;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->uploadDir = $this->client->getContainer()->getParameter('kernel.project_dir') . '/public/uploads/ordonnances/';
        
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
        $this->testPatient->setDateOfBirth(new \DateTime('1990-01-01'));
        $this->testPatient->setPhoneNumber('0123456789');
        
        // Create a test consultation
        $this->testConsultation = new Consultation();
        $this->testConsultation->setDoctor($this->testDoctor);
        $this->testConsultation->setPatient($this->testPatient);
        $this->testConsultation->setDate(new \DateTime('yesterday'));
        $this->testConsultation->setStatus(Consultation::STATUS_COMPLETED);
        
        // Create a test ordonnance
        $this->testOrdonnance = new Ordonnance();
        $this->testOrdonnance->setConsultation($this->testConsultation);
        $this->testOrdonnance->setCreatedAt(new \DateTime());
        
        // Create a test file
        $testPdfPath = $this->uploadDir . 'test_ordonnance.pdf';
        file_put_contents($testPdfPath, 'Test PDF content');
        
        // Set the file path in the entity (simulating what VichUploader would do)
        $reflection = new \ReflectionClass($this->testOrdonnance);
        $fileProperty = $reflection->getProperty('filePath');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($this->testOrdonnance, 'test_ordonnance.pdf');
        
        // Persist all entities
        $this->entityManager->persist($this->testDoctor);
        $this->entityManager->persist($this->testPatient);
        $this->entityManager->persist($this->testConsultation);
        $this->entityManager->persist($this->testOrdonnance);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up the test file
        $testPdfPath = $this->uploadDir . 'test_ordonnance.pdf';
        if (file_exists($testPdfPath)) {
            unlink($testPdfPath);
        }
        
        // Clean up the database after each test
        if ($this->testOrdonnance && $this->entityManager->contains($this->testOrdonnance)) {
            $this->entityManager->remove($this->testOrdonnance);
        }
        if ($this->testConsultation && $this->entityManager->contains($this->testConsultation)) {
            $this->entityManager->remove($this->testConsultation);
        }
        if ($this->testDoctor && $this->entityManager->contains($this->testDoctor)) {
            $this->entityManager->remove($this->testDoctor);
        }
        if ($this->testPatient && $this->entityManager->contains($this->testPatient)) {
            $this->entityManager->remove($this->testPatient);
        }
        $this->entityManager->flush();
        
        parent::tearDown();
    }

    public function testListOrdonnances(): void
    {
        // Login as the test patient
        $this->client->loginUser($this->testPatient);
        
        // Access the ordonnance list page
        $this->client->request('GET', '/patient/ordonnance/list');
        
        // Check if the page loads successfully
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mes Ordonnances');
        
        // Check if the test ordonnance is listed
        $this->assertSelectorTextContains('table', $this->testDoctor->getFullName());
    }
    
    public function testDownloadOrdonnance(): void
    {
        // Login as the test patient
        $this->client->loginUser($this->testPatient);
        
        // Access the download page for the test ordonnance
        $this->client->request('GET', '/patient/ordonnance/download/' . $this->testOrdonnance->getId());
        
        // Check if the file is downloaded successfully
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
        $this->assertResponseHeaderMatches(
            'Content-Disposition',
            '/^attachment; filename=ordonnance-\d+-\d{4}-\d{2}-\d{2}\.pdf$/'
        );
    }
    
    public function testDownloadOrdonnanceUnauthorized(): void
    {
        // Create a different patient
        $otherPatient = new Patient();
        $otherPatient->setEmail('otherpatient@example.com');
        $otherPatient->setPassword('$2y$13$S8Yv5q5Xv5v5v5v5v5v5v.5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v5v');
        $otherPatient->setFirstName('Other');
        $otherPatient->setLastName('Patient');
        $otherPatient->setDateOfBirth(new \DateTime('1990-01-01'));
        $otherPatient->setPhoneNumber('0987654321');
        
        $this->entityManager->persist($otherPatient);
        $this->entityManager->flush();
        
        // Login as the other patient
        $this->client->loginUser($otherPatient);
        
        // Try to access the ordonnance that doesn't belong to this patient
        $this->client->request('GET', '/patient/ordonnance/download/' . $this->testOrdonnance->getId());
        
        // Should be denied access (403) or redirect to login (302)
        $this->assertTrue(
            $this->client->getResponse()->isForbidden() || 
            $this->client->getResponse()->isRedirect()
        );
        
        // Clean up
        $this->entityManager->remove($otherPatient);
        $this->entityManager->flush();
    }
}
