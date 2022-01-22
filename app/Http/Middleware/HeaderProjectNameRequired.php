<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Traits\ApiService;
use Closure;
use Illuminate\Http\Request;

class HeaderProjectNameRequired
{
    use ApiService;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $projectName = $request->header('project-name');
        $project = Project::where('name', $projectName)->first();
        if (!$project) {
            return $this->responseJson(['message' => 'project not found!'], 400);
        }
        return $next($request, $project);
    }
}
