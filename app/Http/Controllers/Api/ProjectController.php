<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdatedProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function list(Request $req)
    {
        $userId = auth('api')->user()->id;

        $project = $this->projectService->list(
            $userId,
            (int) $req->query('per_page', 10),
        );

        return response()->json(ApiFormatter::createJson('Projects retrieved successfully', [
            'project' => ProjectResource::collection($project),
            'pagination' => [
                'current_page' => $project->currentPage(),
                'last_page' => $project->lastPage(),
                'per_page' => $project->perPage(),
                'total' => $project->total(),
            ],
        ]), Response::HTTP_OK);
    }

    public function listWithFilter(Request $req)
    {
        $userId = auth('api')->user()->id;

        $project = $this->projectService->listByfilter(
            $userId,
            filters: $req->only(['search', 'status']),
            perPage: (int) $req->query('per_page', 10)
        );

        return response()->json(ApiFormatter::createJson('Projects retrieved successfully', [
            'project' => ProjectResource::collection($project),
            'pagination' => [
                'current_page' => $project->currentPage(),
                'last_page' => $project->lastPage(),
                'per_page' => $project->perPage(),
                'total' => $project->total(),
            ],
        ]), Response::HTTP_OK);
    }

    public function show(int $id)
    {
        $userId = auth('api')->user();
        $project = $this->projectService->find($id, $userId);

        return response()->json(ApiFormatter::createJson('Project retrieved successfully', [
            'project' => new ProjectResource($project),
        ]), Response::HTTP_OK);
    }

    public function store(StoreProjectRequest $req)
    {
        $project = $this->projectService->create($req->validated(), $req->user());

        return response()->json(ApiFormatter::createJson('Project created successfully', [
            'project' => new ProjectResource($project),
        ]), Response::HTTP_CREATED);
    }

    public function update(UpdatedProjectRequest $req, Project $project)
    {
        $project = $this->projectService->update($project, $req->validated(), $req->user());

        return response()->json(ApiFormatter::createJson('Project updated successfully', [
            'project' => new ProjectResource($project),
        ]), Response::HTTP_OK);
    }

    public function destroy(Request $req, Project $project)
    {
        $this->projectService->delete($project, $req->user());

        return response()->json(ApiFormatter::createJson('Project delete successfully'));
    }
}
