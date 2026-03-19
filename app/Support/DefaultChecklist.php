<?php

namespace App\Support;

class DefaultChecklist
{
    /** @return array<int, array{category: string, text: string}> */
    public static function items(): array
    {
        return [
            ['category' => 'Identification', 'text' => 'Unique identification (serial number / asset tag) clearly marked and matches records'],
            ['category' => 'Identification', 'text' => 'Safe Working Load (SWL / WLL) clearly and permanently marked'],
            ['category' => 'Identification', 'text' => 'Date of last thorough examination and next due date visible / recorded'],
            ['category' => 'General Condition', 'text' => 'No signs of damage, deformation, cracks, sharp edges or significant wear'],
            ['category' => 'General Condition', 'text' => 'No corrosion, pitting or rust that affects strength'],
            ['category' => 'General Condition', 'text' => 'No evidence of heat exposure, chemical damage or UV degradation'],
            ['category' => 'Load-bearing Parts', 'text' => 'No distortion, stretching, bending or elongation beyond manufacturer limits'],
            ['category' => 'Load-bearing Parts', 'text' => 'Welds / joints intact — no cracks or incomplete fusion'],
            ['category' => 'Fittings & Attachments', 'text' => 'Hooks, shackles, eyes, links etc. free from wear, distortion, nicks or gouges'],
            ['category' => 'Fittings & Attachments', 'text' => 'Safety catches / latches operate correctly and are secure'],
            ['category' => 'Moving Parts', 'text' => 'All moving parts operate smoothly without excessive play or binding'],
            ['category' => 'Moving Parts', 'text' => 'Locking mechanisms, springs, pins function correctly'],
            ['category' => 'Documentation', 'text' => "Manufacturer's instructions / declaration of conformity available"],
            ['category' => 'Overall', 'text' => 'Equipment appears suitable for intended use and environment'],
            ['category' => 'Overall', 'text' => 'No other defects or concerns observed'],
        ];
    }
}
