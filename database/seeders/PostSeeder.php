<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::firstOrCreate(['slug' => 'nieuwe-website'], [
            'title' => [
                'nl' => 'Onze nieuwe website is online',
                'fr' => 'Notre nouveau site web est en ligne',
                'en' => 'Our new website is live',
            ],
            'excerpt' => [
                'nl' => 'Ontdek onze expertises, realisaties en het laatste nieuws, voortaan in het Nederlands, Frans en Engels.',
                'fr' => 'Découvrez nos expertises, nos réalisations et nos dernières actualités, désormais en néerlandais, français et anglais.',
                'en' => 'Discover our expertise, projects and latest news, now in Dutch, French and English.',
            ],
            'body' => [
                'nl' => '<p>Welkom op onze vernieuwde website. Hier vindt u voortaan een overzicht van onze expertises, een selectie van onze realisaties en het laatste nieuws over ons bedrijf.</p><p>Heeft u een vraag of project? Neem gerust contact met ons op.</p>',
                'fr' => '<p>Bienvenue sur notre nouveau site web. Vous y trouverez désormais un aperçu de nos expertises, une sélection de nos réalisations et les dernières nouvelles de notre entreprise.</p><p>Une question ou un projet ? N\'hésitez pas à nous contacter.</p>',
                'en' => '<p>Welcome to our new website. Here you will find an overview of our expertise, a selection of our projects and the latest news about our company.</p><p>Have a question or a project in mind? Feel free to get in touch.</p>',
            ],
            'published_at' => now(),
        ]);
    }
}
