<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'warranty_duration')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('warranty_duration', 10)
                    ->nullable()
                    ->default('30d')
                    ->after('status')
                    ->comment('none|30d|3m|6m|1y — durée de garantie choisie à la vente');
            });
        }

        if (! Schema::hasColumn('sales', 'warranty_end_date')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->date('warranty_end_date')->nullable()->after('warranty_duration');
            });
        }

        // Comportement historique : avant ce champ, toutes les ventes validées
        // étaient implicitement couvertes par une garantie de 30 jours.
        // On rend ce comportement explicite pour les ventes déjà existantes
        // au lieu de les faire apparaître sans garantie.
        DB::table('sales')
            ->where('status', 'validated')
            ->whereNull('warranty_end_date')
            ->orderBy('id')
            ->chunkById(200, function ($sales) {
                foreach ($sales as $sale) {
                    if (empty($sale->sale_date)) {
                        continue;
                    }

                    DB::table('sales')->where('id', $sale->id)->update([
                        'warranty_duration' => '30d',
                        'warranty_end_date' => \Carbon\Carbon::parse($sale->sale_date)->addDays(30)->toDateString(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'warranty_end_date')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('warranty_end_date');
            });
        }

        if (Schema::hasColumn('sales', 'warranty_duration')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('warranty_duration');
            });
        }
    }
};
