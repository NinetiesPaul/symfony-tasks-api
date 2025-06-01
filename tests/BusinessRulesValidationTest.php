<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class BusinessRulesValidationTest extends ApiTestCase
{
    protected static $user;

    protected static $token;

    protected static $firstTaskId;

    protected static $secondTaskId;

    public function setUp(): void
    {
        $client = static::createClient();
        $usersListResponse = $client->request('GET', '/api/users/list');
        $usersList = $usersListResponse->toArray()['data']['users'];

        self::$user = $usersList[0];

        $response = $client->request('POST', '/login', [ 
            'json' => [
                "username" => self::$user['email'],
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
        
        self::$firstTaskId = (string) $taskCreationResponse->toArray()['data']['id'];

        $secondDaskCreationResponse = $client->request('POST', '/api/task/create', [
            'auth_bearer' => self::$token,
            'json' => [
                "title" => "TaskCommentCreationValidationTest",
                "description" => "This task is created for the TaskCommentCreationValidationTest test",
                "type" => "hotfix"
            ]
        ]);

        $client->request('POST', '/api/task/assign/' . self::$firstTaskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'assigned_to' => self::$user['id']
            ]
        ]);
        
        self::$secondTaskId = (string) $secondDaskCreationResponse->toArray()['data']['id'];

        $client->request('PUT', '/api/task/close/' . self::$secondTaskId, [
            'auth_bearer' => self::$token
        ]);
    }

    public function testReassignSameUserFails(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/task/assign/' . self::$firstTaskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'assigned_to' => self::$user['id']
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(202);
        $this->assertTrue(in_array("USER_ALREADY_ASSIGNED", $responseMessages));
    }

    public function testUpdateToCloseFails(): void
    {
        $client = static::createClient();

        $response = $client->request('PUT', '/api/task/update/' . self::$firstTaskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'status' => 'closed'
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("CAN_NOT_UPDATE_TO_CLOSE", $responseMessages));
    }

    public function testUpdateClosedTaskFails(): void
    {
        $client = static::createClient();

        $response = $client->request('PUT', '/api/task/update/' . self::$secondTaskId, [
            'auth_bearer' => self::$token,
            'json' => [
                'status' => 'hotfix'
            ]
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("TASK_CLOSED", $responseMessages));
    }

    public function testUCloseClosedTaskFails(): void
    {
        $client = static::createClient();

        $response = $client->request('PUT', '/api/task/close/' . self::$secondTaskId, [
            'auth_bearer' => self::$token
        ]);

        $responseMessages = $response->toArray(false)['message'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertTrue(in_array("TASK_ALREADY_CLOSED", $responseMessages));
    }
}
