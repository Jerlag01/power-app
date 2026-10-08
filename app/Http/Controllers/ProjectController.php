<?php

namespace App\Http\Controllers;

use App\Models\Expertise;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $expertises = Expertise::ordered()->get();
        $activeExpertise = $expertises->firstWhere('slug', $request->query('expertise'));

        $projects = Project::with('expertise')
            ->published()
            ->when($activeExpertise, fn ($query) => $query->whereBelongsTo($activeExpertise))
            ->ordered()
            ->get();

        return view('projects.index', compact('expertises', 'activeExpertise', 'projects'));
    }

    public function show(Project $project): View
    {
        abort_unless($project->is_published, 404);

        $project->load('expertise');

        return view('projects.show', [
            'project' => $project,
            'relatedProjects' => Project::with('expertise')
                ->published()
                ->whereKeyNot($project->getKey())
                ->where('expertise_id', $project->expertise_id)
                ->ordered()
                ->limit(3)
                ->get(),
        ]);
    }
}
