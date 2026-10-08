<?php

namespace Database\Seeders;

use App\Models\Expertise;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Example projects to fill the layout during development.
 * Replace them with real references before going live.
 */
class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expertiseIds = Expertise::pluck('id', 'slug');

        $projects = [
            [
                'expertise' => 'elektrische-installaties',
                'slug' => 'voorbeeld-kantoorgebouw',
                'location' => 'Brussel',
                'title' => ['nl' => 'Renovatie kantoorgebouw', 'fr' => 'Rénovation d\'un immeuble de bureaux', 'en' => 'Office building renovation'],
                'summary' => [
                    'nl' => 'Volledige vernieuwing van de elektrische installatie van een kantoorgebouw in gebruik.',
                    'fr' => 'Renouvellement complet de l\'installation électrique d\'un immeuble de bureaux en activité.',
                    'en' => 'Complete renewal of the electrical installation of an occupied office building.',
                ],
                'description' => [
                    'nl' => 'Gefaseerde vernieuwing van de volledige elektrische installatie terwijl het gebouw in gebruik bleef. Nieuwe verdeelborden, verlichting en databekabeling per verdieping, met minimale hinder voor de gebruikers.',
                    'fr' => 'Renouvellement par phases de toute l\'installation électrique pendant que le bâtiment restait occupé. Nouveaux tableaux, éclairage et câblage de données par étage, avec un minimum de gêne pour les occupants.',
                    'en' => 'Phased renewal of the entire electrical installation while the building stayed in use. New switchboards, lighting and data cabling floor by floor, with minimal disruption for occupants.',
                ],
                'highlights' => [
                    ['value' => '8', 'label' => ['nl' => 'verdiepingen', 'fr' => 'étages', 'en' => 'floors']],
                    ['value' => '0', 'label' => ['nl' => 'dagen sluiting', 'fr' => 'jours de fermeture', 'en' => 'days closed']],
                ],
                'is_featured' => true,
            ],
            [
                'expertise' => 'verlichting',
                'slug' => 'voorbeeld-led-renovatie',
                'location' => 'Vlaams-Brabant',
                'title' => ['nl' => 'LED-renovatie magazijn', 'fr' => 'Rénovation LED d\'un entrepôt', 'en' => 'Warehouse LED retrofit'],
                'summary' => [
                    'nl' => 'Vervanging van conventionele verlichting door LED met daglicht- en aanwezigheidsdetectie.',
                    'fr' => 'Remplacement de l\'éclairage conventionnel par du LED avec détection de lumière du jour et de présence.',
                    'en' => 'Replacing conventional lighting with LED, including daylight and presence detection.',
                ],
                'description' => [
                    'nl' => 'Vervanging van de bestaande hogedruklampen door LED-armaturen met daglicht- en aanwezigheidsdetectie, inclusief nieuwe noodverlichting.',
                    'fr' => 'Remplacement des lampes haute pression existantes par des luminaires LED avec détection de lumière du jour et de présence, y compris un nouvel éclairage de secours.',
                    'en' => 'Replacing the existing high-pressure lamps with LED fittings with daylight and presence detection, including new emergency lighting.',
                ],
                'highlights' => [
                    ['value' => '60%', 'label' => ['nl' => 'energiebesparing', 'fr' => 'd\'économie d\'énergie', 'en' => 'energy saved']],
                ],
            ],
            [
                'expertise' => 'laagspanning-en-borden',
                'slug' => 'voorbeeld-hoofdverdeelbord',
                'location' => 'Brussel',
                'title' => ['nl' => 'Nieuw hoofdverdeelbord', 'fr' => 'Nouveau tableau général', 'en' => 'New main switchboard'],
                'summary' => [
                    'nl' => 'Ontwerp en plaatsing van een nieuw hoofdverdeelbord voor een handelspand.',
                    'fr' => 'Conception et pose d\'un nouveau tableau général pour un commerce.',
                    'en' => 'Design and installation of a new main switchboard for a retail building.',
                ],
                'description' => [
                    'nl' => 'Studie, bouw en indienststelling van een nieuw hoofdverdeelbord met energiemeting per vertrek, uitgevoerd tijdens één nachtelijke onderbreking.',
                    'fr' => 'Étude, construction et mise en service d\'un nouveau tableau général avec comptage par départ, réalisées en une seule coupure de nuit.',
                    'en' => 'Design, build and commissioning of a new main switchboard with per-circuit energy metering, completed during a single overnight shutdown.',
                ],
                'highlights' => [
                    ['value' => '1', 'label' => ['nl' => 'nacht onderbreking', 'fr' => 'nuit de coupure', 'en' => 'night of downtime']],
                ],
            ],
            [
                'expertise' => 'data-en-netwerken',
                'slug' => 'voorbeeld-netwerkbekabeling',
                'location' => 'Brussel',
                'title' => ['nl' => 'Netwerkbekabeling campus', 'fr' => 'Câblage réseau d\'un campus', 'en' => 'Campus network cabling'],
                'summary' => [
                    'nl' => 'Gestructureerde bekabeling en glasvezelbackbone tussen meerdere gebouwen.',
                    'fr' => 'Câblage structuré et dorsale fibre optique entre plusieurs bâtiments.',
                    'en' => 'Structured cabling and a fibre backbone between several buildings.',
                ],
                'description' => [
                    'nl' => 'Plaatsing van een glasvezelbackbone tussen de gebouwen en Cat6A-bekabeling naar alle werkplekken, inclusief certificatiemetingen.',
                    'fr' => 'Pose d\'une dorsale fibre optique entre les bâtiments et d\'un câblage Cat6A vers tous les postes de travail, mesures de certification comprises.',
                    'en' => 'Installing a fibre backbone between buildings and Cat6A cabling to every workstation, including certification testing.',
                ],
                'highlights' => [
                    ['value' => 'Cat6A', 'label' => ['nl' => 'gecertificeerd', 'fr' => 'certifié', 'en' => 'certified']],
                ],
            ],
            [
                'expertise' => 'energie-en-laadinfrastructuur',
                'slug' => 'voorbeeld-laadpleinen',
                'location' => 'Waals-Brabant',
                'title' => ['nl' => 'Laadinfrastructuur bedrijfsparking', 'fr' => 'Bornes de recharge pour un parking d\'entreprise', 'en' => 'EV charging for a company car park'],
                'summary' => [
                    'nl' => 'Plaatsing van laadpalen met dynamisch lastbeheer op een bedrijfsparking.',
                    'fr' => 'Installation de bornes de recharge avec gestion dynamique de la charge sur un parking d\'entreprise.',
                    'en' => 'Installing EV chargers with dynamic load management in a company car park.',
                ],
                'description' => [
                    'nl' => 'Uitbreiding van de aansluiting en plaatsing van laadpalen met dynamisch lastbeheer, gekoppeld aan de bestaande zonnepaneleninstallatie.',
                    'fr' => 'Renforcement du raccordement et installation de bornes avec gestion dynamique de la charge, couplées à l\'installation photovoltaïque existante.',
                    'en' => 'Upgrading the grid connection and installing chargers with dynamic load management, linked to the existing solar installation.',
                ],
                'highlights' => [
                    ['value' => '20', 'label' => ['nl' => 'laadpunten', 'fr' => 'points de recharge', 'en' => 'charge points']],
                ],
            ],
            [
                'expertise' => 'onderhoud-en-interventies',
                'slug' => 'voorbeeld-onderhoudscontract',
                'location' => 'Brussel',
                'title' => ['nl' => 'Onderhoudscontract vastgoedportefeuille', 'fr' => 'Contrat de maintenance d\'un parc immobilier', 'en' => 'Property portfolio maintenance contract'],
                'summary' => [
                    'nl' => 'Preventief onderhoud en interventies voor een portefeuille van appartementsgebouwen.',
                    'fr' => 'Maintenance préventive et interventions pour un parc d\'immeubles à appartements.',
                    'en' => 'Preventive maintenance and call-outs for a portfolio of apartment buildings.',
                ],
                'description' => [
                    'nl' => 'Periodieke inspectie, thermografie van verdeelborden en snelle interventie bij storingen voor een portefeuille van residentiële gebouwen.',
                    'fr' => 'Inspection périodique, thermographie des tableaux et intervention rapide en cas de panne pour un parc de bâtiments résidentiels.',
                    'en' => 'Periodic inspection, switchboard thermography and rapid response to faults for a portfolio of residential buildings.',
                ],
                'highlights' => [
                    ['value' => '24/7', 'label' => ['nl' => 'bereikbaar', 'fr' => 'joignable', 'en' => 'available']],
                ],
            ],
        ];

        foreach ($projects as $order => $project) {
            $expertiseSlug = $project['expertise'];
            unset($project['expertise']);

            Project::updateOrCreate(
                ['slug' => $project['slug']],
                [...$project, 'expertise_id' => $expertiseIds[$expertiseSlug] ?? null, 'sort_order' => $order],
            );
        }
    }
}
