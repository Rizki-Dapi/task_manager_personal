<?php

namespace App\Repositories;

use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function findById(int $id): ?Project
    {
        return Project::find($id);
    }

    public function paginate(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Project::where('user_id', $userId)->latest()->paginate($perPage);
    }

    public function paginateByFilters(
        int $userId,
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        return Project::query()
            ->where('user_id', $userId)
            ->when(
                isset($filters['status']),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                isset($filters['search']),
                fn ($query) => $query->where('name', 'ilike', '%'.$filters['search'].'%')
            )
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->refresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
