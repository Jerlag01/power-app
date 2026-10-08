<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts_newest_first(): void
    {
        Post::factory()->create(['title' => ['nl' => 'Ouder bericht'], 'published_at' => now()->subWeek()]);
        Post::factory()->create(['title' => ['nl' => 'Nieuwer bericht'], 'published_at' => now()->subDay()]);
        Post::factory()->draft()->create(['title' => ['nl' => 'Conceptbericht']]);
        Post::factory()->scheduled()->create(['title' => ['nl' => 'Gepland bericht']]);

        $this->get('/nl/news')
            ->assertOk()
            ->assertSeeInOrder(['Nieuwer bericht', 'Ouder bericht'])
            ->assertDontSee('Conceptbericht')
            ->assertDontSee('Gepland bericht');
    }

    public function test_index_is_paginated_by_nine(): void
    {
        foreach (range(1, 10) as $day) {
            Post::factory()->create(['title' => ['nl' => "Bericht dag {$day}"], 'published_at' => now()->subDays($day)]);
        }

        $this->get('/nl/news')->assertOk()->assertSee('Bericht dag 9')->assertDontSee('Bericht dag 10');
        $this->get('/nl/news?page=2')->assertOk()->assertSee('Bericht dag 10')->assertDontSee('Bericht dag 9');
    }

    public function test_show_displays_post_in_requested_language(): void
    {
        $post = Post::factory()->create([
            'title' => ['nl' => 'Nieuwe website', 'fr' => 'Nouveau site'],
            'body' => ['nl' => '<p>Welkom</p>', 'fr' => '<p>Bienvenue</p>'],
        ]);

        $this->get("/fr/news/{$post->slug}")
            ->assertOk()
            ->assertSee('Nouveau site')
            ->assertSee('<p>Bienvenue</p>', escape: false)
            ->assertDontSee('Welkom');
    }

    public function test_draft_and_scheduled_posts_return_404(): void
    {
        $draft = Post::factory()->draft()->create();
        $scheduled = Post::factory()->scheduled()->create();

        $this->get("/nl/news/{$draft->slug}")->assertNotFound();
        $this->get("/nl/news/{$scheduled->slug}")->assertNotFound();
    }

    public function test_post_body_is_sanitized(): void
    {
        $post = Post::factory()->create([
            'title' => ['nl' => 'Titel <b>vet</b>'],
            'body' => ['nl' => '<p>Tekst</p><script>alert("xss")</script><img src=x onerror="alert(1)">'],
        ]);

        $this->get("/nl/news/{$post->slug}")
            ->assertOk()
            ->assertSee('Titel &lt;b&gt;vet&lt;/b&gt;', escape: false)
            ->assertSee('<p>Tekst</p>', escape: false)
            ->assertDontSee('<script>alert("xss")</script>', escape: false)
            ->assertDontSee('onerror', escape: false);
    }

    public function test_home_page_shows_latest_three_posts(): void
    {
        foreach (range(1, 4) as $day) {
            Post::factory()->create(['title' => ['nl' => "Bericht {$day}"], 'published_at' => now()->subDays($day)]);
        }

        $this->get('/nl')
            ->assertOk()
            ->assertSee('Bericht 1')
            ->assertSee('Bericht 3')
            ->assertDontSee('Bericht 4');
    }
}
