<?php

namespace Database\Seeders;

use App\Models\KitType;
use Illuminate\Database\Seeder;

class KitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Generic fallback types
            ['name' => 'Dynamic Climbing Rope', 'category' => 'Rope', 'interval_months' => 6, 'lifts_people' => true],
            ['name' => 'Static Access Rope', 'category' => 'Rope', 'interval_months' => 6, 'lifts_people' => true],
            ['name' => 'Rigging Sling / Strop', 'category' => 'Sling', 'interval_months' => 12, 'lifts_people' => false],

            // Harnesses
            ['name' => 'Avao Bod Harness', 'category' => 'Harness', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max user weight: 140 kg', 'inspection_price' => '$350 - $450'],
            ['name' => 'TreeMotion Pro Saddle', 'category' => 'Harness', 'brand' => 'Teufelberger', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max user weight: 120 kg', 'inspection_price' => '$699 - $755'],
            ['name' => 'Sequoia SRT Saddle', 'category' => 'Harness', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max user weight: 140 kg', 'inspection_price' => '$440 - $755'],
            ['name' => 'Professional Series Basic Rope Access Harness', 'category' => 'Harness', 'brand' => 'Yates', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max user weight: 140 kg (ANSI rated to 310 lbs)', 'inspection_price' => '$693'],
            ['name' => 'Ignite Arb Harness', 'category' => 'Harness', 'brand' => 'Skylotec', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max user weight: 140 kg', 'inspection_price' => '$300 - $400'],
            ['name' => 'X-Style Featherweight Harness', 'category' => 'Harness', 'brand' => 'Buckingham', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max user weight: 310 lbs', 'inspection_price' => '$653 - $728'],

            // Descenders
            ['name' => "I'D S Descender", 'category' => 'Descender', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 150 kg (one person), 272 kg (rescue)', 'inspection_price' => '$385 - $400'],
            ['name' => 'Rig Descender', 'category' => 'Descender', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 200 kg', 'inspection_price' => '$394 - $490'],
            ['name' => 'Sirius Descender', 'category' => 'Descender', 'brand' => 'Skylotec', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 281 kg', 'inspection_price' => '$300 - $400'],
            ['name' => 'Druid Descender', 'category' => 'Descender', 'brand' => 'CAMP', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 210 kg', 'inspection_price' => '$150 - $250'],
            ['name' => 'Rope Runner Vertec', 'category' => 'Descender', 'brand' => 'Notch', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 20 kN', 'inspection_price' => '$515'],
            ['name' => 'Flow Adjustable Rope Wrench', 'category' => 'Descender', 'brand' => 'Notch', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 20 kN', 'inspection_price' => '$193'],

            // Ascenders
            ['name' => 'Ascension Hand Ascender', 'category' => 'Ascender', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$75 - $120'],
            ['name' => 'Hand Cruiser Ascender', 'category' => 'Ascender', 'brand' => 'Edelrid', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$80 - $100'],
            ['name' => 'Index Ascender', 'category' => 'Ascender', 'brand' => 'Black Diamond', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$80 - $90'],
            ['name' => 'Basic Ascender', 'category' => 'Ascender', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$78'],
            ['name' => 'Micro Traxion', 'category' => 'Ascender', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 15 kN', 'inspection_price' => '$100 - $130'],
            ['name' => 'Futura Body', 'category' => 'Ascender', 'brand' => 'Kong', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$69 - $83'],

            // Connectors / Carabiners
            ['name' => "Am'D Triact Carabiner", 'category' => 'Connector', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 27 kN', 'inspection_price' => '$31 - $33'],
            ['name' => 'Wisp Carabiner', 'category' => 'Connector', 'brand' => 'DMM', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 24 kN', 'inspection_price' => '$13'],
            ['name' => 'Small Iron Wizard Carabiner', 'category' => 'Connector', 'brand' => 'ISC', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 70 kN', 'inspection_price' => '$30'],
            ['name' => 'Photon Wire Carabiner', 'category' => 'Connector', 'brand' => 'CAMP', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 21 kN', 'inspection_price' => '$10 - $15'],
            ['name' => 'Helium 3 Carabiner', 'category' => 'Connector', 'brand' => 'Wild Country', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 24 kN', 'inspection_price' => '$15 - $20'],
            ['name' => 'RockO Carabiner', 'category' => 'Connector', 'brand' => 'Rock Exotica', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 24 kN', 'inspection_price' => '$15 - $20'],

            // Pulleys
            ['name' => 'Thirty Three Micro Pulley', 'category' => 'Pulley', 'brand' => 'Notch', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 33 kN', 'inspection_price' => '$41'],
            ['name' => 'Omni-Block SwivaBiner Oval', 'category' => 'Pulley', 'brand' => 'Rock Exotica', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 36 kN', 'inspection_price' => '$145 - $181'],
            ['name' => 'Rook Swivel Pulley', 'category' => 'Pulley', 'brand' => 'Notch', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 30 kN', 'inspection_price' => '$152'],
            ['name' => 'Micro Glide Pulley', 'category' => 'Pulley', 'brand' => 'CMI', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 30 kN', 'inspection_price' => '$65'],
            ['name' => '200 Series Rigging Pulley', 'category' => 'Pulley', 'brand' => 'ISC', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 200 kN', 'inspection_price' => '$250'],
            ['name' => 'Single Pulley', 'category' => 'Pulley', 'brand' => 'Lanex', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 30 kN', 'inspection_price' => '$24'],

            // Foot Ascenders
            ['name' => 'Pantin Click Foot Ascender', 'category' => 'Foot Ascender', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$45 - $120'],
            ['name' => 'SAKA Self Advancing Knee Ascender', 'category' => 'Foot Ascender', 'brand' => 'Climbing Innovation', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$175 - $185'],
            ['name' => 'Knee Cruiser', 'category' => 'Foot Ascender', 'brand' => 'Edelrid', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max weight: 140 kg', 'inspection_price' => '$150'],

            // Aiders / Etriers
            ['name' => 'Foot Lift', 'category' => 'Aider', 'brand' => 'Singing Rock', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Rated strength: 18 kN', 'inspection_price' => '$20 - $30'],
            ['name' => '4 Step Climbing Aider Etrier', 'category' => 'Aider', 'brand' => 'Wildken', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Pull strength: 18 kN', 'inspection_price' => '$20 - $30'],
            ['name' => '5 Step Etrier', 'category' => 'Aider', 'brand' => 'PMI', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Rated strength: 22 kN', 'inspection_price' => '$40 - $50'],

            // Mechanical Hitches / Jammers / Lanyards
            ['name' => 'Reflex Mechanical Hitch (Jammer)', 'category' => 'Mechanical Hitch', 'brand' => 'ISC', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 20 kN', 'inspection_price' => '$602'],
            ['name' => 'ZigZag Mechanical Prusik', 'category' => 'Mechanical Hitch', 'brand' => 'Petzl', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 25 kN', 'inspection_price' => '$350 - $410'],
            ['name' => 'Stileo Wirecore Lanyard (with Jammer function)', 'category' => 'Mechanical Hitch', 'brand' => 'Rope Logic', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 25 kN', 'inspection_price' => '$120 - $310'],
            ['name' => 'Custom Tree Climbing Lanyard (with Jammer)', 'category' => 'Mechanical Hitch', 'brand' => 'Rope Logic', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'MBS: 20 kN', 'inspection_price' => '$98 - $332'],

            // Rigging Plates
            ['name' => 'Rigging Plate NFPA', 'category' => 'Rigging Plate', 'brand' => 'Omega Pacific', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 36 kN', 'inspection_price' => '$50 - $70'],
            ['name' => '7 Hole Medium Rigging Plate', 'category' => 'Rigging Plate', 'brand' => 'RNR', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 45 kN', 'inspection_price' => '$60 - $80'],
            ['name' => 'TransPorter Tool Carrier (Rigging Plate)', 'category' => 'Rigging Plate', 'brand' => 'Rock Exotica', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 36 kN', 'inspection_price' => '$94 - $170'],

            // Rigging Blocks
            ['name' => 'Large Fiddle Block', 'category' => 'Rigging Block', 'brand' => 'US Rigging', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 50 kN', 'inspection_price' => '$100 - $150'],
            ['name' => 'Rigging Spring Block', 'category' => 'Rigging Block', 'brand' => 'Notch', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => 'MBS: 40 kN', 'inspection_price' => '$435'],
        ];

        foreach ($types as $type) {
            KitType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
