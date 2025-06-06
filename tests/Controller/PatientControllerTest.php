<?php

namespace App\Tests\Controller;

use App\Entity\Patient;
use App\Repository\PatientRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class PatientControllerTest extends WebTestCase
{
    private $client;
    private $patientRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->patientRepository = static::getContainer()->get(PatientRepository::class);
    }

    public function testAccessDashboardWithoutLogin(): void
    {
        $this->client->request('GET', '/patient/dashboard');
        
        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    public function testAccessHistoryWithoutLogin(): void
    {
        $this->client->request('GET', '/patient/historique');
        
        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    public function testAccessDashboardWithLogin(): void
    {
        // Find a test patient
        $testPatient = $this->patientRepository->findOneBy([]);

        if (!$testPatient) {
            $this->markTestSkipped('No test patient available in database');
        }

        // Log in as this patient
        $this->client->loginUser($testPatient);

        // Try accessing the dashboard
        $this->client->request('GET', '/patient/dashboard');

        // Should be successful
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Bienvenue');
    }

    public function testAccessHistoryWithLogin(): void
    {
        // Find a test patient
        $testPatient = $this->patientRepository->findOneBy([]);

        if (!$testPatient) {
            $this->markTestSkipped('No test patient available in database');
        }

        // Log in as this patient
        $this->client->loginUser($testPatient);

        // Try accessing the history page
        $this->client->request('GET', '/patient/historique');

        // Should be successful
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Historique médical');
    }

    public function testDashboardShowsPatientInfo(): void
    {
        // Find a test patient
        $testPatient = $this->patientRepository->findOneBy([]);

        if (!$testPatient) {
            $this->markTestSkipped('No test patient available in database');
        }

        // Log in as this patient
        $this->client->loginUser($testPatient);

        // Access the dashboard
        $crawler = $this->client->request('GET', '/patient/dashboard');

        // Verify patient information is displayed
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.text-gray-900');
        $this->assertSelectorTextContains('.text-gray-500', 'Email');
    }
} 