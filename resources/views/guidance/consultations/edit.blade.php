<x-layouts.app>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <form
            method="POST"
            action="{{ route('guidance.consultations.update', $consultation) }}"

            x-data="{
                locked: {{ $consultation->time_out ? 'true' : 'false' }},
                decision: '{{ old('after_consultation', $consultation->after_consultation ?? 'resume') }}',
                goingHomeMethod: '{{ old('going_home_method', $consultation->going_home_method) }}',
                fetcherName: '{{ old('fetcher_name', $consultation->fetcher_name) }}',
                approvedBy: '{{ old('self_approved_by', $consultation->self_approved_by) }}'
            }"

            x-init="$watch('decision', value => {
                if (value === 'resume') {
                    goingHomeMethod = ''
                    fetcherName = ''
                    approvedBy = ''
                }
            })"
        >
            @csrf
            @method('PUT')

            <input
                type="hidden"
                name="return_url"
                value="{{ request('return_url') }}"
            >

            <!-- LOCK NOTICE -->
            <div
                x-show="locked"
                class="mb-6 p-3 rounded-lg border text-sm
                       bg-yellow-100 border-yellow-300 text-yellow-800
                       dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-200"
            >
                Consultation disposition is finalized. Only session notes may be edited.
            </div>

            <!-- ======================================================
            TOP CARDS
            ====================================================== -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

                <!-- Teacher in Check-In -->
                <div class="rounded-xl p-5 border bg-white border-neutral-200 shadow-sm
                            dark:bg-neutral-900 dark:border-neutral-700">
                    <p class="text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                        Teacher in Check-In
                    </p>

                    <input
                        type="text"
                        name="check_in_teacher"
                        value="{{ old('check_in_teacher', $consultation->check_in_teacher ?: 'No Teacher Assigned') }}"
                        :disabled="locked"
                        class="w-full bg-transparent text-lg focus:outline-none
                               text-neutral-900 placeholder-neutral-400
                               dark:text-white dark:placeholder-neutral-500"
                    >
                </div>

                <!-- Teacher in Check-Out -->
                <div class="rounded-xl p-5 border bg-white border-neutral-200 shadow-sm
                            dark:bg-neutral-900 dark:border-neutral-700">
                    <p class="text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                        Teacher in Check-Out
                    </p>

                    <input
                        type="text"
                        name="current_teacher"
                        value="{{ old('current_teacher', $consultation->current_teacher ?: 'No Teacher Assigned') }}"
                        :disabled="locked"
                        class="w-full bg-transparent text-lg focus:outline-none
                               text-neutral-900 placeholder-neutral-400
                               dark:text-white dark:placeholder-neutral-500"
                    >
                </div>

                <!-- Time In -->
                <div class="rounded-xl p-5 border bg-white border-neutral-200 shadow-sm
                            dark:bg-neutral-900 dark:border-neutral-700">
                    <p class="text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                        Time In
                    </p>

                    <input
                        type="datetime-local"
                        name="time_in"
                        value="{{ old('time_in', optional($consultation->time_in)->format('Y-m-d\TH:i')) }}"
                        :disabled="locked"
                        class="w-full bg-transparent focus:outline-none
                               text-neutral-900
                               dark:text-white"
                    >
                </div>

                <!-- Time Out -->
                <div class="rounded-xl p-5 border bg-white border-neutral-200 shadow-sm
                            dark:bg-neutral-900 dark:border-neutral-700">
                    <p class="text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                        Time Out
                    </p>

                    <input
                        type="datetime-local"
                        name="time_out"
                        value="{{ old('time_out', optional($consultation->time_out)->format('Y-m-d\TH:i')) }}"
                        :disabled="locked"
                        class="w-full bg-transparent focus:outline-none
                               text-neutral-900
                               dark:text-white"
                    >
                </div>
            </div>

            <!-- ======================================================
            SESSION INFORMATION (EDITABLE)
            ====================================================== -->
            <div class="rounded-xl p-6 mb-8 border bg-white border-neutral-200 shadow-sm
                        dark:bg-neutral-900 dark:border-neutral-700">

                <h2 class="text-lg font-semibold mb-6 text-neutral-900 dark:text-white">
                    Session Information
                </h2>

                <div class="grid md:grid-cols-2 gap-6 mb-6">

                    <!-- Type of Session -->
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                            Type of Session
                        </label>

                        <select
                            name="type_of_session"
                            class="w-full rounded-lg p-3 border
                                   bg-white text-neutral-900 border-neutral-300
                                   focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                                   dark:bg-neutral-800 dark:text-white dark:border-neutral-700"
                        >
                            @foreach(['Mandatory','PFA','Referral','Walk-In','Follow up','Group'] as $type)
                                <option
                                    value="{{ $type }}"
                                    {{ old('type_of_session', $consultation->type_of_session) === $type ? 'selected' : '' }}
                                >
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Risk Assessment -->
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                            Risk Assessment
                        </label>

                        <select
                            name="risk_assessment"
                            class="w-full rounded-lg p-3 border
                                   bg-white text-neutral-900 border-neutral-300
                                   focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                                   dark:bg-neutral-800 dark:text-white dark:border-neutral-700"
                        >
                            @foreach(['Low','Moderate','High'] as $risk)
                                <option
                                    value="{{ $risk }}"
                                    {{ old('risk_assessment', $consultation->risk_assessment) === $risk ? 'selected' : '' }}
                                >
                                    {{ $risk }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Issue -->
                <div class="mb-6">
                    <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                        Issue / Concern
                    </label>

                    <textarea
                        name="issue_concern"
                        rows="4"
                        class="w-full rounded-lg p-3 border
                               bg-white text-neutral-900 border-neutral-300
                               placeholder-neutral-400
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                               dark:bg-neutral-800 dark:text-white dark:border-neutral-700 dark:placeholder-neutral-500"
                    >{{ old('issue_concern', $consultation->issue_concern) }}</textarea>
                </div>

                <!-- Intervention -->
                <div class="mb-6">
                    <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                        Intervention
                    </label>

                    <textarea
                        name="intervention"
                        rows="4"
                        class="w-full rounded-lg p-3 border
                               bg-white text-neutral-900 border-neutral-300
                               placeholder-neutral-400
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                               dark:bg-neutral-800 dark:text-white dark:border-neutral-700 dark:placeholder-neutral-500"
                    >{{ old('intervention', $consultation->intervention) }}</textarea>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        rows="3"
                        class="w-full rounded-lg p-3 border
                               bg-white text-neutral-900 border-neutral-300
                               placeholder-neutral-400
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                               dark:bg-neutral-800 dark:text-white dark:border-neutral-700 dark:placeholder-neutral-500"
                    >{{ old('remarks', $consultation->remarks) }}</textarea>
                </div>
            </div>

            <!-- ======================================================
            AFTER CONSULTATION
            ====================================================== -->
            <div class="rounded-xl p-6 mb-8 border bg-white border-neutral-200 shadow-sm
                        dark:bg-neutral-900 dark:border-neutral-700">

                <h2 class="text-lg font-semibold mb-6 text-neutral-900 dark:text-white">
                    After Consultation
                </h2>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                            Decision
                        </label>

                        <select
                            name="after_consultation"
                            x-model="decision"
                            :disabled="locked"
                            class="w-full rounded-lg p-3 border
                                   bg-white text-neutral-900 border-neutral-300
                                   focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                                   disabled:bg-neutral-100 disabled:text-neutral-500
                                   dark:bg-neutral-800 dark:text-white dark:border-neutral-700
                                   dark:disabled:bg-neutral-800/60 dark:disabled:text-neutral-400"
                        >
                            <option value="resume">Resume</option>
                            <option value="go_home">Go Home</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                            Going Home Method
                        </label>

                        <select
                            name="going_home_method"
                            x-model="goingHomeMethod"
                            :disabled="locked || decision === 'resume'"
                            class="w-full rounded-lg p-3 border
                                   bg-white text-neutral-900 border-neutral-300
                                   focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                                   disabled:bg-neutral-100 disabled:text-neutral-500
                                   dark:bg-neutral-800 dark:text-white dark:border-neutral-700
                                   dark:disabled:bg-neutral-800/60 dark:disabled:text-neutral-400"
                        >
                            <option value="">Select</option>
                            <option value="fetcher">Fetcher</option>
                            <option value="self">Self</option>
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                            Fetcher Name
                        </label>

                        <input
                            type="text"
                            name="fetcher_name"
                            x-model="fetcherName"
                            :disabled="locked || decision === 'resume'"
                            class="w-full rounded-lg p-3 border
                                   bg-white text-neutral-900 border-neutral-300
                                   placeholder-neutral-400
                                   focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                                   disabled:bg-neutral-100 disabled:text-neutral-500
                                   dark:bg-neutral-800 dark:text-white dark:border-neutral-700 dark:placeholder-neutral-500
                                   dark:disabled:bg-neutral-800/60 dark:disabled:text-neutral-400"
                        >
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-2 text-neutral-500 dark:text-neutral-400">
                            Self Approved By
                        </label>

                        <input
                            type="text"
                            name="self_approved_by"
                            x-model="approvedBy"
                            :disabled="locked || decision === 'resume'"
                            class="w-full rounded-lg p-3 border
                                   bg-white text-neutral-900 border-neutral-300
                                   placeholder-neutral-400
                                   focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                                   disabled:bg-neutral-100 disabled:text-neutral-500
                                   dark:bg-neutral-800 dark:text-white dark:border-neutral-700 dark:placeholder-neutral-500
                                   dark:disabled:bg-neutral-800/60 dark:disabled:text-neutral-400"
                        >
                    </div>
                </div>
            </div>

            <!-- ======================================================
            BUTTONS
            ====================================================== -->
            <div class="flex justify-end gap-4">
                @if(request('return_url'))
                    <a
                        href="{{ request('return_url') }}"
                        class="px-4 py-2 rounded-md border font-medium transition
                               bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100
                               dark:bg-neutral-900 dark:text-white dark:border-neutral-600 dark:hover:bg-neutral-800"
                    >
                        Cancel
                    </a>
                @else
                    <a
                        href="{{ route('guidance.consultations.index') }}"
                        class="px-4 py-2 rounded-md border font-medium transition
                               bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100
                               dark:bg-neutral-900 dark:text-white dark:border-neutral-600 dark:hover:bg-neutral-800"
                    >
                        Cancel
                    </a>
                @endif

                <button
                    type="submit"
                    class="px-5 py-2 rounded-md font-medium text-white bg-green-600 hover:bg-green-700 transition"
                >
                    Update Consultation
                </button>
            </div>

        </form>
    </div>
</x-layouts.app>