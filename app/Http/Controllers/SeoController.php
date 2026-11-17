<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Employee;
use App\Models\Post;
use App\Models\Project;
use App\Models\Vacancy;
use App\Support\Seo;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

/**
 * robots.txt and sitemap.xml for search engines. Both are generated, so new projects, news and vacancies are
 * listed automatically (in every language, with links between the language versions) and a site that may not be
 * indexed tells robots to stay away.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = Seo::indexable()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /livewire', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $pages = [];
        $add = function (string $route, array $parameters = [], ?CarbonInterface $modified = null, string $priority = '0.5') use (&$pages): void {
            $urls = collect(array_keys(config('app.locales')))->mapWithKeys(fn (string $locale): array => [$locale => route($route, [...$parameters, 'locale' => $locale])])->all();
            $pages[] = ['urls' => $urls, 'lastmod' => $modified, 'priority' => $priority];
        };

        $add('home', priority: '1.0');
        $add('expertises', priority: '0.8');
        $add('projects.index', priority: '0.8');
        $add('posts.index', priority: '0.7');
        $add('contact', priority: '0.7');

        if (Certificate::anyPublished()) {
            $add('certificates', priority: '0.6');
        }

        if (Employee::pageIsAvailable()) {
            $add('who-is-who', priority: '0.5');
        }

        if (Vacancy::anyPublished()) {
            $add('vacancies.index', priority: '0.7');
        }

        $add('privacy', priority: '0.2');

        Project::published()->ordered()->get(['id', 'slug', 'updated_at'])->each(fn (Project $project) => $add('projects.show', ['project' => $project], $project->updated_at, '0.6'));
        Post::latestPublished()->get(['id', 'slug', 'updated_at', 'published_at'])->each(fn (Post $post) => $add('posts.show', ['post' => $post], $post->updated_at, '0.6'));
        Vacancy::published()->ordered()->get(['id', 'slug', 'updated_at'])->each(fn (Vacancy $vacancy) => $add('vacancies.show', ['vacancy' => $vacancy], $vacancy->updated_at, '0.7'));

        return response()
            ->view('seo.sitemap', ['pages' => $pages, 'default' => array_key_first(config('app.locales'))])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
