<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class TaskCommentCreationValidationTest extends ApiTestCase
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
        
        $response = $client->request('POST', '/api/task/comment/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => []
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("MISSING_TEXT", $responseMessages));
    }

    public function testInvalidFields(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/comment/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'text' => 99
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("TEXT_NOT_STRING", $responseMessages));
    }

    public function testEmptyFields(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/comment/' . self::$taskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'text' => ""
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("EMPTY_TEXT", $responseMessages));
    }

    public function testTaskNotFound(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/comment/999999', [
            'auth_bearer' => self::$token,
            'json' => [
                'text' => "Some comment"
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(404);
        $this->assertTrue(in_array("TASK_NOT_FOUND", $responseMessages));
    }

    public function testCommentNotFound(): void
    {
        $client = static::createClient();
        
        $response = $client->request('DELETE', '/api/task/comment/999', [
            'auth_bearer' => self::$token
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(404);
        $this->assertTrue(in_array("COMMENT_NOT_FOUND", $responseMessages));
    }
}
