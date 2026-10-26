<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $record = DB::table('site_settings')->where('group', 'contact')->first();
        if ($record === null) return;
        $values = json_decode($record->values, true, 512, JSON_THROW_ON_ERROR);
        if (array_key_exists('location_image', $values)) return;
        $values['location_image'] = '/images/anare-clinic-location.webp';
        DB::table('site_settings')->where('id', $record->id)->update(['values' => json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
    }

    public function down(): void
    {
        $record = DB::table('site_settings')->where('group', 'contact')->first();
        if ($record === null) return;
        $values = json_decode($record->values, true, 512, JSON_THROW_ON_ERROR);
        if (($values['location_image'] ?? null) !== '/images/anare-clinic-location.webp') return;
        unset($values['location_image']);
        DB::table('site_settings')->where('id', $record->id)->update(['values' => json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
    }
};
