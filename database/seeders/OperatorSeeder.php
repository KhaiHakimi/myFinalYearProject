<?php

namespace Database\Seeders;

use App\Models\Ferry;
use App\Models\Operator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        // Get every unique operator name from the ferries table
        $operatorNames = Ferry::select('operator')
            ->distinct()
            ->whereNotNull('operator')
            ->pluck('operator');

        foreach ($operatorNames as $name) {
            if (empty($name)) {
                continue;
            }

            // Build a short uppercase code from the operator name
            // e.g. "Langkawi Ferry Line" => "LFL"
            $words = preg_split('/\s+/', trim($name));
            $code  = strtoupper(
                collect($words)
                    ->map(fn ($w) => Str::substr($w, 0, 1))
                    ->implode('')
            );

            // Ensure uniqueness by appending a number if the code already exists
            $baseCode  = $code;
            $attempt   = 1;
            while (Operator::where('code', $code)->exists()) {
                $code = $baseCode . $attempt;
                $attempt++;
            }

            // Create the operator record
            $operator = Operator::create([
                'name'         => $name,
                'code'         => $code,
                'sync_enabled' => false,
            ]);

            // Link all ferries with this operator name to the new operator record
            Ferry::where('operator', $name)->update([
                'operator_id' => $operator->id,
            ]);

            $linkedCount = Ferry::where('operator_id', $operator->id)->count();
            $this->command?->info("Created operator [{$code}] {$name} — linked {$linkedCount} ferries");
        }

        $this->command?->info('Done. ' . Operator::count() . ' operators created.');
    }
}
