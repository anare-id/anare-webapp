<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class LocalizedContent
{
    public static function text(string $key, string $fallback): string
    {
        return Lang::has('site.'.$key) ? (string) __('site.'.$key) : $fallback;
    }

    public static function treatment(string $service, array $item): array
    {
        $key = 'treatments.'.$service.'.'.$item['slug'];
        foreach (['name', 'subtitle', 'duration', 'description'] as $field) {
            if (Lang::has('site.'.$key.'.'.$field)) $item[$field] = __('site.'.$key.'.'.$field);
        }
        return $item;
    }

    public static function doctor(int $index, array $doctor): array
    {
        if (!empty($doctor['is_dummy'])) $doctor['name'] = __('site.doctors.dummy_name', ['number' => $index]);
        $doctor['education'] = self::text('doctors.profiles.'.$index.'.education', $doctor['education'] ?? __('site.doctors.education_placeholder'));
        $doctor['expertise'] = Lang::has('site.doctors.profiles.'.$index.'.expertise')
            ? __('site.doctors.profiles.'.$index.'.expertise')
            : ($doctor['expertise'] ?? __('site.doctors.expertise_placeholder'));
        if (Lang::has('site.doctors.schedules.'.$index)) $doctor['schedule_summary'] = __('site.doctors.schedules.'.$index);
        return $doctor;
    }
}
