<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(ManagerRegistry $doctrine, Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $request = json_decode($request->getContent());

        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(),
            ],
            'email' => [
                new Assert\NotBlank(),
                new Assert\Email(null, "E-mail is not valid"),
            ],
            'password' => [
                new Assert\NotBlank(),
            ],
        ]);
    
        $validationResult = $validator->validate( (array) $request, $constraints);
        if (count($validationResult) > 0) {
            $messages = [];

            foreach ($validationResult as $error) {
                $messages[] = $error->getPropertyPath() . " " . $error->getMessage();
            }
            
            return $this->json([
                'success' => false,
                'message' => $messages
            ], Response::HTTP_BAD_REQUEST);
        }

        $userRep = new UserRepository($doctrine);
        $user = $userRep->findBy([ 'email' => $request->email ], [ 'id' => 'DESC' ]);
        if ($user) {
            return $this->json([
                'success' => false,
                'message' => "E-mail already taken"
            ], Response::HTTP_NOT_FOUND);
        }

        $user = new User();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $request->password
        );

        $user->setName($request->name);
        $user->setEmail($request->email);
        $user->setPassword($hashedPassword);

        $userRep = new UserRepository($doctrine);
        $userRep->save($user, true);

        return $this->json([
            'success' => true,
            'data' => $user
        ]);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(): void {}
}
