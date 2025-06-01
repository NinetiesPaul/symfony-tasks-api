<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class TaskAssignmentValidationTest extends ApiTestCase
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

    public function testMissingFields(): void
    {
        $this->setUp();

        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/assign/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => []
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("MISSING_ASSIGNED_TO", $responseMessages));
    }

    public function testInvalidFields(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/assign/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'assigned_to' => "99"
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("ASSIGNED_TO_NOT_INTEGER", $responseMessages));
    }

    public function testTaskNotFound(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/assign/999999', [
            'auth_bearer' => self::$token,
            'json' => [
                'assigned_to' => 1
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(404);
        $this->assertTrue(in_array("TASK_NOT_FOUND", $responseMessages));
    }

    public function testUserkNotFound(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/assign/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'assigned_to' => 99
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(404);
        $this->assertTrue(in_array("USER_NOT_FOUND", $responseMessages));
    }

    public function testAssignmentNotFound(): void
    {
        $client = static::createClient();
        
        $response = $client->request('DELETE', '/api/task/unassign/999', [
            'auth_bearer' => self::$token
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(404);
        $this->assertTrue(in_array("ASSIGNMENT_NOT_FOUND", $responseMessages));
    }
}
