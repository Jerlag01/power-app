<?php

namespace Tests\Feature;

use App\Models\Expertise;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_published_projects_only(): void
    {
        Project::factory()->create(['title' => ['nl' => 'Zichtbaar project']]);
        Project::factory()->unpublished()->create(['title' => ['nl' => 'Verborgen project']]);

        $this->get('/nl/projects')
            ->assertOk()
            ->assertSee('Zichtbaar project')
            ->assertDontSee('Verborgen project');
    }

    public function test_index_can_be_filtered_by_expertise(): void
    {
        $lighting = Expertise::factory()->create(['slug' => 'verlichting']);
        Project::factory()->for($lighting)->create(['title' => ['nl' => 'LED-project']]);
        Project::factory()->create(['title' => ['nl' => 'Ander project']]);

        $this->get('/nl/projects?expertise=verlichting')
            ->assertOk()
            ->assertSee('LED-project')
            ->assertDontSee('Ander project');
    }

    public function test_unknown_expertise_filter_shows_all_projects(): void
    {
        Project::factory()->create(['title' => ['nl' => 'Eerste project']]);

        $this->get('/nl/projects?expertise=bestaat-niet')
            ->assertOk()
            ->assertSee('Eerste project');
    }

    public function test_show_displays_translated_project_and_related_projects(): void
    {
        $expertise = Expertise::factory()->create();
        $project = Project::factory()->for($expertise)->create([
            'title' => ['nl' => 'Hoofdproject', 'en' => 'Main project'],
            'highlights' => [['value' => '42', 'label' => ['nl' => 'laadpunten', 'en' => 'charge points']]],
        ]);
        Project::factory()->for($expertise)->create(['title' => ['nl' => 'Verwant', 'en' => 'Related one']]);

        $this->get("/en/projects/{$project->slug}")
            ->assertOk()
            ->assertSee('Main project')
            ->assertSee('charge points')
            ->assertSee('Related one');
    }

    public function test_unpublished_project_returns_404(): void
    {
        $project = Project::factory()->unpublished()->create();

        $this->get("/nl/projects/{$project->slug}")->assertNotFound();
    }

    public function test_show_returns_404_for_unknown_project(): void
    {
        $this->get('/nl/projects/bestaat-niet')->assertNotFound();
    }
}
