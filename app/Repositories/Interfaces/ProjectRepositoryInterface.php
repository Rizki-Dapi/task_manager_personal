<?php

namespace App\Repositories\Interfaces;

use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function findById(int $id): ?Project;

    public function paginate(int $userId, int $perPage = 10): LengthAwarePaginator;

    public function paginateByFilters(
        int $userId,
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator;

    public function create(array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;
}
