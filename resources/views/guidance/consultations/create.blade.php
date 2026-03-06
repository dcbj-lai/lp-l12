<x-layouts.app>
   <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
        x-data="{
            teacher: @js(old('current_teacher','')),
            checkedIn: false,

            timeInISO: '',
            timeInDisplay: '',

            decision: '',          // resume | go_home
            goHomeMethod: '',      // fetcher | self
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
            },

            checkIn() {
                if (!this.teacher) return;

                const now = new Date();

                // machine value (optional if you still submit time_in)
                this.timeInISO = now.toISOString();

                // display value (local PH time)
                this.timeInDisplay = now.toLocaleString('en-PH', {
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true,
                });

                this.checkedIn = true;
            }
        }">

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
                // Livewire v3 puts payload in e.detail
                const p = e.detail;

                // IMPORTANT: target the Alpine container that controls your form
                const root = document.getElementById('consultation-root');
                if (!root) return;

                const x = Alpine.$data(root);
                x.teacher = p.teacherName;
                x.checkedIn = true;
                x.timeInISO = p.timeInIso;
                x.timeInDisplay = p.timeInDisplay;
            });
            </script>
    <div id="consultation-root"
     x-data="{
        teacher: '',
        checkedIn: false,
        timeInISO: '',
        timeInDisplay: '',
        decision: '',
        goHomeMethod: '',
        approvedBy: '',
        fetcherName: '',
        canSubmit() {
          if (!this.checkedIn || !this.decision) return false;
          if (this.decision !== 'go_home') return true;
          if (!this.goHomeMethod) return false;
          return this.goHomeMethod === 'self'
            ? !!this.approvedBy.trim()
            : !!this.fetcherName.trim();
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

                    <input type="hidden" name="current_teacher" x-bind:value="teacher">
                    <input type="hidden" name="time_in" x-bind:value="timeInISO">

                    <input type="hidden" name="after_consultation" x-bind:value="decision">
                    <input type="hidden" name="going_home_method" x-bind:value="goHomeMethod">
                    <input type="hidden" name="fetcher_name" x-bind:value="fetcherName">
                    <input type="hidden" name="self_approved_by" x-bind:value="approvedBy">
                  <fieldset x-bind:disabled="!checkedIn" class="space-y-5">
                        <!-- Student Name (dynamic) -->
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
                                <select name="type_of_session" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                               dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">-- Select --</option>
                                    <option>Mandatory</option>
                                    <option>PFA</option>
                                    <option>Referral</option>
                                    <option>Walk-In</option>
                                    <option>Follow up</option>
                                    <option>Group</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Risk Assessment
                                </label>
                                <select name="risk_assessment" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                               dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">-- Select --</option>
                                    <option>Low</option>
                                    <option>Moderate</option>
                                    <option>High</option>
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
                            <label name="remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Remarks
                            </label>
                            <textarea name="remarks" rows="3"
                                      class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                             dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                             focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                      placeholder="Additional remarks..."></textarea>
                        </div>

                        <!-- Decision: Resume class or Go home -->
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

                                    <span :class="!teacher ? 'text-gray-400' : ''">
                                        Resume class
                                    </span>
                                </label>

                                <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                    <input type="radio" x-model="decision" value="go_home" class="text-indigo-600">
                                    Go home
                                </label>
                            </div>

                            <div x-show="decision === 'resume'" class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                On submit, the selected teacher will be notified that the student is resuming class.
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

                                <!-- Fetcher name -->
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
                                    />

                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Required when the student goes home with a fetcher.
                                    </p>
                                </div>

                                <!-- Approved by -->
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
                                    />

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

                       <button
                            type="submit"
                            name="action"
                            value="cancel"
                            class="px-3 py-2 text-sm rounded-md bg-red-600 hover:bg-red-700 text-white">
                            Cancel
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Note: Save/Submit will later record Time Out automatically and notify the selected teacher.
                    </p>
                </form>
            </div>
        </div>

        {{-- end off alpine root id wrapper --}}
    </div>

    </div>
</x-layouts.app>