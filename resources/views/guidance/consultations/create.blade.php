<x-layouts.app>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
            Health & Wellness
            <span class="mx-1">›</span>
            Guidance
            <span class="mx-1">›</span>
            <a href="{{ route('guidance.clients.index') }}" class="hover:underline">Clients</a>
            <span class="mx-1">›</span>
            <a href="{{ route('guidance.clients.show', $client) }}" class="hover:underline">Student Profile</a>
            <span class="mx-1">›</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">Start Consultation</span>
        </div>

        <!-- Header -->
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Start Consultation
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $client->first_name }} {{ $client->last_name }} — {{ $client->email }}
                </p>
            </div>

            <a href="{{ route('guidance.clients.show', $client) }}"
               class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50
                      dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                Back to Profile
            </a>
        </div>

        <livewire:guidance.check-in-consultation :client="$client" />

        <script>
            window.addEventListener('guidance-checked-in', (e) => {
                const p = e.detail;
                const root = document.getElementById('consultation-root');
                if (!root) return;

                const x = Alpine.$data(root);

                // teacher during check-in
                x.teacher = p.teacherName || '';
                x.teacherEmail = p.teacherEmail || '';

                // working dropdown now used as next class teacher
                x.nextClassTeacher = '';
                x.nextClassTeacherEmail = '';

                x.checkedIn = true;
                x.timeInISO = p.timeInIso || '';
                x.timeInDisplay = p.timeInDisplay || '';
            });
        </script>

        <div id="consultation-root"
             x-data="{
                teacher: '',
                teacherEmail: '',

                nextClassTeacher: '',
                nextClassTeacherEmail: '',

                checkedIn: false,
                timeInISO: '',
                timeInDisplay: '',

                decision: '',
                goHomeMethod: '',
                approvedBy: '',
                fetcherName: '',

                canSubmit() {
                    if (!this.checkedIn) return false;
                    if (!this.decision) return false;

                    if (this.decision === 'go_home') {
                        if (!this.goHomeMethod) return false;
                        if (this.goHomeMethod === 'self' && !this.approvedBy.trim()) return false;
                        if (this.goHomeMethod === 'fetcher' && !this.fetcherName.trim()) return false;
                    }

                    return true;
                }
             }">

            <!-- Consultation Form Card -->
            <div class="mt-6 rounded-lg shadow bg-white dark:bg-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Consultation Form</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Form is disabled until Time In (check-in) is recorded.
                    </p>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('guidance.consultations.store', $client) }}" class="space-y-5">
                        @csrf

                        {{-- Check-in teacher --}}
                        <input type="hidden" name="check_in_teacher" x-bind:value="teacher">
                        <input type="hidden" name="check_in_teacher_email" x-bind:value="teacherEmail">

                        {{-- Next class teacher --}}
                        <input type="hidden" name="current_teacher" x-bind:value="nextClassTeacher">
                        <input type="hidden" name="teacher_email" x-bind:value="nextClassTeacherEmail">

                        {{-- Other hidden form fields --}}
                        <input type="hidden" name="time_in" x-bind:value="timeInISO">
                        <input type="hidden" name="after_consultation" x-bind:value="decision">
                        <input type="hidden" name="going_home_method" x-bind:value="goHomeMethod">
                        <input type="hidden" name="fetcher_name" x-bind:value="fetcherName">
                        <input type="hidden" name="self_approved_by" x-bind:value="approvedBy">

                        <fieldset x-bind:disabled="!checkedIn" class="space-y-5">
                            <!-- Student Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Student Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text"
                                       value="{{ $client->first_name }} {{ $client->last_name }}"
                                       class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-sm text-gray-900
                                              dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600"
                                       readonly>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Type of Session
                                    </label>
                                    <select name="type_of_session"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                   dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                   focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">-- Select --</option>
                                        <option value="Mandatory">Mandatory</option>
                                        <option value="PFA">PFA</option>
                                        <option value="Referral">Referral</option>
                                        <option value="Walk-In">Walk-In</option>
                                        <option value="Follow up">Follow up</option>
                                        <option value="Group">Group</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Risk Assessment
                                    </label>
                                    <select name="risk_assessment"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                   dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                   focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">-- Select --</option>
                                        <option value="Low">Low</option>
                                        <option value="Moderate">Moderate</option>
                                        <option value="High">High</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Issue/Concern
                                </label>
                                <textarea name="issue_concern" rows="3"
                                          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Describe the issue/concern..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Brief Info on the Intervention
                                </label>
                                <textarea name="intervention" rows="3"
                                          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Describe the intervention..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Remarks
                                </label>
                                <textarea name="remarks" rows="3"
                                          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Additional remarks..."></textarea>
                            </div>

                            <!-- Next Class Teacher -->
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Next Class Teacher
                                </label>

                                <select
                                    x-model="nextClassTeacherEmail"
                                    @change="nextClassTeacher = $event.target.selectedOptions[0]?.dataset.name ?? ''"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                           dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">-- No teacher selected --</option>

                                    @foreach($teachers as $t)
                                        <option value="{{ $t['email'] }}" data-name="{{ $t['name'] }}">
                                            {{ $t['name'] }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Leave blank if there is no teacher or class to be notified.
                                </p>
                            </div>

                            <!-- Decision -->
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">
                                    After Consultation <span class="text-red-600">*</span>
                                </label>

                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                        <input type="radio"
                                               x-model="decision"
                                               value="resume"
                                               class="text-indigo-600">
                                        Resume class
                                    </label>

                                    <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                        <input type="radio"
                                               x-model="decision"
                                               value="go_home"
                                               class="text-indigo-600">
                                        Go home
                                    </label>
                                </div>

                                <div x-show="decision === 'resume'" class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                    If a next class teacher is selected, an email may be sent after submission.
                                </div>

                                <div x-show="decision === 'go_home'" class="mt-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                            Going home method <span class="text-red-600">*</span>
                                        </label>

                                        <div class="space-y-2">
                                            <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                                <input type="radio" x-model="goHomeMethod" value="fetcher" class="text-indigo-600">
                                                With fetcher
                                            </label>

                                            <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                                <input type="radio" x-model="goHomeMethod" value="self" class="text-indigo-600">
                                                By oneself
                                            </label>
                                        </div>
                                    </div>

                                    <div x-show="goHomeMethod === 'fetcher'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                            Fetcher Name <span class="text-red-600">*</span>
                                        </label>

                                        <input
                                            type="text"
                                            x-model="fetcherName"
                                            placeholder="Name of fetcher"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                   dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                   focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >

                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Required when the student goes home with a fetcher.
                                        </p>
                                    </div>

                                    <div x-show="goHomeMethod === 'self'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                            Approved by <span class="text-red-600">*</span>
                                        </label>

                                        <input
                                            type="text"
                                            x-model="approvedBy"
                                            placeholder="Name of approving person"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                   dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                   focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >

                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Required when the student goes home by oneself.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit"
                                    :disabled="!canSubmit()"
                                    class="inline-flex items-center rounded px-4 py-2 text-sm font-medium text-white transition"
                                    :class="!canSubmit()
                                        ? 'bg-gray-400 cursor-not-allowed'
                                        : 'bg-green-600 hover:bg-green-700'">
                                Save / Submit
                            </button>

                            <button type="submit"
                                    name="action"
                                    value="cancel"
                                    class="px-3 py-2 text-sm rounded-md bg-red-600 hover:bg-red-700 text-white">
                                Cancel
                            </button>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Note: Save/Submit will record Time Out automatically. If no teacher is selected, no email will be sent for either Resume class or Go home.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>