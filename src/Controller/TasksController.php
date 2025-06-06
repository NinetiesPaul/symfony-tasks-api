<?php

namespace App\Controller;

use DateTime;
use App\Entity\TaskAssignee;
use App\Entity\TaskComments;
use App\Entity\TaskHistory;
use App\Entity\User;
use App\Entity\Tasks;
use App\Repository\TaskAssigneeRepository;
use App\Repository\TaskCommentsRepository;
use App\Repository\TaskHistoryRepository;
use App\Repository\UserRepository;
use App\Repository\TasksRepository;
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

        $typeQueryParameter = $request->query->get('type');
        $statusQueryParameter = $request->query->get('status');
        $createdByQueryParameter = $request->query->get('created_by');
        $assignedToUserParameter = $request->query->get('assigned');

        $parameters = [];
        $parametersErrors = [];
        if ($typeQueryParameter !== null) {
            if (!Tasks::allowedTypes($typeQueryParameter)) {
                $parametersErrors[] = "INVALID_TYPE";
            }

            $parameters['type'] = $request->query->get('type');
        }
        
        if ($statusQueryParameter !== null) {
            if (!Tasks::allowedStatuses($statusQueryParameter)) {
                $parametersErrors[] = "INVALID_STATUS";
            }

            $parameters['status'] = $request->query->get('status');
        }
        
        if ($createdByQueryParameter !== null) {
            $userRep = new UserRepository($doctrine);
            $user = $userRep->find($createdByQueryParameter);

            if (!$user) {
                $parametersErrors[] = "USER_NOT_FOUND";
            }

            $parameters['createdBy'] = $request->query->get('created_by');
        }
        
        if ($assignedToUserParameter !== null) {
            $assignedToUserParameter = ($request->query->get('assigned') === "false") ? false : true;
        }

        if ($parametersErrors) {
            return $this->json([
                'success' => false,
                'message' => $parametersErrors
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $tasks = $taskRep->findBy($parameters, [ 'id' => 'DESC' ]);
        $result = [];

        foreach ($tasks as &$task) {
            if (!is_null($assignedToUserParameter) && (!$assignedToUserParameter && count($task->getAssignees()) > 0) || ($assignedToUserParameter && count($task->getAssignees()) == 0)) {
                continue;
            }
            $task->hideFields([ 'comments', 'history' ]);
            $result[] = $task;
        }

        return $this->json([
            'success' => true,
            'data' => [
                'total' => count($result),
                'tasks' => $result
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
                'message' => [ "TASK_NOT_FOUND" ]
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
    
        $validationResult = $validator->validate((array) $request,
            new Assert\Collection([
                'title' => [
                    new Assert\Required(),
                    new Assert\NotBlank(null, "EMPTY_TITLE"),
                    new Assert\Type("string", "TITLE_NOT_STRING"),
                ],
                'description' => [
                    new Assert\Required(),
                    new Assert\NotBlank(null, "EMPTY_DESCRIPTION"),
                    new Assert\Type("string", "DESCRIPTION_NOT_STRING"),
                ],
                'type' => [
                    new Assert\Required(),
                    new Assert\NotBlank(null, "EMPTY_TYPE"),
                    new Assert\Type("string", "TYPE_NOT_STRING"),
                    new Assert\Choice([], [ 'feature', 'bugfix', 'hotfix' ], null, null, null, null, null, "INVALID_TYPE"),
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
        
        $task = new Tasks();
        $task->setTitle($request->title);
        $task->setDescription($request->description);
        $task->setStatus("open");
        $task->setType($request->type);
        $task->setCreatedOn(new DateTime());
        $task->setCreatedBy($user);

        $taskRep = new TasksRepository($doctrine);
        $taskRep->save($task, true);

        $task->hideFields([ 'assignees', 'comments', 'history' ]);

        return $this->json([
            'success' => true,
            'data' => $task
        ]);
    }

    #[Route('/api/task/update/{taskId}', methods: ['PUT'])]
    public function update(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, ValidatorInterface $validator, Request $request, int $taskId): JsonResponse
    {
        $taskRep = new TasksRepository($doctrine);
        $task = $taskRep->find($taskId);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => [ "TASK_NOT_FOUND" ]
            ], Response::HTTP_NOT_FOUND);
        }

        if ($task->getStatus() == "closed") {
            return $this->json([
                'success' => false,
                'message' => [ "TASK_CLOSED" ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $storeHistory = false;
        $request = json_decode($request->getContent());
        $constraints = [];
   
        if (isset($request->title)){
            if ($task->getTitle() != $request->title) {
                $storeHistory['title'] = [ $task->getTitle(), $request->title ];
            }
            $task->setTitle($request->title);

            $constraints['title'] = [
                new Assert\NotBlank(null, "EMPTY_TITLE"),
                new Assert\Type("string", "TITLE_NOT_STRING"),
            ];
        }
   
        if (isset($request->description)){
            if ($task->getDescription() != $request->description) {
                $storeHistory['description'] = [ $task->getDescription(), $request->description ];
            }
            $task->setDescription($request->description);

            $constraints['description'] = [
                new Assert\NotBlank(null, "EMPTY_DESCRIPTION"),
                new Assert\Type("string", "DESCRIPTION_NOT_STRING"),
            ];
        }
        
        if (isset($request->type)) {
            if ($task->getType() != $request->type) {
                $storeHistory['type'] = [ $task->getType(), $request->type ];
            }
            $task->setType($request->type);

            $constraints['type'] = [
                new Assert\NotBlank(null, "EMPTY_TYPE"),
                new Assert\Type("string", "TYPE_NOT_STRING"),
                new Assert\Choice([], [ 'feature', 'bugfix', 'hotfix' ], null, null, null, null, null, "INVALID_TYPE"),
            ];
        }
        
        if (isset($request->status)) {
            if ($request->status === "closed") {
                return $this->json([
                    'success' => false,
                    'message' => [ "CAN_NOT_UPDATE_TO_CLOSE" ]
                ], Response::HTTP_BAD_REQUEST);
            }
            if ($task->getStatus() != $request->status) {
                $storeHistory['status'] = [ $task->getStatus(), $request->status ];
            }
            $task->setStatus($request->status);

            $constraints['status'] = [
                new Assert\NotBlank(null, "EMPTY_STATUS"),
                new Assert\Type("string", "STATUS_NOT_STRING"),
                new Assert\Choice([], [ 'open', 'closed', 'in_dev', 'blocked', 'in_qa' ], null, null, null, null, null, "INVALID_STATUS"),
            ];
        }

        if (count($constraints) > 0) {
            $validationResult = $validator->validate((array) $request,
                new Assert\Collection($constraints)
            );

            if (count($validationResult) > 0) {
                $messages = [];
    
                foreach ($validationResult as $error) {
                    $messages[] = $error->getMessage();
                }
                
                return $this->json([
                    'success' => false,
                    'message' => $messages
                ], Response::HTTP_BAD_REQUEST);
            }
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
            'success' => true
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
                'message' => [ "TASK_NOT_FOUND" ]
            ], Response::HTTP_NOT_FOUND);
        }

        if ($task->getStatus() == "closed") {
            return $this->json([
                'success' => false,
                'message' => [ "TASK_ALREADY_CLOSED" ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $taskHistory = new TaskHistory();
        $taskHistory->setField('status');
        $taskHistory->setChangedFrom($task->getStatus());
        $taskHistory->setChangedTo('closed');
        $taskHistory->setChangedOn(new DateTime());
        $taskHistory->setChangedBy($user);
        $taskHistory->setTask($task);
        
        $taskHistoryRep = new TaskHistoryRepository($doctrine);
        $taskHistoryRep->save($taskHistory, true);

        $task->setStatus("closed");
        $task->setClosedOn(new DateTime());
        $task->setClosedBy($user);
        $taskRep->save($task, true);

        return $this->json([
            'success' => true,
            'data' => $task
        ]);
    }

    #[Route('/api/task/assign/{taskId}', methods: ['POST'])]
    public function assign(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, ValidatorInterface $validator, Request $request, int $taskId): JsonResponse
    {
        $request = json_decode($request->getContent());

        $taskRep = new TasksRepository($doctrine);
        $task = $taskRep->find($taskId);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => [ "TASK_NOT_FOUND" ]
            ], Response::HTTP_NOT_FOUND);
        }

        $validationResult = $validator->validate((array) $request,
            new Assert\Collection([
                'assigned_to' => [
                    new Assert\Required(),
                    new Assert\Type("int", "ASSIGNED_TO_NOT_INTEGER"),
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
        $assignedTo = $userRep->find($request->assigned_to);

        if (!$assignedTo) {
            return $this->json([
                'success' => false,
                'message' => [ "USER_NOT_FOUND" ]
            ], Response::HTTP_NOT_FOUND);
        }

        $taskAssigneeRep = new TaskAssigneeRepository($doctrine);
        $taskAlreadyAssignedTo = $taskAssigneeRep->findOneBy([ 'assignedTo' => $assignedTo, 'task' => $task ]);
        if ($taskAlreadyAssignedTo) {
            return $this->json([
                'success' => false,
                'message' => [ "USER_ALREADY_ASSIGNED" ]
            ], Response::HTTP_ACCEPTED);
        }

        $taskAssignee = new TaskAssignee();
        $taskAssignee->setAssignedBy($user);
        $taskAssignee->setAssignedTo($assignedTo);
        $taskAssignee->setTask($task);
        
        $taskAssigneeRep->save($taskAssignee, true);

        $taskHistory = new TaskHistory();
        $taskHistory->setField('added_assignee');
        $taskHistory->setChangedFrom("");
        $taskHistory->setChangedTo($assignedTo->getName());
        $taskHistory->setChangedOn(new DateTime());
        $taskHistory->setChangedBy($user);
        $taskHistory->setTask($task);
        
        $taskHistoryRep = new TaskHistoryRepository($doctrine);
        $taskHistoryRep->save($taskHistory, true);

        return $this->json([
            'success' => true,
            'data' => $taskAssignee
        ]);
    }

    #[Route('/api/task/unassign/{assignmentId}', methods: ['DELETE'])]
    public function unassign(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, int $assignmentId): JsonResponse
    {
        $taskAssignmentRep = new TaskAssigneeRepository($doctrine);
        $taskAssignment = $taskAssignmentRep->find($assignmentId);

        if (!$taskAssignment) {
            return $this->json([
                'success' => false,
                'message' => [ "ASSIGNMENT_NOT_FOUND" ]
            ], Response::HTTP_NOT_FOUND);
        }

        $taskAssignmentRep->remove($taskAssignment, true);

        $taskHistory = new TaskHistory();
        $taskHistory->setField('removed_assignee');
        $taskHistory->setChangedFrom("");
        $taskHistory->setChangedTo($taskAssignment->getAssignedTo()->getName());
        $taskHistory->setChangedOn(new DateTime());
        $taskHistory->setChangedBy($user);
        $taskHistory->setTask($taskAssignment->getTask());
        
        $taskHistoryRep = new TaskHistoryRepository($doctrine);
        $taskHistoryRep->save($taskHistory, true);

        return $this->json([
            'success' => true
        ]);
    }

    #[Route('/api/task/comment/{taskId}', methods: ['POST'])]
    public function addComment(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, ValidatorInterface $validator, Request $request, int $taskId): JsonResponse
    {
        $request = json_decode($request->getContent());
    
        $validationResult = $validator->validate((array) $request,
            new Assert\Collection([
                'text' => [
                    new Assert\Required(),
                    new Assert\NotBlank(null, "EMPTY_TEXT"),
                    new Assert\Type("string", "TEXT_NOT_STRING"),
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

        $taskRep = new TasksRepository($doctrine);
        $task = $taskRep->find($taskId);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => [ "TASK_NOT_FOUND" ]
            ], Response::HTTP_NOT_FOUND);
        }

        $taskComment = new TaskComments();
        $taskComment->setCommentText($request->text);
        $taskComment->setTask($task);
        $taskComment->setCreatedBy($user);
        $taskComment->setCreatedOn(new DateTime());
        
        $taskCommentsRep = new TaskCommentsRepository($doctrine);
        $taskCommentsRep->save($taskComment, true);

        return $this->json([
            'success' => true,
            'data' => $taskComment
        ]);
    }

    #[Route('/api/task/comment/{commentId}', methods: ['DELETE'])]
    public function deleteComment(ManagerRegistry $doctrine, #[CurrentUser] ?User $user, Request $request, int $commentId): JsonResponse
    {
        $request = json_decode($request->getContent());

        $taskCommentsRep = new TaskCommentsRepository($doctrine);
        $taskComment = $taskCommentsRep->find($commentId);

        if (!$taskComment) {
            return $this->json([
                'success' => false,
                'message' => [ "COMMENT_NOT_FOUND" ]
            ], Response::HTTP_NOT_FOUND);
        }

        $taskCommentsRep->remove($taskComment, true);

        return $this->json([
            'success' => true
        ]);
    }
}
