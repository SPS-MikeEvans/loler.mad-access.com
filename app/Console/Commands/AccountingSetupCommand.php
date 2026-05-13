<?php

namespace App\Console\Commands;

use App\Models\BusinessSetting;
use App\Models\ExpenseCategory;
use Illuminate\Console\Command;

class AccountingSetupCommand extends Command
{
    protected $signature = 'accounting:setup';

    protected $description = 'Seed the business settings singleton and default expense categories. Safe to re-run.';

    public function handle(): int
    {
        $settings = BusinessSetting::current();
        $this->info("Business settings ready (id={$settings->id}).");

        $defaults = ['Fuel', 'Tools', 'Subsistence', 'Office', 'Software', 'Other'];

        foreach ($defaults as $name) {
            $created = ExpenseCategory::withTrashed()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );

            $line = $created->wasRecentlyCreated ? "  + {$name}" : "  · {$name} (exists)";
            $this->line($line);
        }

        $this->info('Accounting setup complete.');

        return self::SUCCESS;
    }
}
