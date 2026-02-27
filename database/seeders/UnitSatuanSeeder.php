<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSatuanSeeder extends Seeder
{
    /**
     * Seed the unit_satuans table from unit_satuans.sql
     */
    public function run(): void
    {
        $sqlPath = base_path('unit_satuans.sql');

        if (!file_exists($sqlPath)) {
            $this->command->error('File unit_satuans.sql tidak ditemukan di root project!');
            return;
        }

        $sql = file_get_contents($sqlPath);

        // Extract only INSERT statements from the SQL dump
        preg_match_all('/INSERT INTO.*?;/s', $sql, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $insertStatement) {
                DB::unprepared($insertStatement);
            }
            $this->command->info('Unit Satuans berhasil di-seed dari unit_satuans.sql!');
        } else {
            $this->command->warn('Tidak ada INSERT statement ditemukan di unit_satuans.sql');
        }
    }
}
