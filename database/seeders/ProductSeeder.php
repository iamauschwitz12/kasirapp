<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Seed the products table from products.sql
     */
    public function run(): void
    {
        $sqlPath = base_path('products.sql');

        if (!file_exists($sqlPath)) {
            $this->command->error('File products.sql tidak ditemukan di root project!');
            return;
        }

        $sql = file_get_contents($sqlPath);

        // Extract only INSERT statements from the SQL dump
        preg_match_all('/INSERT INTO.*?;/s', $sql, $matches);

        if (!empty($matches[0])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            foreach ($matches[0] as $insertStatement) {
                DB::unprepared($insertStatement);
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->command->info('Products berhasil di-seed dari products.sql!');
        } else {
            $this->command->warn('Tidak ada INSERT statement ditemukan di products.sql');
        }
    }
}
