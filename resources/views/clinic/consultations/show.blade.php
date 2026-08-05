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

    {{-- Medicine and Supplies --}}
<div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden mt-6 bg-white dark:bg-gray-900">
    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
        Medicine and Supplies
    </div>

    @php
        /*
            Supports both formats:

            New format from create blade:
            medicines = [
                ['name' => 'Paracetamol', 'qty' => 2, 'label' => '500mg'],
            ]

            supplies = [
                ['name' => 'Bandage', 'qty' => 1],
            ]

            Old fallback format:
            medicine_given, medicine_qty, supplies_used, supplies_qty
        */

        $medicines = $consultation->medicines ?? [];
        $supplies = $consultation->supplies ?? [];

        if (is_string($medicines)) {
            $decodedMedicines = json_decode($medicines, true);
            $medicines = is_array($decodedMedicines) ? $decodedMedicines : [];
        }

        if ($medicines instanceof \Illuminate\Support\Collection) {
            $medicines = $medicines->toArray();
        }

        if (is_string($supplies)) {
            $decodedSupplies = json_decode($supplies, true);
            $supplies = is_array($decodedSupplies) ? $decodedSupplies : [];
        }

        if ($supplies instanceof \Illuminate\Support\Collection) {
            $supplies = $supplies->toArray();
        }

        if (empty($medicines) && !empty($consultation->medicine_given)) {
            $medicines = [
                [
                    'name' => $consultation->medicine_given,
                    'qty' => $consultation->medicine_qty,
                    'label' => null,
                ],
            ];
        }

        if (empty($supplies) && !empty($consultation->supplies_used)) {
            $supplies = [
                [
                    'name' => $consultation->supplies_used,
                    'qty' => $consultation->supplies_qty,
                ],
            ];
        }
    @endphp

    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Medicines --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
            <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300 mb-3">
                MEDICINES GIVEN
            </div>

            @if (!empty($medicines))
                <div class="space-y-2">
                    @foreach ($medicines as $medicine)
                        @php
                            $medicineName = data_get($medicine, 'name') ?? data_get($medicine, 'medicine_name');
                            $medicineQty = data_get($medicine, 'qty') ?? data_get($medicine, 'quantity');
                            $medicineLabel = data_get($medicine, 'label') ?? data_get($medicine, 'equivalent');
                        @endphp

                        <div class="flex flex-col gap-1 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $medicineName ?? '—' }}
                            </div>

                            <div class="text-xs text-gray-600 dark:text-gray-300">
                                Qty: {{ $medicineQty ?? '—' }}

                                @if (!empty($medicineLabel))
                                    <span class="mx-1">|</span>
                                    {{ $medicineLabel }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    —
                </div>
            @endif
        </div>

        {{-- Supplies --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
            <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300 mb-3">
                SUPPLIES USED
            </div>

            @if (!empty($supplies))
                <div class="space-y-2">
                    @foreach ($supplies as $supply)
                        @php
                            $supplyName = data_get($supply, 'name') ?? data_get($supply, 'supply_name');
                            $supplyQty = data_get($supply, 'qty') ?? data_get($supply, 'quantity');
                        @endphp

                        <div class="flex flex-col gap-1 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $supplyName ?? '—' }}
                            </div>

                            <div class="text-xs text-gray-600 dark:text-gray-300">
                                Qty: {{ $supplyQty ?? '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    —
                </div>
            @endif
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
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">CHECK-IN TEACHER</div>
                        <div class="mt-1 text-gray-900 dark:text-white">
                            {{ $consultation->check_in_teacher ?: 'No Teacher Assigned' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-300 break-all">
                            {{ $consultation->check_in_teacher_email ?: '—' }}
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">CHECK-OUT TEACHER</div>
                        <div class="mt-1 text-gray-900 dark:text-white">
                            {{ $consultation->current_teacher ?: 'No Teacher Assigned' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-300 break-all">
                            {{ $consultation->teacher_email ?: '—' }}
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
                    <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">EMAIL NOTIFICATION</div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @if ($consultation->email_status === \App\Models\ClinicConsultation::EMAIL_STATUS_QUEUED)
                                <span class="inline-flex rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">Queued</span>
                            @elseif ($consultation->email_status === \App\Models\ClinicConsultation::EMAIL_STATUS_SENT)
                                <span class="inline-flex rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">Sent</span>
                                @if ($consultation->email_sent_at)
                                    <span class="text-xs text-gray-500 dark:text-gray-300">{{ $consultation->email_sent_at->format('M d, Y h:i A') }}</span>
                                @endif
                            @elseif ($consultation->email_status === \App\Models\ClinicConsultation::EMAIL_STATUS_FAILED)
                                <span class="inline-flex rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300">Failed</span>
                                <form method="POST" action="{{ route('clinic.consultations.email.retry', $consultation) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Retry email</button>
                                </form>
                            @else
                                <span class="text-gray-500 dark:text-gray-300">&mdash;</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
