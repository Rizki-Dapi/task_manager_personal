<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdatedTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function list(int $projectId)
    {
        $userId = auth('api')->user()->id;

        $task = $this->taskService->findByProjectId($projectId, $userId);

        return response()->json(ApiFormatter::createJson('Tasks retrieved successfully', [
            'tasks' => TaskResource::collection($task),
        ]), Response::HTTP_OK);
    }

    //
    public function show(int $id)
    {
        $userId = auth('api')->user()->id;

        $task = $this->taskService->findById($id, $userId);

        return response()->json(ApiFormatter::createJson('Task retrieved successfully', [
            'task' => new TaskResource($task),
        ]), Response::HTTP_OK);
    }

    public function store(StoreTaskRequest $req)
    {
        $task = $this->taskService->create($req->validated());

        return response()->json(ApiFormatter::createJson('Task created successfully', [
            'task' => new TaskResource($task),
        ]), Response::HTTP_CREATED);
    }

    public function update(UpdatedTaskRequest $req, Task $task)
    {
        $task = $this->taskService->update($task, $req->validated(), $req->user());

        return response()->json(ApiFormatter::createJson('Task update successfully', [
            'task' => new TaskResource($task),
        ]), Response::HTTP_OK);
    }

    public function destroy(Task $task, Request $req)
    {
        $this->taskService->delete($task, $req->user());

        return response()->json(ApiFormatter::createJson('Task delete successfully'));
    }
}
