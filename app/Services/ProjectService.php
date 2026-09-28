<?php

namespace App\Services;

use App\Exceptions\NotAllowedException;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function list(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->projectRepository->paginate($userId, $perPage);
    }

    public function find(int $id, User $actingUser): Project
    {
        $project = $this->projectRepository->findById($id);

        if (! $project) {
            throw new ModelNotFoundException('Project not found');
        }

        if ($project->user_id !== $actingUser->id) {
            throw new NotAllowedException('you can only update your own project');
        }

        return $project;
    }

    public function listByfilter(int $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->projectRepository->paginateByFilters($userId, $filters, $perPage);
    }

    public function create(array $data, User $user): Project
    {
        $data['status'] ??= 'in-progress';

        $project = $this->projectRepository->create([
            ...$data,
            'user_id' => $user->id,
        ]);

        $this->logRepository->record([
            'actions' => 'user.project_created',
            'context' => ['project_id' => $project->id],
        ]);

        return $project;
    }

    public function update(Project $project, array $data, User $actingUser): Project
    {
        if ($project->user_id !== $actingUser->id) {
            throw new NotAllowedException('you can only update your own project');
        }

        $updated = $this->projectRepository->update($project, $data);

        $this->logRepository->record([
            'action' => 'user.projectUpdate',
            'conetxt' => ['project_id' => $project->id],
        ]);

        return $updated;
    }

    public function delete(Project $project, User $actingUser): void
    {
        if ($project->user_id !== $actingUser->id) {
            throw new NotAllowedException('You can only delete your own project');
        }

        $this->projectRepository->delete($project);

        $this->logRepository->record([
            'action' => 'user.delete_project',
            'context' => ['project_id' => $project->id],
        ]);
    }
}
