<dialog id="doctor-modal-{{ $doctorIndex }}" class="doctor-profile-modal" data-doctor-modal aria-labelledby="doctor-modal-title-{{ $doctorIndex }}" aria-modal="true">
    <h2 id="doctor-modal-title-{{ $doctorIndex }}" class="sr-only">{{ $doctor['name'] }}</h2>
    <button class="doctor-modal-close" type="button" data-doctor-modal-close aria-label="{{ __('site.common.close_profile', ['name' => $doctor['name']]) }}" autofocus><span aria-hidden="true">×</span></button>
    <div class="doctor-modal-scroll" tabindex="0" role="region" aria-label="{{ __('site.doctors.information_schedule', ['name' => $doctor['name']]) }}">
        <div class="doctor-modal-grid">
            <aside class="doctor-modal-identity" aria-label="{{ __('site.doctors.photo_schedule') }}">
                @if(!empty($doctor['schedule_image']))
                    <img class="doctor-schedule-image" src="{{ asset($doctor['schedule_image']) }}" alt="{{ $doctor['schedule_image_alt'] ?? '' ?: __('site.doctors.schedule_alt', ['name' => $doctor['name'], 'schedule' => $doctor['schedule_summary']]) }}" @if($doctor['schedule_width']) width="{{ $doctor['schedule_width'] }}" @endif @if($doctor['schedule_height']) height="{{ $doctor['schedule_height'] }}" @endif loading="lazy" decoding="async">
                @else
                    <div class="doctor-modal-dummy">
                        @if($doctor['profile_image'])<img src="{{ asset($doctor['profile_image']) }}" alt="{{ $doctor['image_alt'] ?? '' ?: $doctor['name'] }}" width="1148" height="1371" loading="lazy">@endif
                    </div>
                @endif
            </aside>
            <div class="doctor-modal-information">
                <section class="doctor-modal-education" aria-labelledby="doctor-education-{{ $doctorIndex }}">
                    <h3 id="doctor-education-{{ $doctorIndex }}">{{ __('site.doctors.education') }}</h3>
                    <div class="rich-content">{!! $doctor['education_html'] !!}</div>
                </section>
                <section class="doctor-modal-schedule"><h3>{{ __('site.doctors.schedule') }}</h3><div class="rich-content">{!! $doctor['schedule_html'] !!}</div></section>
                <section class="doctor-modal-expertise" aria-labelledby="doctor-expertise-{{ $doctorIndex }}">
                    <h3 id="doctor-expertise-{{ $doctorIndex }}">{{ __('site.doctors.expertise') }}</h3>
                    <div class="doctor-expertise-content rich-content">{!! $doctor['expertise_html'] !!}</div>
                </section>
            </div>
        </div>
    </div>
</dialog>
