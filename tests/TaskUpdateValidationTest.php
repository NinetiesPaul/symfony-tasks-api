<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class TaskUpdateValidationTest extends ApiTestCase
{
    protected static $token;

    protected static $taskId;

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

        $taskCreationResponse = $client->request('POST', '/api/task/create', [
            'auth_bearer' => self::$token,
            'json' => [
                "title" => "TaskCommentCreationValidationTest",
                "description" => "This task is created for the TaskCommentCreationValidationTest test",
                "type" => "hotfix"
            ]
        ]);
        
        self::$taskId = (string) $taskCreationResponse->toArray()['data']['id'];
    }

    public function testInvalidFields(): void
    {
        $this->setUp();

        $client = static::createClient();
        
        $response = $client->request('PUT', '/api/task/update/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => [
                "title" => 99,
                "description" => 99,
                "type" => 99,
                "status" => 99
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("TITLE_NOT_STRING", $responseMessages));
        $this->assertTrue(in_array("DESCRIPTION_NOT_STRING", $responseMessages));
        $this->assertTrue(in_array("TYPE_NOT_STRING", $responseMessages));
        $this->assertTrue(in_array("STATUS_NOT_STRING", $responseMessages));
    }

    public function testEmptyFields(): void
    {
        $client = static::createClient();
        
        $response = $client->request('PUT', '/api/task/update/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => [
                "title" => "",
                "description" => "",
                "type" => "",
                "status" => ""
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("EMPTY_TITLE", $responseMessages));
        $this->assertTrue(in_array("EMPTY_DESCRIPTION", $responseMessages));
        $this->assertTrue(in_array("EMPTY_TYPE", $responseMessages));
        $this->assertTrue(in_array("EMPTY_STATUS", $responseMessages));
    }

    public function testTaskNotFound(): void
    {
        $client = static::createClient();
        
        $response = $client->request('PUT', '/api/task/update/999999', [
            'auth_bearer' => self::$token,
            'json' => [
                "title" => "Some title",
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(404);
        $this->assertTrue(in_array("TASK_NOT_FOUND", $responseMessages));
    }

    public function testTaskNotFoundOnClose(): void
    {
        $client = static::createClient();
        
        $response = $client->request('PUT', '/api/task/close/999999', [
            'auth_bearer' => self::$token
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(404);
        $this->assertTrue(in_array("TASK_NOT_FOUND", $responseMessages));
    }
}
