<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (DB::table('site_settings')->whereIn('group', ['contact', 'social'])->get() as $record) {
                $values = json_decode($record->values, true, 512, JSON_THROW_ON_ERROR);
                if ($record->group === 'contact') {
                    $values['map_embed'] = 'https://maps.google.com/maps?cid=3421534276469252101&ll=-7.3234692%2C112.776346&z=17&output=embed';
                } else {
                    foreach ($values as $key => $url) {
                        $handle = is_string($url) ? trim((string) parse_url($url, PHP_URL_PATH), '/@') : '';
                        if (strcasecmp($handle, 'anareskin.id') === 0 || ($key === 'skin' && empty($url))) unset($values[$key]);
                    }
                }
                DB::table('site_settings')->where('id', $record->id)->update([
                    'values' => json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Keep the corrected location and do not restore a nonexistent social account.
    }
};
