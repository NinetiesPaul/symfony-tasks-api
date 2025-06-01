<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class UserRoutesValidationTest extends ApiTestCase
{
    public function testMissingFields(): void
    {
        $client = static::createClient();
        $response = $client->request('POST', '/register', [ 
            'json' => []
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("MISSING_NAME", $responseMessages));
        $this->assertTrue(in_array("MISSING_EMAIL", $responseMessages));
        $this->assertTrue(in_array("MISSING_PASSWORD", $responseMessages));
    }

    public function testInvalidFields(): void
    {
        $client = static::createClient();
        $response = $client->request('POST', '/register', [ 
            'json' => [
                "name" => 99,
                "email" => 99,
                "password" => 99 
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("NAME_NOT_STRING", $responseMessages));
        $this->assertTrue(in_array("EMAIL_NOT_STRING", $responseMessages));
        $this->assertTrue(in_array("PASSWORD_NOT_STRING", $responseMessages));
    }

    public function testEmptyParameters(): void
    {
        $client = static::createClient();
        $response = $client->request('POST', '/register', [ 
            'json' => [
                "name" => "",
                "email" => "",
                "password" => "" 
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("EMPTY_NAME", $responseMessages));
        $this->assertTrue(in_array("EMPTY_EMAIL", $responseMessages));
        $this->assertTrue(in_array("EMPTY_PASSWORD", $responseMessages));
    }
}
