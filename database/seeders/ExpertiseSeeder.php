<?php

namespace Database\Seeders;

use App\Models\Expertise;
use Illuminate\Database\Seeder;

class ExpertiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expertises = [
            [
                'slug' => 'elektrische-installaties',
                'icon' => 'bolt',
                'title' => ['nl' => 'Elektrische installaties', 'fr' => 'Installations électriques', 'en' => 'Electrical installations'],
                'description' => [
                    'nl' => 'Ontwerp en uitvoering van complete elektrische installaties voor kantoren, handelspanden, industrie en residentiële gebouwen, conform het AREI.',
                    'fr' => 'Conception et réalisation d\'installations électriques complètes pour bureaux, commerces, industrie et bâtiments résidentiels, conformément au RGIE.',
                    'en' => 'Design and installation of complete electrical systems for offices, retail, industry and residential buildings, compliant with Belgian regulations (AREI/RGIE).',
                ],
                'services' => [
                    'nl' => ['Nieuwbouw & renovatie', 'Utiliteitsbouw', 'Industriële installaties', 'Studie & plannen'],
                    'fr' => ['Neuf & rénovation', 'Bâtiments tertiaires', 'Installations industrielles', 'Étude & plans'],
                    'en' => ['New build & renovation', 'Commercial buildings', 'Industrial installations', 'Design & drawings'],
                ],
            ],
            [
                'slug' => 'laagspanning-en-borden',
                'icon' => 'panel',
                'title' => ['nl' => 'Laagspanning & verdeelborden', 'fr' => 'Basse tension & tableaux', 'en' => 'Low voltage & switchboards'],
                'description' => [
                    'nl' => 'Bouw, plaatsing en vernieuwing van hoofd- en onderverdeelborden, voedingskabels en energiedistributie binnen gebouwen.',
                    'fr' => 'Construction, pose et renouvellement de tableaux généraux et divisionnaires, câbles d\'alimentation et distribution d\'énergie dans les bâtiments.',
                    'en' => 'Building, installing and renewing main and sub-distribution boards, power cabling and energy distribution within buildings.',
                ],
                'services' => [
                    'nl' => ['Hoofdverdeelborden', 'Onderverdeelborden', 'Voedingskabels', 'Energiemeting'],
                    'fr' => ['Tableaux généraux', 'Tableaux divisionnaires', 'Câbles d\'alimentation', 'Comptage d\'énergie'],
                    'en' => ['Main switchboards', 'Sub-distribution boards', 'Power cabling', 'Energy metering'],
                ],
            ],
            [
                'slug' => 'verlichting',
                'icon' => 'light',
                'title' => ['nl' => 'Verlichting', 'fr' => 'Éclairage', 'en' => 'Lighting'],
                'description' => [
                    'nl' => 'Binnen- en buitenverlichting, noodverlichting en LED-renovaties die het energieverbruik drastisch verlagen.',
                    'fr' => 'Éclairage intérieur et extérieur, éclairage de secours et rénovations LED qui réduisent fortement la consommation d\'énergie.',
                    'en' => 'Indoor and outdoor lighting, emergency lighting and LED retrofits that drastically cut energy use.',
                ],
                'services' => [
                    'nl' => ['LED-renovatie', 'Noodverlichting', 'Buitenverlichting', 'Lichtsturing'],
                    'fr' => ['Rénovation LED', 'Éclairage de secours', 'Éclairage extérieur', 'Gestion de l\'éclairage'],
                    'en' => ['LED retrofit', 'Emergency lighting', 'Outdoor lighting', 'Lighting control'],
                ],
            ],
            [
                'slug' => 'data-en-netwerken',
                'icon' => 'network',
                'title' => ['nl' => 'Data & netwerken', 'fr' => 'Données & réseaux', 'en' => 'Data & networks'],
                'description' => [
                    'nl' => 'Gestructureerde bekabeling, glasvezel en netwerkinfrastructuur voor betrouwbare data- en telecomverbindingen.',
                    'fr' => 'Câblage structuré, fibre optique et infrastructure réseau pour des connexions de données et télécoms fiables.',
                    'en' => 'Structured cabling, fibre optics and network infrastructure for reliable data and telecom connections.',
                ],
                'services' => [
                    'nl' => ['Koper- & glasvezelbekabeling', 'Patchkasten', 'Wifi-infrastructuur', 'Certificatiemetingen'],
                    'fr' => ['Câblage cuivre & fibre', 'Baies de brassage', 'Infrastructure wifi', 'Mesures de certification'],
                    'en' => ['Copper & fibre cabling', 'Patch cabinets', 'Wi-Fi infrastructure', 'Certification testing'],
                ],
            ],
            [
                'slug' => 'energie-en-laadinfrastructuur',
                'icon' => 'solar',
                'title' => ['nl' => 'Energie & laadinfrastructuur', 'fr' => 'Énergie & bornes de recharge', 'en' => 'Energy & EV charging'],
                'description' => [
                    'nl' => 'Integratie van zonnepanelen, batterijopslag en laadpalen voor elektrische voertuigen in nieuwe en bestaande installaties.',
                    'fr' => 'Intégration de panneaux solaires, de stockage par batterie et de bornes de recharge pour véhicules électriques dans des installations neuves et existantes.',
                    'en' => 'Integrating solar panels, battery storage and EV chargers into new and existing installations.',
                ],
                'services' => [
                    'nl' => ['Zonnepanelen', 'Batterijopslag', 'Laadpalen', 'Energiebeheer'],
                    'fr' => ['Panneaux solaires', 'Stockage par batterie', 'Bornes de recharge', 'Gestion de l\'énergie'],
                    'en' => ['Solar panels', 'Battery storage', 'EV chargers', 'Energy management'],
                ],
            ],
            [
                'slug' => 'onderhoud-en-interventies',
                'icon' => 'shield',
                'title' => ['nl' => 'Onderhoud & interventies', 'fr' => 'Maintenance & interventions', 'en' => 'Maintenance & call-outs'],
                'description' => [
                    'nl' => 'Preventief onderhoud, conformiteitsaanpassingen en snelle interventies om uw installatie veilig en beschikbaar te houden.',
                    'fr' => 'Maintenance préventive, mises en conformité et interventions rapides pour garder votre installation sûre et disponible.',
                    'en' => 'Preventive maintenance, compliance upgrades and fast call-outs to keep your installation safe and available.',
                ],
                'services' => [
                    'nl' => ['Preventief onderhoud', 'Herstellingen', 'Conformiteit AREI', 'Thermografie'],
                    'fr' => ['Maintenance préventive', 'Réparations', 'Conformité RGIE', 'Thermographie'],
                    'en' => ['Preventive maintenance', 'Repairs', 'Regulatory compliance', 'Thermography'],
                ],
            ],
        ];

        foreach ($expertises as $order => $expertise) {
            Expertise::updateOrCreate(
                ['slug' => $expertise['slug']],
                [...$expertise, 'sort_order' => $order],
            );
        }
    }
}
