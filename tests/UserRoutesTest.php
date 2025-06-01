<?php

namespace App\Tests;

use Tests\TestCase;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class UserRoutesTest extends ApiTestCase
{
    protected static $mail;

    public function testRegisterUser(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        self::$mail = $randomString . "@mail.com";
 
        $client = static::createClient();
        $response = $client->request('POST', '/register', [ 
            'json' => [
                "name" => $randomString,
                "email" => ($randomString . "@mail.com"),
                "password" => "123456" 
            ]
        ]);

        $response = $response->toArray();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testListUsers(): void
    {
        $client = static::createClient();
        $response = $client->request('GET', '/api/users/list');
        
        $response = $response->toArray();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAuthenticateUser(): void
    {
        $client = static::createClient();
        $response = $client->request('POST', '/login', [
            'json' => [
                "username" => self::$mail,
                "password" => "123456" 
            ]
        ]);
        
        $response = $response->toArray();
        $this->assertResponseStatusCodeSame(200);
    }
}
