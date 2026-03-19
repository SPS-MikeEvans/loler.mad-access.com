<?php

namespace Database\Seeders;

use App\Models\KitType;
use Illuminate\Database\Seeder;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Rope' => [
                'instructions' => "Inspect the full length of the rope in good lighting. Run the rope through your hands slowly, feeling for core damage, sheath slippage, or stiff spots.\n\nRemove from service immediately if any check fails.",
                'resources_links' => json_encode([
                    ['name' => 'IRATA Rope Inspection Guide', 'url' => 'https://irata.org'],
                    ['name' => 'EN 1891 Standard Overview', 'url' => 'https://www.en-standard.eu'],
                ]),
                'checklist_json' => json_encode([
                    ['category' => 'Visual', 'text' => 'Sheath intact — no cuts, abrasion, or melting'],
                    ['category' => 'Visual', 'text' => 'Sheath not slipping on core'],
                    ['category' => 'Visual', 'text' => 'No discolouration suggesting chemical contamination'],
                    ['category' => 'Visual', 'text' => 'End terminations and whippings secure'],
                    ['category' => 'Tactile', 'text' => 'No flat spots or stiff sections indicating core damage'],
                    ['category' => 'Tactile', 'text' => 'No soft spots (core fibres broken)'],
                    ['category' => 'Functional', 'text' => 'Rope identification label legible and present'],
                    ['category' => 'Functional', 'text' => 'Date of manufacture within service life'],
                ]),
            ],
            'Harness' => [
                'instructions' => "Lay the harness flat and inspect each webbing tape, buckle, and stitching panel systematically. Pay close attention to load-bearing loops and attachment points.",
                'resources_links' => json_encode([
                    ['name' => 'Petzl Harness Inspection Guide', 'url' => 'https://www.petzl.com'],
                    ['name' => 'EN 361 Standard', 'url' => 'https://www.en-standard.eu'],
                ]),
                'checklist_json' => json_encode([
                    ['category' => 'Visual', 'text' => 'Webbing — no cuts, abrasion, burns, or chemical damage'],
                    ['category' => 'Visual', 'text' => 'Stitching panels intact — no broken or loose stitches'],
                    ['category' => 'Visual', 'text' => 'Attachment points (front D, rear D) undamaged and undeformed'],
                    ['category' => 'Visual', 'text' => 'Buckles undamaged — no cracks, corrosion, or deformation'],
                    ['category' => 'Visual', 'text' => 'No discolouration or stiffness from chemical exposure'],
                    ['category' => 'Functional', 'text' => 'All buckles adjust and lock correctly'],
                    ['category' => 'Functional', 'text' => 'Labels legible — CE mark, manufacturer, batch/serial, date'],
                    ['category' => 'Functional', 'text' => 'Fall-indicator tags not deployed'],
                ]),
            ],
            'Connector' => [
                'instructions' => "Inspect connectors (karabiners, maillon rapides, shackles) for deformation, corrosion, and gate function. Check sleeve/locking mechanism thoroughly.",
                'resources_links' => json_encode([
                    ['name' => 'EN 362 Connector Standard', 'url' => 'https://www.en-standard.eu'],
                    ['name' => 'Petzl Connector Care', 'url' => 'https://www.petzl.com'],
                ]),
                'checklist_json' => json_encode([
                    ['category' => 'Visual', 'text' => 'Body — no cracks, gouges, or deformation'],
                    ['category' => 'Visual', 'text' => 'No significant corrosion or pitting'],
                    ['category' => 'Visual', 'text' => 'Gate — no bending, cracking, or excessive wear at hinge/nose'],
                    ['category' => 'Visual', 'text' => 'Locking sleeve undamaged and thread intact'],
                    ['category' => 'Functional', 'text' => 'Gate opens and closes smoothly under spring pressure'],
                    ['category' => 'Functional', 'text' => 'Locking sleeve engages fully and locks gate closed'],
                    ['category' => 'Functional', 'text' => 'Markings legible — CE, kN rating, manufacturer'],
                ]),
            ],
            'Descender' => [
                'instructions' => "Inspect descenders and belay devices for wear, deformation, and smooth operation. Check rope channel wear carefully.",
                'resources_links' => json_encode([
                    ['name' => 'EN 341 Descender Standard', 'url' => 'https://www.en-standard.eu'],
                    ['name' => 'Petzl Descender Inspection', 'url' => 'https://www.petzl.com'],
                ]),
                'checklist_json' => json_encode([
                    ['category' => 'Visual', 'text' => 'Body — no cracks, deformation, or excessive wear'],
                    ['category' => 'Visual', 'text' => 'Rope channel/groove — no sharp edges or excessive grooving'],
                    ['category' => 'Visual', 'text' => 'No corrosion or damage to moving parts'],
                    ['category' => 'Visual', 'text' => 'Friction plates/cam undamaged if applicable'],
                    ['category' => 'Functional', 'text' => 'Moving parts operate freely without sticking'],
                    ['category' => 'Functional', 'text' => 'Anti-panic handle functions correctly if applicable'],
                    ['category' => 'Functional', 'text' => 'Markings legible — CE, manufacturer, rope diameter range'],
                ]),
            ],
            'Ascender' => [
                'instructions' => "Inspect ascenders (handled and chest) for cam function, shell integrity, and trigger mechanism. Test on a rope before committing to use.",
                'resources_links' => json_encode([
                    ['name' => 'EN 567 Ascender Standard', 'url' => 'https://www.en-standard.eu'],
                    ['name' => 'Petzl Ascender Guide', 'url' => 'https://www.petzl.com'],
                ]),
                'checklist_json' => json_encode([
                    ['category' => 'Visual', 'text' => 'Shell — no cracks, deformation, or sharp edges'],
                    ['category' => 'Visual', 'text' => 'Cam teeth sharp and undamaged (not worn smooth)'],
                    ['category' => 'Visual', 'text' => 'Trigger/locking lever undamaged and spring-loaded correctly'],
                    ['category' => 'Visual', 'text' => 'No corrosion on pivot pin or cam axle'],
                    ['category' => 'Functional', 'text' => 'Cam grips rope firmly when loaded'],
                    ['category' => 'Functional', 'text' => 'Cam releases and slides freely when unloaded'],
                    ['category' => 'Functional', 'text' => 'Markings legible — CE, rope diameter range, manufacturer'],
                ]),
            ],
        ];

        foreach ($data as $category => $fields) {
            KitType::where('category', $category)->update([
                'instructions' => $fields['instructions'],
                'resources_links' => $fields['resources_links'],
                'checklist_json' => $fields['checklist_json'],
            ]);
        }
    }
}
