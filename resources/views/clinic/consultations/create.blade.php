{{-- resources/views/clinic/consultations/create.blade.php --}}
<x-layouts.app title="Start Clinic Consultation">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <div
            id="consultation-root"
            x-data="{
                patientType: @js($patient->type),

                // Filled by Livewire check-in event
                consultationId: @js(old('consultation_id', '')),
                checkedIn: false,
                timeInISO: @js(old('time_in', '')),
                timeInDisplay: @js(old('time_in_display', '')),

                // For emails / tracking (student only, optional)
                checkInTeacher: @js(old('check_in_teacher', '')),
                checkInTeacherEmail: @js(old('check_in_teacher_email', '')),

                // Next class teacher -> current_teacher + teacher_email
                nextClassTeacher: @js(old('current_teacher', '')),
                nextClassTeacherEmail: @js(old('teacher_email', '')),

                // Student workflow fields
                decision: @js(old('after_consultation', '')),
                goHomeMethod: @js(old('going_home_method', '')),
                approvedBy: @js(old('self_approved_by', '')),
                fetcherName: @js(old('fetcher_name', '')),

                init() {
                    if (this.consultationId) {
                        this.checkedIn = true;
                    }

                    if (this.timeInISO && !this.timeInDisplay) {
                        const d = new Date(this.timeInISO);
                        if (!isNaN(d.getTime())) {
                            this.timeInDisplay = d.toLocaleString('en-PH', {
                                timeZone: 'Asia/Manila',
                                year: 'numeric',
                                month: 'short',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true,
                            });
                        }
                    }
                },

                isStudent() { return this.patientType === 'student'; },
                isStaff() { return this.patientType === 'staff'; },

                canSubmit() {
                    if (!this.checkedIn) return false;
                    if (!this.consultationId) return false;

                    if (this.isStaff()) return true;

                    if (!this.decision) return false;

                    if (this.decision === 'go_home') {
                        if (!this.goHomeMethod) return false;
                        if (this.goHomeMethod === 'self' && !this.approvedBy.trim()) return false;
                        if (this.goHomeMethod === 'fetcher' && !this.fetcherName.trim()) return false;
                    }

                    return true;
                },
            }"
        >

            {{-- Breadcrumb --}}
            <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                Health & Wellness
                <span class="mx-1">›</span>
                Clinic
                <span class="mx-1">›</span>
                <a href="{{ route('clinic.patients.index', ['tab' => $patient->type === 'staff' ? 'staff' : 'students']) }}"
                   class="hover:underline">
                    Patients
                </a>
                <span class="mx-1">›</span>
                <a href="{{ route('clinic.patients.show', ['patient' => $patient->id, 'tab' => request('tab', $patient->type === 'staff' ? 'staff' : 'students')]) }}"
                   class="hover:underline">
                    Patient Profile
                </a>
                <span class="mx-1">›</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">Start Consultation</span>
            </div>

            {{-- Header --}}
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Start Consultation
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $patient->first_name }} {{ $patient->last_name }} — {{ $patient->email ?? 'No email' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 capitalize">
                        Patient Type: {{ $patient->type }}
                    </p>
                </div>

                <a href="{{ route('clinic.patients.show', ['patient' => $patient->id, 'tab' => request('tab', $patient->type === 'staff' ? 'staff' : 'students')]) }}"
                   class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50
                          dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                    Back to Profile
                </a>
            </div>

            {{-- Flash / Validation --}}
            @if (session('flash'))
                <div class="mb-4 rounded border p-4
                    {{ session('flash.type') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : '' }}
                    {{ session('flash.type') === 'error' ? 'border-red-200 bg-red-50 text-red-800' : '' }}
                    {{ session('flash.type') === 'info' ? 'border-blue-200 bg-blue-50 text-blue-800' : '' }}">
                    {{ session('flash.message') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 p-4 text-red-800">
                    <p class="font-medium mb-2">Please fix the following:</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <livewire:clinic.check-in-consultation :patient="$patient" />

            <script>
                window.addEventListener('clinic-checked-in', (e) => {
                    const p = e.detail;
                    const root = document.getElementById('consultation-root');
                    if (!root) return;

                    const x = Alpine.$data(root);

                    x.consultationId = p.consultationId ?? '';
                    x.checkedIn = true;
                    x.timeInISO = p.timeInIso ?? '';
                    x.timeInDisplay = p.timeInDisplay ?? '';
                    x.checkInTeacher = p.teacherName ?? '';
                    x.checkInTeacherEmail = p.teacherEmail ?? '';
                });
            </script>

            {{-- Consultation Form Card --}}
            <div class="mt-6 rounded-lg shadow bg-white dark:bg-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Consultation Form</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Form is disabled until Time In (check-in) is recorded.
                    </p>
                </div>

                <div class="p-6">
                    <form method="POST"
                          action="{{ route('clinic.consultations.store', $patient) }}"
                          enctype="multipart/form-data"
                          class="space-y-5">
                        @csrf

                        <input type="hidden" name="consultation_id" :value="consultationId">
                        <input type="hidden" name="time_in" :value="timeInISO">

                        <input type="hidden" name="check_in_teacher" :value="checkInTeacher">
                        <input type="hidden" name="check_in_teacher_email" :value="checkInTeacherEmail">

                        <input type="hidden" name="current_teacher" :value="nextClassTeacher">
                        <input type="hidden" name="teacher_email" :value="nextClassTeacherEmail">

                        <template x-if="isStudent()">
                            <div>
                                <input type="hidden" name="after_consultation" :value="decision">
                                <input type="hidden" name="going_home_method" :value="goHomeMethod">
                                <input type="hidden" name="fetcher_name" :value="fetcherName">
                                <input type="hidden" name="self_approved_by" :value="approvedBy">
                            </div>
                        </template>

                        <fieldset :disabled="!checkedIn" class="space-y-5">

                            {{-- Patient Name --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Patient Name
                                </label>
                                <input type="text"
                                       value="{{ $patient->first_name }} {{ $patient->last_name }}"
                                       class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-sm text-gray-900
                                              dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600"
                                       readonly>
                            </div>

                            {{-- Chief Complaint --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Chief Complaint</label>
                                <textarea name="chief_complaint" rows="3"
                                          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Describe the chief complaint...">{{ old('chief_complaint') }}</textarea>
                            </div>
                            {{-- Classification + Vitals + Pain --}}
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Case Classification</label>
                                    <select name="case_classification"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">-- Select --</option>
                                        <option value="non_urgent" @selected(old('case_classification') === 'non_urgent')>Non urgent</option>
                                        <option value="trauma" @selected(old('case_classification') === 'trauma')>Trauma</option>
                                        <option value="urgent" @selected(old('case_classification') === 'urgent')>Urgent</option>
                                        <option value="communicable" @selected(old('case_classification') === 'communicable')>Communicable</option>
                                    </select>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Pain Rating (0–10)</label>
                                    <input type="number" name="pain_rating" value="{{ old('pain_rating') }}" min="0" max="10" placeholder="0"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Temperature (°C)</label>
                                    <input type="number" step="0.1" name="temperature" value="{{ old('temperature') }}" placeholder="e.g. 36.5"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Blood Pressure</label>
                                    <input type="text" name="blood_pressure" value="{{ old('blood_pressure') }}" placeholder="e.g. 120/80"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Pulse Rate (bpm)</label>
                                    <input type="number" name="pulse_rate" value="{{ old('pulse_rate') }}" min="0" placeholder="e.g. 76"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Respiratory Rate</label>
                                    <input type="number" name="respiratory_rate" value="{{ old('respiratory_rate') }}" min="0" placeholder="e.g. 18"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">O2 Saturation (SpO2 %)</label>
                                    <input type="number" name="o2_saturation" value="{{ old('o2_saturation') }}" min="0" max="100" placeholder="e.g. 98"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                            </div>

                            {{-- Assessment --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Assessment</label>
                                <textarea name="assessment" rows="3"
                                          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Assessment...">{{ old('assessment') }}</textarea>
                            </div>

                            {{-- Treatment --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Treatment</label>
                                <textarea name="treatment" rows="3"
                                          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Treatment given...">{{ old('treatment') }}</textarea>
                            </div>

                            {{-- Medicines --}}
                            <div
                                x-data='{
                                    medicine_name: "",
                                    medicine_qty: "",
                                    medicine_label: "",
                                    medicines: @json(old("medicines", [])),

                                    addMedicine() {
                                        const name = this.medicine_name.trim();
                                        const qtyRaw = String(this.medicine_qty).trim();
                                        const label = this.medicine_label.trim();

                                        if (!name || !qtyRaw) return;

                                        const qty = parseInt(qtyRaw, 10);
                                        if (!Number.isFinite(qty) || qty <= 0) return;

                                        this.medicines.push({
                                            name: name,
                                            qty: qty,
                                            label: label || null,
                                        });

                                        this.medicine_name = "";
                                        this.medicine_qty = "";
                                        this.medicine_label = "";
                                    },

                                    removeMedicine(index) {
                                        this.medicines.splice(index, 1);
                                    }
                                }'
                            >
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Medicines Given
                                </label>

                                {{-- Pills --}}
                                <div class="flex flex-wrap gap-2 mb-2 p-2 border border-dashed border-gray-300 dark:border-gray-600 rounded-md min-h-[2.5rem]">
                                    <template x-if="medicines.length === 0">
                                        <span class="text-xs text-gray-400">Add medicine(s) here...</span>
                                    </template>

                                    <template x-for="(medicine, index) in medicines" :key="index">
                                        <div class="flex items-center gap-2 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs">
                                            <span x-text="medicine.label ? `${medicine.name} (${medicine.qty}) — ${medicine.label}` : `${medicine.name} (${medicine.qty})`"></span>

                                            <button type="button" @click="removeMedicine(index)" class="hover:text-red-600">
                                                ✕
                                            </button>

                                            <input type="hidden" :name="`medicines[${index}][name]`" :value="medicine.name">
                                            <input type="hidden" :name="`medicines[${index}][qty]`" :value="medicine.qty">
                                            <input type="hidden" :name="`medicines[${index}][label]`" :value="medicine.label ?? ''">
                                        </div>
                                    </template>
                                </div>

                                @error('medicines')
                                    <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                                @enderror
                                @error('medicines.*.name')
                                    <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                                @enderror
                                @error('medicines.*.qty')
                                    <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                                @enderror
                                @error('medicines.*.label')
                                    <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                                @enderror

                                {{-- Input fields --}}
                                <div class="flex flex-col md:flex-row gap-2">
                                    <input
                                        type="text"
                                        x-model="medicine_name"
                                        placeholder="Medicine name"
                                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-56"
                                    >

                                    <input
                                        type="number"
                                        min="1"
                                        x-model="medicine_qty"
                                        placeholder="Qty"
                                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-32"
                                    >

                                    <input
                                        type="text"
                                        x-model="medicine_label"
                                        placeholder="Label (optional) e.g. bottles x 250 mL"
                                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-72"
                                    >

                                    <button
                                        type="button"
                                        @click="addMedicine()"
                                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                    >
                                        Add
                                    </button>
                                </div>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Tip: Use label for packaging/dosage notes (e.g., “bottles x 250 mL”, “250mg/5mL — 10 mL given”).
                                </p>
                            </div>

                            {{-- Supplies --}}
                            <div
                                x-data='{
                                    supply_name: "",
                                    supply_qty: "",
                                    supplies: @json(old("supplies", [])),

                                    addSupply() {
                                        const name = this.supply_name.trim();
                                        const qty = this.supply_qty;

                                        if (!name || !qty) return;

                                        this.supplies.push({
                                            name: name,
                                            qty: parseInt(qty, 10)
                                        });

                                        this.supply_name = "";
                                        this.supply_qty = "";
                                    },

                                    removeSupply(index) {
                                        this.supplies.splice(index, 1);
                                    }
                                }'
                            >
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Supplies Used
                                </label>

                                {{-- Pills --}}
                                <div class="flex flex-wrap gap-2 mb-2 p-2 border border-dashed border-gray-300 dark:border-gray-600 rounded-md min-h-[2.5rem]">
                                    <template x-if="supplies.length === 0">
                                        <span class="text-xs text-gray-400">Add supply item(s) here...</span>
                                    </template>

                                    <template x-for="(supply, index) in supplies" :key="index">
                                        <div class="flex items-center gap-2 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs">
                                            <span x-text="`${supply.name} (${supply.qty})`"></span>
                                            <button type="button" @click="removeSupply(index)" class="hover:text-red-600">
                                                ✕
                                            </button>

                                            <input type="hidden" :name="`supplies[${index}][name]`" :value="supply.name">
                                            <input type="hidden" :name="`supplies[${index}][qty]`" :value="supply.qty">
                                        </div>
                                    </template>
                                </div>

                                @error('supplies')
                                    <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                                @enderror
                                @error('supplies.*.name')
                                    <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                                @enderror
                                @error('supplies.*.qty')
                                    <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                                @enderror

                                {{-- Input fields --}}
                                <div class="flex flex-col md:flex-row gap-2">
                                    <input type="text"
                                        x-model="supply_name"
                                        placeholder="Supply item"
                                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-56">

                                    <input type="number"
                                        min="1"
                                        x-model="supply_qty"
                                        placeholder="Qty"
                                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-40">

                                    <button type="button"
                                            @click="addSupply()"
                                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                        Add
                                    </button>
                                </div>
                            </div>

                            {{-- Remarks --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Remarks</label>
                                <textarea name="remarks" rows="3"
                                          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Remarks...">{{ old('remarks') }}</textarea>
                            </div>

                            {{-- Photos upload --}}
                            <div
                                x-data="{
                                    files: [],

                                    handleFiles(event) {
                                        const selectedFiles = Array.from(event.target.files || []);

                                        selectedFiles.forEach((file, index) => {
                                            if (!file.type.startsWith('image/')) return;

                                            const exists = this.files.some(item =>
                                                item.file.name === file.name &&
                                                item.file.size === file.size &&
                                                item.file.lastModified === file.lastModified
                                            );

                                            if (exists) return;

                                            const id = `${file.name}-${file.size}-${file.lastModified}-${Date.now()}-${index}`;
                                            const reader = new FileReader();

                                            reader.onload = (e) => {
                                                this.files.push({
                                                    id,
                                                    file,
                                                    name: file.name,
                                                    url: e.target.result,
                                                    keep: true,
                                                });

                                                this.syncInput();
                                            };

                                            reader.readAsDataURL(file);
                                        });

                                        event.target.value = '';
                                    },

                                    syncInput() {
                                        const input = this.$refs.photoInput;
                                        if (!input) return;

                                        const dt = new DataTransfer();

                                        this.files.forEach(item => {
                                            dt.items.add(item.file);
                                        });

                                        input.files = dt.files;
                                    },

                                    toggleKeep(index, checked) {
                                        if (!checked) {
                                            this.files.splice(index, 1);
                                            this.syncInput();
                                        }
                                    },

                                    removePhoto(index) {
                                        this.files.splice(index, 1);
                                        this.syncInput();
                                    }
                                }"
                            >
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Upload Photos
                                </label>

                                <input type="file"
                                       x-ref="photoInput"
                                       name="photo_attachments[]"
                                       accept="image/*"
                                       multiple
                                       @change="handleFiles($event)"
                                       class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                              dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                              focus:outline-none focus:ring-2 focus:ring-indigo-500">

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    You may upload multiple images (JPG/PNG). Max 5MB each.
                                </p>

                                <div x-show="files.length" x-cloak class="mt-4">
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                                        Preview
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                        <template x-for="(item, index) in files" :key="item.id">
                                            <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                                                <div class="aspect-square">
                                                    <img :src="item.url" :alt="item.name" class="h-full w-full object-cover" />
                                                </div>

                                                <div class="px-2 py-2 space-y-2">
                                                    <div class="text-[11px] text-gray-600 dark:text-gray-300 truncate" x-text="item.name"></div>

                                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                                        <input type="checkbox"
                                                               checked
                                                               @change="toggleKeep(index, $event.target.checked)"
                                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                        Keep this photo
                                                    </label>

                                                    <button type="button"
                                                            @click="removePhoto(index)"
                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- STUDENTS ONLY --}}
                            <div x-show="isStudent()" x-cloak class="space-y-4">

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

                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">
                                        After Consultation <span class="text-red-600">*</span>
                                    </label>

                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                            <input type="radio" x-model="decision" value="resume" class="text-indigo-600">
                                            Resume class
                                        </label>

                                        <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                            <input type="radio" x-model="decision" value="go_home" class="text-indigo-600">
                                            Go home
                                        </label>
                                    </div>

                                    <div x-show="decision === 'go_home'" x-cloak class="mt-4 space-y-3">
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

                                        <div x-show="goHomeMethod === 'fetcher'" x-cloak>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                                Fetcher Name <span class="text-red-600">*</span>
                                            </label>
                                            <input type="text"
                                                   x-model="fetcherName"
                                                   placeholder="Name of fetcher"
                                                   class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                          dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                          focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        </div>

                                        <div x-show="goHomeMethod === 'self'" x-cloak>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                                Approved by <span class="text-red-600">*</span>
                                            </label>
                                            <input type="text"
                                                   x-model="approvedBy"
                                                   placeholder="Name of approving person"
                                                   class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                                                          dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                                                          focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Staff note --}}
                            <div x-show="isStaff()" x-cloak
                                 class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4 text-sm text-gray-700 dark:text-gray-300">
                                Staff consultations are recorded only (no after-consultation workflow).
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

                            <a href="{{ route('clinic.patients.show', ['patient' => $patient->id, 'tab' => request('tab', $patient->type === 'staff' ? 'staff' : 'students')]) }}"
                               class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50
                                      dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                                Cancel
                            </a>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Note: Save/Submit records Time Out and saves the consultation details.
                        </p>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>