<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $mapping = [
        1 => ['naziv' => 'Procesori',          'slug' => 'cpu',           'redoslijed' => 1, 'ikona' => 'bi-cpu',        'obavezan' => true],
        2 => ['naziv' => 'Matične ploče',       'slug' => 'maticna-ploca', 'redoslijed' => 2, 'ikona' => 'bi-motherboard','obavezan' => true],
        3 => ['naziv' => 'RAM memorija',        'slug' => 'ram',           'redoslijed' => 3, 'ikona' => 'bi-memory',     'obavezan' => true],
        4 => ['naziv' => 'Grafičke kartice',    'slug' => 'gpu',           'redoslijed' => 4, 'ikona' => 'bi-gpu-card',   'obavezan' => false],
        5 => ['naziv' => 'SSD i HDD',           'slug' => 'storage',       'redoslijed' => 5, 'ikona' => 'bi-device-hdd', 'obavezan' => true],
        6 => ['naziv' => 'Napajanja',           'slug' => 'napajanje',     'redoslijed' => 6, 'ikona' => 'bi-lightning',  'obavezan' => true],
        7 => ['naziv' => 'Kućišta',             'slug' => 'kuciste',       'redoslijed' => 7, 'ikona' => 'bi-pc-display', 'obavezan' => true],
    ];

    public function up(): void
    {
        // Pripremi mapping
        $idMap = [];
        foreach ($this->mapping as $oldId => $data) {
            $tipId = DB::table('tip_proizvoda')
                ->where('naziv_tip', $data['naziv'])
                ->value('id_tip');
            if ($tipId) $idMap[$oldId] = $tipId;
        }

        // 1. Dodaj nove stupce u tip_proizvoda (skip ako vec postoje)
        if (!Schema::hasColumn('tip_proizvoda', 'konfigurator')) {
            Schema::table('tip_proizvoda', function (Blueprint $table) {
                $table->boolean('konfigurator')->default(false)->after('kategorija_id');
                $table->string('slug', 50)->nullable()->unique()->after('konfigurator');
                $table->integer('redoslijed')->default(0)->after('slug');
                $table->string('ikona', 50)->nullable()->after('redoslijed');
                $table->boolean('obavezan')->default(true)->after('ikona');
                $table->timestamps();
            });
        }

        // 2. Popuni konfigurator podatke (idempotentno)
        foreach ($this->mapping as $oldId => $data) {
            if (isset($idMap[$oldId])) {
                DB::table('tip_proizvoda')->where('id_tip', $idMap[$oldId])->update([
                    'konfigurator' => true,
                    'slug'         => $data['slug'],
                    'redoslijed'   => $data['redoslijed'],
                    'ikona'        => $data['ikona'],
                    'obavezan'     => $data['obavezan'],
                    'updated_at'   => now(),
                ]);
            }
        }

        // 3. Preimenuj tip_id -> tip_proizvoda_id u proizvod
        if (Schema::hasColumn('proizvod', 'tip_id')) {
            Schema::table('proizvod', function (Blueprint $table) {
                $table->renameColumn('tip_id', 'tip_proizvoda_id');
            });
        }

        // 4. pc_component_specs: ažuriraj i preimenuj component_type_id -> tip_proizvoda_id
        if (Schema::hasColumn('pc_component_specs', 'component_type_id')) {
            // Drop unique index
            try {
                Schema::table('pc_component_specs', function (Blueprint $table) {
                    $table->dropUnique('pc_component_specs_proizvod_id_component_type_id_unique');
                });
            } catch (\Exception $e) { /* index may not exist */ }

            // Ažuriraj ID-ove (reverse order da se izbjegne kaskadni problem: 3→7 pa 7→57)
            foreach (array_reverse($idMap, true) as $oldId => $newId) {
                DB::table('pc_component_specs')
                    ->where('component_type_id', $oldId)
                    ->update(['component_type_id' => $newId]);
            }

            Schema::table('pc_component_specs', function (Blueprint $table) {
                $table->integer('component_type_id')->change();
            });
            Schema::table('pc_component_specs', function (Blueprint $table) {
                $table->renameColumn('component_type_id', 'tip_proizvoda_id');
            });
            Schema::table('pc_component_specs', function (Blueprint $table) {
                $table->unique(['proizvod_id', 'tip_proizvoda_id'], 'pc_component_specs_proizvod_tip_unique');
            });
        }

        // 5. pc_configuration_items: isto
        if (Schema::hasColumn('pc_configuration_items', 'component_type_id')) {
            try {
                Schema::table('pc_configuration_items', function (Blueprint $table) {
                    $table->dropUnique('pc_configuration_items_configuration_id_component_type_id_unique');
                });
            } catch (\Exception $e) { /* index may not exist */ }

            foreach (array_reverse($idMap, true) as $oldId => $newId) {
                DB::table('pc_configuration_items')
                    ->where('component_type_id', $oldId)
                    ->update(['component_type_id' => $newId]);
            }

            Schema::table('pc_configuration_items', function (Blueprint $table) {
                $table->integer('component_type_id')->change();
            });
            Schema::table('pc_configuration_items', function (Blueprint $table) {
                $table->renameColumn('component_type_id', 'tip_proizvoda_id');
            });
            Schema::table('pc_configuration_items', function (Blueprint $table) {
                $table->unique(['configuration_id', 'tip_proizvoda_id'], 'pc_config_items_config_tip_unique');
            });
        }

        // 6. Obriši pc_component_types
        Schema::dropIfExists('pc_component_types');
    }

    public function down(): void
    {
        // Recreate pc_component_types
        Schema::create('pc_component_types', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 100);
            $table->string('slug', 50)->unique();
            $table->integer('redoslijed')->default(0);
            $table->string('ikona', 50)->nullable();
            $table->boolean('obavezan')->default(true);
            $table->timestamps();
        });

        $reverseMap = [];
        $singleNames = [
            'Procesori' => 'Procesor', 'Matične ploče' => 'Matična ploča',
            'Grafičke kartice' => 'Grafička kartica', 'SSD i HDD' => 'SSD/HDD',
            'Napajanja' => 'Napajanje', 'Kućišta' => 'Kućište',
        ];
        foreach ($this->mapping as $oldId => $data) {
            DB::table('pc_component_types')->insert([
                'id' => $oldId, 'naziv' => $singleNames[$data['naziv']] ?? $data['naziv'],
                'slug' => $data['slug'], 'redoslijed' => $data['redoslijed'],
                'ikona' => $data['ikona'], 'obavezan' => $data['obavezan'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $tipId = DB::table('tip_proizvoda')->where('naziv_tip', $data['naziv'])->value('id_tip');
            if ($tipId) $reverseMap[$tipId] = $oldId;
        }

        // Reverse pc_configuration_items
        Schema::table('pc_configuration_items', function (Blueprint $t) { $t->dropUnique('pc_config_items_config_tip_unique'); });
        Schema::table('pc_configuration_items', function (Blueprint $t) { $t->renameColumn('tip_proizvoda_id', 'component_type_id'); });
        Schema::table('pc_configuration_items', function (Blueprint $t) { $t->bigInteger('component_type_id')->unsigned()->change(); });
        foreach ($reverseMap as $tipId => $oldId) {
            DB::table('pc_configuration_items')->where('component_type_id', $tipId)->update(['component_type_id' => $oldId]);
        }
        Schema::table('pc_configuration_items', function (Blueprint $t) { $t->unique(['configuration_id', 'component_type_id'], 'pc_configuration_items_configuration_id_component_type_id_unique'); });

        // Reverse pc_component_specs
        Schema::table('pc_component_specs', function (Blueprint $t) { $t->dropUnique('pc_component_specs_proizvod_tip_unique'); });
        Schema::table('pc_component_specs', function (Blueprint $t) { $t->renameColumn('tip_proizvoda_id', 'component_type_id'); });
        Schema::table('pc_component_specs', function (Blueprint $t) { $t->bigInteger('component_type_id')->unsigned()->change(); });
        foreach ($reverseMap as $tipId => $oldId) {
            DB::table('pc_component_specs')->where('component_type_id', $tipId)->update(['component_type_id' => $oldId]);
        }
        Schema::table('pc_component_specs', function (Blueprint $t) { $t->unique(['proizvod_id', 'component_type_id'], 'pc_component_specs_proizvod_id_component_type_id_unique'); });

        // Reverse proizvod rename
        Schema::table('proizvod', function (Blueprint $t) { $t->renameColumn('tip_proizvoda_id', 'tip_id'); });

        // Remove new columns
        Schema::table('tip_proizvoda', function (Blueprint $t) {
            $t->dropUnique(['slug']);
            $t->dropColumn(['konfigurator', 'slug', 'redoslijed', 'ikona', 'obavezan', 'created_at', 'updated_at']);
        });
    }
};
