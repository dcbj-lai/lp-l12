@php
  use Illuminate\Support\Facades\Storage;
  $photos = is_array($consultation->photo_attachments) ? $consultation->photo_attachments : [];
@endphp
<x-layouts.app>
    <div class="p-6 max-w-6xl mx-auto text-gray-900 dark:text-white">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Clinic Consultation Details
                </h1>

                <div class="mt-3">
                    <a href="{{ route('clinic.patients.show', ['patient' => $consultation->patient_id]) }}"
                       class="inline-flex items-center gap-1 text-xl md:text-2xl font-semibold
                              text-indigo-700 hover:underline dark:text-indigo-300"
                       title="Open patient profile">
                        {{ optional($consultation->patient)->first_name }}
                        {{ optional($consultation->patient)->last_name }}
                        <span class="text-sm align-super opacity-80">↗</span>
                    </a>

                    <div class="mt-1 text-xs md:text-sm text-gray-500 dark:text-gray-300">
                        {{ optional($consultation->patient)->email ?? 'No email' }}
                    </div>

                    <div class="mt-1 text-xs md:text-sm text-gray-500 dark:text-gray-300 capitalize">
                        {{ optional($consultation->patient)->type ?? 'Unknown patient type' }}
                    </div>
                </div>
            </div>

            @php
                    $returnUrl = request('return_url');

                    $backUrl = (is_string($returnUrl) && filter_var($returnUrl, FILTER_VALIDATE_URL))
                        ? $returnUrl
                        : route('clinic.consultations.index');
                @endphp

            <div class="flex gap-2">
                <a href="{{ $backUrl }}"
                   class="bg-gray-200 text-gray-800 hover:bg-gray-300 border border-gray-200
                          dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:border-gray-700
                          rounded px-4 py-2">
                    Back
                </a>

                <a href="{{ route('clinic.consultations.edit', [
                        'consultation' => $consultation->id,
                        'return_url' => request('return_url') ?? url()->previous(),
                    ]) }}"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm transition">
                    Edit
                </a>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">CASE CLASSIFICATION</div>
                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $consultation->case_classification
                        ? \Illuminate\Support\Str::of($consultation->case_classification)->replace('_', ' ')->title()
                        : '—'
                    }}
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">TIME IN</div>
                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $consultation->time_in ? \Carbon\Carbon::parse($consultation->time_in)->format('Y-m-d h:i A') : '—' }}
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">TIME OUT</div>
                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $consultation->time_out ? \Carbon\Carbon::parse($consultation->time_out)->format('Y-m-d h:i A') : '—' }}
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">PAIN RATING</div>
                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $consultation->pain_rating !== null ? $consultation->pain_rating . ' / 10' : '—' }}
                </div>
            </div>
        </div>

        {{-- Vitals --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
                Vitals
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">BLOOD PRESSURE</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->blood_pressure ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">PULSE RATE</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->pulse_rate !== null ? $consultation->pulse_rate . ' bpm' : '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">RESPIRATORY RATE</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->respiratory_rate !== null ? $consultation->respiratory_rate : '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">TEMPERATURE</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->temperature !== null ? $consultation->temperature . ' °C' : '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">O2 SATURATION</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->o2_saturation !== null ? $consultation->o2_saturation . '%' : '—' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Main clinical details --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden mt-6 bg-white dark:bg-gray-900">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
                Clinical Information
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 md:col-span-2 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">CHIEF COMPLAINT</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-line">
                        {{ $consultation->chief_complaint ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 md:col-span-2 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">ASSESSMENT</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-line">
                        {{ $consultation->assessment ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 md:col-span-2 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">TREATMENT</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-line">
                        {{ $consultation->treatment ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 md:col-span-2 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">REMARKS</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-line">
                        {{ $consultation->remarks ?? '—' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Medicine and supplies --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden mt-6 bg-white dark:bg-gray-900">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
                Medicine and Supplies
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">MEDICINE GIVEN</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->medicine_given ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">QTY. MEDICINE</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->medicine_qty ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">SUPPLIES USED</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->supplies_used ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">QTY. SUPPLIES</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->supplies_qty ?? '—' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Photo attachments --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden mt-6 bg-white dark:bg-gray-900">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
                Photo Attachments
            </div>

            <div class="p-4">
                @php
                    $photos = $consultation->photo_attachments;

                    if (is_string($photos)) {
                        $decoded = json_decode($photos, true);
                        $photos = is_array($decoded) ? $decoded : [];
                    }

                    $photos = is_array($photos) ? $photos : [];
                @endphp

                @if(count($photos) === 0)
                    <div class="text-sm text-gray-500 dark:text-gray-300">
                        No photo attachments uploaded.
                    </div>
                @else
                   
                      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($photos as $photo)
                        @php
                            $url = Storage::disk('private_s3')->temporaryUrl($photo, now()->addMinutes(30));
                        @endphp

                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                            class="block border border-gray-200 dark:border-gray-700 rounded overflow-hidden">
                            <div class="aspect-square bg-gray-100 dark:bg-gray-800">
                            <img src="{{ $url }}" class="w-full h-full object-cover" alt="Consultation photo">
                            </div>
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Student-only after consultation --}}
        @if(optional($consultation->patient)->type === 'student')
            <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden mt-6 bg-white dark:bg-gray-900">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
                    After Consultation
                </div>

                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">CURRENT TEACHER</div>
                        <div class="mt-1 text-gray-900 dark:text-white">
                            {{ $consultation->current_teacher ?? '—' }}
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">TEACHER EMAIL</div>
                        <div class="mt-1 text-gray-900 dark:text-white break-all">
                            {{ $consultation->teacher_email ?? '—' }}
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">DECISION</div>
                        <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                            {{ $consultation->after_consultation
                                ? \Illuminate\Support\Str::of($consultation->after_consultation)->replace('_', ' ')->title()
                                : '—'
                            }}
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">GOING HOME METHOD</div>
                        <div class="mt-1 text-gray-900 dark:text-white">
                            {{ $consultation->going_home_method
                                ? \Illuminate\Support\Str::of($consultation->going_home_method)->replace('_', ' ')->title()
                                : '—'
                            }}
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">FETCHER NAME</div>
                        <div class="mt-1 text-gray-900 dark:text-white">
                            {{ $consultation->fetcher_name ?? '—' }}
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">APPROVED BY</div>
                        <div class="mt-1 text-gray-900 dark:text-white">
                            {{ $consultation->self_approved_by ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>