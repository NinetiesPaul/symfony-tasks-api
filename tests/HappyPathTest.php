<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class HappyPathTest extends ApiTestCase
{
    protected static $firstUser;

    protected static $firstUserToken;

    protected static $secondUser;

    protected static $secondUserToken;

    protected static $taskId;

    protected static $assignmentId;

    protected static $commentId;

    public function setUp(): void
    {
        $client = static::createClient();
        $usersListResponse = $client->request('GET', '/api/users/list');
        $usersList = $usersListResponse->toArray()['data']['users'];

        self::$firstUser = $usersList[0];
        self::$secondUser = $usersList[0];

        if (count($usersList) > 1) {
            self::$secondUser = $usersList[1];
        }

        $firstUserLoginResponse = $client->request('POST', '/login', [ 
            'json' => [
                "username" => self::$firstUser['email'],
                "password" => "123456" 
            ]
        ]);
        
        self::$firstUserToken = $firstUserLoginResponse->toArray()['token'];

        $secondUserLoginResponse = $client->request('POST', '/login', [ 
            'json' => [
                "username" => self::$secondUser['email'],
                "password" => "123456" 
            ]
        ]);
        
        self::$secondUserToken = $secondUserLoginResponse->toArray()['token'];
    }

    public function testCreateTask(): void
    {
        $this->setUp();

        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/create', [
            'auth_bearer' => self::$firstUserToken,
            'json' => [
                "title" => "Task title",
                "description" => "Task description",
                "type" => "feature"
            ]
        ]);

        $response = $response->toArray()['data'];

        self::$taskId = (string) $response['id'];

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(null, $response['closed_by']);
        $this->assertEquals(null, $response['closed_on']);
        $this->assertEquals("open", $response['status']);
    }

    public function testUpdateTask(): void
    {
        $client = static::createClient();
        
        $response = $client->request('PUT', '/api/task/update/' . self::$taskId, [
            'auth_bearer' => self::$firstUserToken,
            'json' => [
                "type" => "hotfix",
                "status" => "in_qa",
                "title" => "New title",
                "description" => "New description"
            ]
        ]);

        $response = $response->toArray()['data'];

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals("hotfix", $response['type']);
        $this->assertEquals("in_qa", $response['status']);
        $this->assertEquals("New title", $response['title']);
        $this->assertEquals("New description", $response['description']);
    }

    public function testTaskHistoryIsUpdated(): void
    {
        $client = static::createClient();
        
        $response = $client->request('GET', '/api/task/view/' . self::$taskId, [
            'auth_bearer' => self::$firstUserToken
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $response->toArray()['data'];

        $taskHistory = $response['history'];

        $this->assertEquals(4, count($taskHistory));
        foreach($taskHistory as $entry) {
            if ($entry['field'] == "type") {
                $this->assertEquals("feature", $entry['changed_from']);
                $this->assertEquals("hotfix", $entry['changed_to']);
            }

            if ($entry['field'] == "status") {
                $this->assertEquals("open", $entry['changed_from']);
                $this->assertEquals("in_qa", $entry['changed_to']);
            }

            if ($entry['field'] == "title") {
                $this->assertEquals("Task title", $entry['changed_from']);
                $this->assertEquals("New title", $entry['changed_to']);
            }

            if ($entry['field'] == "description") {
                $this->assertEquals("Task description", $entry['changed_from']);
                $this->assertEquals("New description", $entry['changed_to']);
            }
        }
    }

    public function testAssignTask(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/assign/' . self::$taskId, [
            'auth_bearer' => self::$secondUserToken,
            'json' => [
                'assigned_to' => self::$firstUser['id']
            ]
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $response->toArray()['data'];

        self::$assignmentId = (string) $response['id'];

        $this->assertEquals(self::$firstUser['name'], $response['assigned_to']['name']);
        $this->assertEquals(self::$secondUser['name'], $response['assigned_by']['name']);
    }

    public function testTaskHistoryHasAssignmentEntry(): void
    {
        $client = static::createClient();
        
        $response = $client->request('GET', '/api/task/view/' . self::$taskId, [
            'auth_bearer' => self::$firstUserToken
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $response->toArray()['data'];

        $taskHistory = $response['history'];
        foreach($taskHistory as $entry) {
            if ($entry['field'] == "added_assignee") {
                $this->assertEquals(self::$secondUser['name'], $entry['changed_by']['name']);
                $this->assertEquals(self::$firstUser['name'], $entry['changed_to']);
            }
        }
    }

    public function testTaskUnassignTask(): void
    {
        $client = static::createClient();
        
        $response = $client->request('DELETE', '/api/task/unassign/' . self::$assignmentId, [
            'auth_bearer' => self::$secondUserToken
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testTaskHistoryHasUnassignmentEntry(): void
    {
        $client = static::createClient();
        
        $response = $client->request('GET', '/api/task/view/' . self::$taskId, [
            'auth_bearer' => self::$firstUserToken
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $response->toArray()['data'];

        $taskHistory = $response['history'];
        foreach($taskHistory as $entry) {
            if ($entry['field'] == "removed_assignee") {
                $this->assertEquals(self::$secondUser['name'], $entry['changed_by']['name']);
                $this->assertEquals(self::$firstUser['name'], $entry['changed_to']);
            }
        }
    }

    public function testCreateTaskComment(): void
    {
        $client = static::createClient();
        
        $response = $client->request('POST', '/api/task/comment/' . self::$taskId, [
            'auth_bearer' => self::$secondUserToken,
            'json' => [
                'text' => 'This is the first task Comment'
            ]
        ]);
        
        $client->request('POST', '/api/task/comment/' . self::$taskId, [
            'auth_bearer' => self::$secondUserToken,
            'json' => [
                'text' => 'This is the second task Comment'
            ]
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $response->toArray()['data'];

        self::$commentId = (string) $response['id'];

        $this->assertEquals(self::$secondUser['name'], $response['created_by']['name']);
    }

    public function testRemoveTaskComment(): void
    {
        $client = static::createClient();
        
        $client->request('DELETE', '/api/task/comment/' . self::$commentId, [
            'auth_bearer' => self::$secondUserToken
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testTaskDetailsListsComments(): void
    {
        $client = static::createClient();
        
        $response = $client->request('GET', '/api/task/view/' . self::$taskId, [
            'auth_bearer' => self::$firstUserToken
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $response->toArray()['data'];

        $taskComments = $response['comments'];

        $this->assertEquals(1, count($taskComments));
        $this->assertEquals("This is the second task Comment", $taskComments[0]['comment_text']);
    }

    public function testCloseTask(): void
    {
        $client = static::createClient();
        
        $response = $client->request('PUT', '/api/task/close/' . self::$taskId, [
            'auth_bearer' => self::$firstUserToken
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $response->toArray()['data'];

        $this->assertEquals(self::$firstUser['name'], $response['closed_by']['name']);
    }
}
