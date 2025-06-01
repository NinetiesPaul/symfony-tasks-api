<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class TaskCreationValidationTest extends ApiTestCase
{
    protected static $token;

    public function setUp(): void
    {
        $client = static::createClient();
        $usersListResponse = $client->request('GET', '/api/users/list');
        $usersList = $usersListResponse->toArray()['data']['users'];

        $user = $usersList[0];

        $response = $client->request('POST', '/login', [ 
            'json' => [
                "username" => $user['email'],
                "password" => "123456" 
            ]
        ]);
        
        self::$token = $response->toArray()['token'];
    }

    public function testMissingFields(): void
    {
        $this->setUp();

        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/create', [
            'auth_bearer' => self::$token,
            'json' => []
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("MISSING_TITLE", $responseMessages));
        $this->assertTrue(in_array("MISSING_DESCRIPTION", $responseMessages));
        $this->assertTrue(in_array("MISSING_TYPE", $responseMessages));
    }

    public function testInvalidFields(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/create', [
            'auth_bearer' => self::$token,
            'json' => [
                "title" => 99,
                "description" => 99,
                "type" => 99
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("TITLE_NOT_STRING", $responseMessages));
        $this->assertTrue(in_array("DESCRIPTION_NOT_STRING", $responseMessages));
        $this->assertTrue(in_array("TYPE_NOT_STRING", $responseMessages));
    }

    public function testEmptyFields(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/create', [
            'auth_bearer' => self::$token,
            'json' => [
                "title" => "",
                "description" => "",
                "type" => ""
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("EMPTY_TITLE", $responseMessages));
        $this->assertTrue(in_array("EMPTY_DESCRIPTION", $responseMessages));
        $this->assertTrue(in_array("EMPTY_TYPE", $responseMessages));
    }
}
