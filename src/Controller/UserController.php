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

        $validationResult = $validator->validate((array) $request,
            new Assert\Collection([  
                'name' => [
                    new Assert\Required(),
                    new Assert\NotBlank(null, "EMPTY_NAME"),
                    new Assert\Type("string", "NAME_NOT_STRING"),
                ],
                'email' => [
                    new Assert\Required(),
                    new Assert\NotBlank(null, "EMPTY_EMAIL"),
                    new Assert\Email(null, "INVALID_EMAIL"),
                    new Assert\Type("string", "EMAIL_NOT_STRING"),
                ],
                'password' => [
                    new Assert\Required(),
                    new Assert\NotBlank(null, "EMPTY_PASSWORD"),
                    new Assert\Type("string", "PASSWORD_NOT_STRING"),
                ],
            ])
        );

        if (count($validationResult) > 0) {
            $messages = [];
            foreach ($validationResult as $error) {
                $messages[] = (($error->getMessage() == "This field is missing.") ? "MISSING_" . strtoupper(str_replace([ "[", "]" ], "", $error->getPropertyPath())) : $error->getMessage());
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
                'message' => [ "EMAIL_ALREADY_TAKEN" ]
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
