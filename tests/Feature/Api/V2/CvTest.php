<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

class CvTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /** @test */
    public function can_get_cv_index(): void
    {
        $response = $this->getJson($this->apiUrl('cv'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_experience(): void
    {
        $response = $this->getJson($this->apiUrl('cv/experience'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_education(): void
    {
        $response = $this->getJson($this->apiUrl('cv/education'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_skills(): void
    {
        $response = $this->getJson($this->apiUrl('cv/skills'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_projects(): void
    {
        $response = $this->getJson($this->apiUrl('cv/projects'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_repositories(): void
    {
        $response = $this->getJson($this->apiUrl('cv/repositories'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_services(): void
    {
        $response = $this->getJson($this->apiUrl('cv/services'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_collaborations(): void
    {
        $response = $this->getJson($this->apiUrl('cv/collaborations'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_hobbies(): void
    {
        $response = $this->getJson($this->apiUrl('cv/hobbies'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function can_get_cv_jobs(): void
    {
        $response = $this->getJson($this->apiUrl('cv/jobs'));
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }
}
