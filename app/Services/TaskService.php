<?php

namespace App\Services;

use App\Enums\Status;
use App\Exceptions\NotAllowedException;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function findById(int $id, int $userId): ?Task
    {
        $task = $this->taskRepository->findById($id);

        if (! $task) {
            throw new ModelNotFoundException('Task Not found');
        }

        if ($task->project->user_id !== $userId) {
            throw new NotAllowedException('You can only access your own project');
        }

        return $task;
    }

    public function findByProjectId(int $projectId, int $userId): Collection
    {
        $project = $this->projectRepository->findById($projectId);

        if (! $project) {
            throw new ModelNotFoundException('project not found');
        }

        if ($project->user_id !== $userId) {
            throw new NotAllowedException('You can only access your own project');
        }

        return $this->taskRepository->findByProjectId($projectId);
    }

    public function create(array $data): Task
    {
        $data['status'] ??= 'in-progress';

        $task = $this->taskRepository->create($data);

        $this->syncProjectStatus($task->project_id);

        $this->logRepository->record([
            'action' => 'user.create_task',
            'context' => ['task_id' => $task->id],
        ]);

        return $task;
    }

    public function update(Task $task, array $data, User $actingUser): Task
    {
        if ($task->project->user_id !== $actingUser->id) {
            throw new NotAllowedException('You can only update your own Task');
        }

        $task = $this->taskRepository->update($task, $data);

        $this->syncProjectStatus($task->project_id);

        $this->logRepository->record([
            'action' => 'user.update_task',
            'context' => ['task_id' => $task->id],
        ]);

        return $task;
    }

    public function delete(Task $task, User $actingUser): void
    {
        if ($task->project->user_id !== $actingUser->id) {
            throw new NotAllowedException('You can only delete your own Task');
        }

        $projectId = $task->project_id;

        $this->taskRepository->delete($task);

        $this->syncProjectStatus($projectId);

        $this->logRepository->record([
            'action' => 'user.delete_task',
            'context' => ['task_id' => $task->id],
        ]);
    }

    private function syncProjectStatus(int $projectId): void
    {
        $project = $this->projectRepository->findById($projectId);

        if (! $project) {
            return;
        }

        $task = $this->taskRepository->findByProjectId($projectId);

        $allcompleted = $task->isNotEmpty()
            && $task->every(fn(Task $task) => $task->status === Status::COMPLETED->value);

        $newStatus = $allcompleted ? Status::COMPLETED->value : Status::IN_PROGRESS->value;

        if ($project->status !== $newStatus) {
            $this->projectRepository->update($project, ['status' => $newStatus]);

            $this->logRepository->record([
                'action' => 'system.project_auto_status_change',
                'context' => [
                    'project_id' => $project->id,
                    'from' => $project->status,
                    $newStatus < 'to',
                ],
            ]);
        }
    }
}
