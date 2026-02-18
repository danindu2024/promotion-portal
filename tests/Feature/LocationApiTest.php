<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocationApiTest extends TestCase
{
    public function test_provinces_returns_all_nine_provinces(): void
    {
        $response = $this->getJson('/api/locations/provinces');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(9, $data);
        $this->assertContains('Central', $data);
        $this->assertContains('Western', $data);
        $this->assertContains('Southern', $data);
    }

    public function test_districts_for_western_province(): void
    {
        $response = $this->getJson('/api/locations/districts?province=Western');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(3, $data);
        $this->assertContains('Colombo', $data);
        $this->assertContains('Gampaha', $data);
        $this->assertContains('Kalutara', $data);
    }

    public function test_districts_requires_province_parameter(): void
    {
        $response = $this->getJson('/api/locations/districts');

        $response->assertStatus(422);
    }

    public function test_districts_for_invalid_province(): void
    {
        $response = $this->getJson('/api/locations/districts?province=InvalidProvince');

        $response->assertStatus(422);
    }

    public function test_ds_divisions_for_colombo_district(): void
    {
        $response = $this->getJson('/api/locations/ds-divisions?district=Colombo');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertContains('Thimbirigasyaya', $data);
        $this->assertContains('Kaduwela', $data);
        $this->assertContains('Moratuwa', $data);
    }

    public function test_ds_divisions_requires_district_parameter(): void
    {
        $response = $this->getJson('/api/locations/ds-divisions');

        $response->assertStatus(422);
    }

    public function test_ds_divisions_for_invalid_district(): void
    {
        $response = $this->getJson('/api/locations/ds-divisions?district=InvalidDistrict');

        $response->assertStatus(422);
    }

    public function test_districts_for_central_province(): void
    {
        $response = $this->getJson('/api/locations/districts?province=Central');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(3, $data);
        $this->assertContains('Kandy', $data);
        $this->assertContains('Matale', $data);
        $this->assertContains('Nuwara Eliya', $data);
    }
}
