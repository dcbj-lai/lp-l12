<x-layouts.app>
    <div class="p-6 max-w-5xl mx-auto text-gray-900 dark:text-white">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Consultation Details</h1>

                {{-- Student block --}}
                <div class="mt-3">
                    <a href="{{ route('guidance.clients.show', ['client' => $consultation->client_id]) }}"
                       class="inline-flex items-center gap-1 text-xl md:text-2xl font-semibold
                              text-indigo-700 hover:underline dark:text-indigo-300"
                       title="Open student profile">
                        {{ optional($consultation->client)->first_name }}
                        {{ optional($consultation->client)->last_name }}
                        <span class="text-sm align-super opacity-80">↗</span>
                    </a>

                    <div class="mt-1 text-xs md:text-sm text-gray-500 dark:text-gray-300">
                        {{ optional($consultation->client)->email ?? 'No email' }}
                    </div>
                </div>
            </div>

            @php
                $returnUrl = request('return_url');

                $backUrl = (is_string($returnUrl) && filter_var($returnUrl, FILTER_VALIDATE_URL))
                    ? $returnUrl
                    : route('guidance.consultations.index');
            @endphp

            <div class="flex gap-2">
                <a href="{{ $backUrl }}"
                   class="bg-gray-200 text-gray-800 hover:bg-gray-300 border border-gray-200
                          dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:border-gray-700
                          rounded px-4 py-2">
                    Back
                </a>

                <a href="{{ route('guidance.consultations.edit', [
                        'consultation' => $consultation->id,
                        'return_url' => request('return_url')
                    ]) }}"
                   class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm transition">
                    Edit
                </a>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">CHECK-IN TEACHER</div>
                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $consultation->check_in_teacher ?: 'No Teacher Assigned' }}
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                    {{ $consultation->check_in_teacher_email ?: '—' }}
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">CHECK-OUT TEACHER</div>
                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $consultation->current_teacher ?: 'No Teacher Assigned' }}
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                    {{ $consultation->teacher_email ?: '—' }}
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
        </div>

        {{-- Main details --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
                Session Information
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">TYPE OF SESSION</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->type_of_session ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">RISK ASSESSMENT</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        {{ $consultation->risk_assessment ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 md:col-span-2 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">ISSUE / CONCERN</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-line">
                        {{ $consultation->issue_concern ?? '—' }}
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded p-4 md:col-span-2 bg-white dark:bg-gray-900">
                    <div class="text-xs tracking-widest text-gray-500 dark:text-gray-300">INTERVENTION</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-line">
                        {{ $consultation->intervention ?? '—' }}
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

        {{-- After consultation --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden mt-6 bg-white dark:bg-gray-900">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">
                After Consultation
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            @if ($consultation->email_status === \App\Models\Consultation::EMAIL_STATUS_QUEUED)
                                <span class="inline-flex rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">Queued</span>
                            @elseif ($consultation->email_status === \App\Models\Consultation::EMAIL_STATUS_SENT)
                                <span class="inline-flex rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">Sent</span>
                                @if ($consultation->email_sent_at)
                                    <span class="text-xs text-gray-500 dark:text-gray-300">{{ $consultation->email_sent_at->format('M d, Y h:i A') }}</span>
                                @endif
                            @elseif ($consultation->email_status === \App\Models\Consultation::EMAIL_STATUS_FAILED)
                                <span class="inline-flex rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300">Failed</span>
                                <form method="POST" action="{{ route('guidance.consultations.email.retry', $consultation) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Retry email</button>
                                </form>
                            @else
                                <span class="text-gray-500 dark:text-gray-300">—</span>
                            @endif
                        </div>
                        @if ($consultation->email_status === \App\Models\Consultation::EMAIL_STATUS_FAILED && $consultation->email_failure_message)
                            <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $consultation->email_failure_message }}</p>
                        @endif
                    </div>
            </div>
        </div>
    </div>
</x-layouts.app>
