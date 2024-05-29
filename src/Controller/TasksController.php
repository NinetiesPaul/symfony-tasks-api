<?php

namespace App\Controller;

use App\Entity\TaskHistory;
use DateTime;
use App\Entity\User;
use App\Entity\Tasks;
use App\Repository\TaskHistoryRepository;
use App\Repository\TasksRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TasksController extends AbstractController
{
    #[Route('/api/task/list', methods: ['GET'])]
    public function list(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $taskRep = new TasksRepository($doctrine);

        $parameters = [];

        $typeQueryParameter = $request->query->get('type');
        $statusQueryParameter = $request->query->get('status');
        $createdByQueryParameter = $request->query->get('created_by');

        if ($typeQueryParameter !== null) {
            if (!Tasks::allowedTypes($typeQueryParameter)) {
                return $this->json([
                    'success' => false,
                    'message' => "Invalid task type: must be one of 'feature' 'bugfix' 'hotfix'"
                ], Response::HTTP_BAD_REQUEST);
            }

            $parameters['type'] = $request->query->get('type');
        }
        
        if ($statusQueryParameter !== null) {
            if (!Tasks::allowedStatuses($statusQueryParameter)) {
                return $this->json([
                    'success' => false,
                    'message' => "Invalid task status: must be one of 'open' 'closed' 'in_dev' 'blocked' 'in_qa'"
                ], Response::HTTP_BAD_REQUEST);
            }

            $parameters['status'] = $request->query->get('status');
        }
        
        if ($createdByQueryParameter !== null) {
            $userRep = new UserRepository($doctrine);
            $user = $userRep->find($createdByQueryParameter);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => "No user found with given id"
                ], Response::HTTP_NOT_FOUND);
            }

            $parameters['createdBy'] = $request->query->get('created_by');
        }
        
        $tasks = $taskRep->findBy($parameters, [ 'id' => 'DESC' ]);

        return $this->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks
            ]
        ]);
    }
    #[Route('/api/task/view/{taskId}', methods: ['GET'])]
    public function view(ManagerRegistry $doctrine, int $taskId): JsonResponse
    {
        $taskRep = new TasksRepository($doctrine);
        $task = $taskRep->find($taskId);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => "No task found with given id"
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $task
        ]);
    }

    #[Route('/api/task/create', methods: ['POST'])]
    public function create(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, ValidatorInterface $validator, Request $request): JsonResponse
    {
        $request = json_decode($request->getContent());

        $constraints = new Assert\Collection([
            'title' => [
                new Assert\NotBlank(),
            ],
            'description' => [
                new Assert\NotBlank(),
            ],
            'type' => [
                new Assert\NotBlank(),
            ],
        ]);
    
        $validationResult = $validator->validate((array) $request, $constraints);

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

        if (!Tasks::allowedTypes($request->type)) {
            return $this->json([
                'success' => false,
                'message' => "Invalid task type: must be one of 'feature' 'bugfix' 'hotfix'"
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $task = new Tasks();
        $task->setTitle($request->title);
        $task->setDescription($request->description);
        $task->setStatus("open");
        $task->setType($request->type);
        $task->setCreatedOn(new DateTime());
        $task->setCreatedBy($user);

        $taskRep = new TasksRepository($doctrine);
        $taskRep->save($task, true);

        return $this->json([
            'success' => true,
            'message' => "Task created",
            'data' => $task
        ]);
    }

    #[Route('/api/task/update/{taskId}', methods: ['PUT'])]
    public function update(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, Request $request, int $taskId): JsonResponse
    {
        $request = json_decode($request->getContent());

        $taskRep = new TasksRepository($doctrine);
        $task = $taskRep->find($taskId);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => "No task found with given id"
            ], Response::HTTP_NOT_FOUND);
        }

        if ($task->getStatus() == "closed") {
            return $this->json([
                'success' => false,
                'message' => "Invalid operation: cannot update a closed task"
            ], Response::HTTP_BAD_REQUEST);
        }

        $storeHistory = false;
   
        if (!empty($request->title) && $task->getTitle() != $request->title){
            $storeHistory['title'] = [ $task->getTitle(), $request->title ];
            $task->setTitle($request->title);
        }
   
        if (!empty($request->description) && $task->getDescription() != $request->description){
            $storeHistory['description'] = [ $task->getDescription(), $request->description ];
            $task->setDescription($request->description);
        }
        
        if (!empty($request->type) && $task->getType() != $request->type) {
            if (!Tasks::allowedTypes($request->type)) {
                return $this->json([
                    'success' => false,
                    'message' => "Invalid task type: must be one of 'feature' 'bugfix' 'hotfix'"
                ], Response::HTTP_BAD_REQUEST);
            }

            $storeHistory['type'] = [ $task->getType(), $request->type ];
            $task->setType($request->type);
        }
        
        if (!empty($request->status) && $task->getStatus() != $request->status) {
            if (!Tasks::allowedStatuses($request->status)) {
                return $this->json([
                    'success' => false,
                    'message' => "Invalid task status: must be one of 'open' 'closed' 'in_dev' 'blocked' 'in_qa'"
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($request->status === 'closed') {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid operation: use PUT /api/task/close/{id} to close a task'
                ], Response::HTTP_BAD_REQUEST);
            }

            $storeHistory['status'] = [ $task->getStatus(), $request->status ];
            $task->setStatus($request->status);
        }

        $taskRep->save($task, true);

        if ($storeHistory) {
            $taskHistoryRep = new TaskHistoryRepository($doctrine);

            foreach($storeHistory as $field => $history) {

                $taskHistory = new TaskHistory();
                $taskHistory->setField($field);
                $taskHistory->setChangedFrom($history[0]);
                $taskHistory->setChangedTo($history[1]);
                $taskHistory->setChangedOn(new DateTime());
                $taskHistory->setChangedBy($user);
                $taskHistory->setTask($task);
                $taskHistoryRep->save($taskHistory, true);
            }
        }

        return $this->json([
            'success' => true,
            'message' => "Task updated",
            'data' => $task
        ]);
    }

    #[Route('/api/task/delete/{taskId}', methods: ['DELETE'])]
    public function delete(ManagerRegistry $doctrine, int $taskId): JsonResponse
    {
        $taskRep = new TasksRepository($doctrine);
        $task = $taskRep->find($taskId);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => "No task found with given id"
            ], Response::HTTP_NOT_FOUND);
        }

        $taskRep->remove($task, true);

        return $this->json([
            'success' => true,
            'message' => "Task deleted"
        ]);
    }

    #[Route('/api/task/close/{taskId}', methods: ['PUT'])]
    public function close(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, int $taskId): JsonResponse
    {
        $taskRep = new TasksRepository($doctrine);
        $task = $taskRep->find($taskId);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => "No task found with given id"
            ], Response::HTTP_NOT_FOUND);
        }

        if ($task->getStatus() == "closed") {
            return $this->json([
                'success' => false,
                'message' => "Invalid operation: cannot close a closed task"
            ], Response::HTTP_BAD_REQUEST);
        }
   
        $task->setStatus("closed");
        $task->setClosedOn(new DateTime());
        $task->setClosedBy($user);
        $taskRep->save($task, true);

        return $this->json([
            'success' => true,
            'message' => "Task closed",
            'data' => $task
        ]);
    }
}
