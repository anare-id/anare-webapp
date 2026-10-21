<dialog id="doctor-modal-{{ $doctorIndex }}" class="doctor-profile-modal" data-doctor-modal aria-labelledby="doctor-modal-title-{{ $doctorIndex }}" aria-modal="true">
    <h2 id="doctor-modal-title-{{ $doctorIndex }}" class="sr-only">{{ $doctor['name'] }}</h2>
    <button class="doctor-modal-close" type="button" data-doctor-modal-close aria-label="{{ __('site.common.close_profile', ['name' => $doctor['name']]) }}" autofocus><span aria-hidden="true">×</span></button>
    <div class="doctor-modal-scroll" tabindex="0" role="region" aria-label="{{ __('site.doctors.information_schedule', ['name' => $doctor['name']]) }}">
        <div class="doctor-modal-grid">
            <aside class="doctor-modal-identity" aria-label="{{ __('site.doctors.photo_schedule') }}">
                @if(!empty($doctor['schedule_image']))
                    <img class="doctor-schedule-image" src="{{ asset($doctor['schedule_image']) }}" alt="{{ __('site.doctors.schedule_alt', ['name' => $doctor['name'], 'schedule' => $doctor['schedule_summary']]) }}" @if($doctor['schedule_width']) width="{{ $doctor['schedule_width'] }}" @endif @if($doctor['schedule_height']) height="{{ $doctor['schedule_height'] }}" @endif loading="lazy" decoding="async">
                    @if(app()->getLocale() === 'en')
                        <p class="doctor-schedule-translation" lang="en">{{ $doctor['schedule_summary'] }}</p>
                    @endif
                @else
                    <div class="doctor-modal-dummy">
                        @if($doctor['profile_image'])<img src="{{ asset($doctor['profile_image']) }}" alt="{{ $doctor['name'] }}" width="1148" height="1371" loading="lazy">@endif
                        <h3>{{ $doctor['name'] }}</h3>
                        <div class="doctor-dummy-schedule">
                            <h4>{{ __('site.doctors.schedule') }}</h4>
                            <p>{{ $doctor['schedule_summary'] }}</p>
                        </div>
                    </div>
                @endif
            </aside>
            <div class="doctor-modal-information">
                <section>
                    <h3>{{ $doctor['specialty'] }}</h3>
                    <p class="article-content">{{ $doctor['description'] }}</p>
                    @if($doctor['schedule_summary'])<p class="article-content">{{ $doctor['schedule_summary'] }}</p>@endif
                </section>
                <section class="doctor-modal-education" aria-labelledby="doctor-education-{{ $doctorIndex }}">
                    <h3 id="doctor-education-{{ $doctorIndex }}">{{ __('site.doctors.education') }}</h3>
                    <p>{{ $doctor['education'] }}</p>
                </section>
                <section class="doctor-modal-expertise" aria-labelledby="doctor-expertise-{{ $doctorIndex }}">
                    <h3 id="doctor-expertise-{{ $doctorIndex }}">{{ __('site.doctors.expertise') }}</h3>
                    <div class="doctor-expertise-grid">
                        @foreach($doctor['expertise'] as $expertise)
                            <p>{{ $expertise }}</p>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</dialog>
