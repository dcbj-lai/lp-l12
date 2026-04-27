<x-layouts.app title="Edit Clinic Consultation">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        @php
            $returnUrl = request('return_url') ?? ($returnUrl ?? null);

            $backUrl = (is_string($returnUrl) && filter_var($returnUrl, FILTER_VALIDATE_URL))
                ? $returnUrl
                : route('clinic.consultations.index');

            $photos = is_array($consultation->photo_attachments) ? $consultation->photo_attachments : [];
        @endphp

        <!-- Breadcrumb -->
        <div class="mb-3 text-sm text-neutral-600 dark:text-neutral-400">
            Health & Wellness
            <span class="mx-1">›</span>
            Clinic
            <span class="mx-1">›</span>
            <a href="{{ route('clinic.patients.index', ['tab' => optional($consultation->patient)->type === 'staff' ? 'staff' : 'students']) }}"
               class="hover:underline">
                Patients
            </a>
            <span class="mx-1">›</span>
            <a href="{{ route('clinic.patients.show', [
                    'patient' => $consultation->patient_id,
                    'tab' => optional($consultation->patient)->type === 'staff' ? 'staff' : 'students'
                ]) }}"
               class="hover:underline">
                Patient Profile
            </a>
            <span class="mx-1">›</span>
            <span class="font-semibold text-neutral-900 dark:text-neutral-100">
                Edit Consultation
            </span>
        </div>

        <!-- Header -->
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                    Edit Consultation
                </h1>
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                    {{ optional($consultation->patient)->first_name }} {{ optional($consultation->patient)->last_name }}
                    @if(optional($consultation->patient)->email)
                        — {{ optional($consultation->patient)->email }}
                    @endif
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ $backUrl }}"
                   class="inline-flex items-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50
                          dark:border-neutral-600 dark:text-neutral-200 dark:hover:bg-neutral-800">
                    Back
                </a>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-4 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation errors --}}
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

        <div class="rounded-lg shadow bg-white dark:bg-neutral-900 overflow-hidden border border-neutral-200 dark:border-neutral-700">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Consultation Details</h2>
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                    Update medical details and photo attachments.
                </p>
            </div>

            <div class="p-6">
                <form method="POST"
                    action="{{ route('clinic.consultations.update', $consultation) }}"
                    enctype="multipart/form-data"
                    x-data="photoUploadHandler()"
                    @submit="beforeSubmit($event)">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="return_url" value="{{ $returnUrl }}">

                    <!-- Case + Vitals + Pain -->
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                                Case Classification
                            </label>
                            <select name="case_classification"
                                    class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- Select --</option>
                                <option value="non_urgent" @selected(old('case_classification', $consultation->case_classification) === 'non_urgent')>Non urgent</option>
                                <option value="trauma" @selected(old('case_classification', $consultation->case_classification) === 'trauma')>Trauma</option>
                                <option value="urgent" @selected(old('case_classification', $consultation->case_classification) === 'urgent')>Urgent</option>
                                <option value="communicable" @selected(old('case_classification', $consultation->case_classification) === 'communicable')>Communicable</option>
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                                Pain Rating (0–10)
                            </label>
                            <input type="number"
                                name="pain_rating"
                                min="0"
                                max="10"
                                value="{{ old('pain_rating', $consultation->pain_rating) }}"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                                Temperature (°C)
                            </label>
                            <input type="number"
                                step="0.01"
                                name="temperature"
                                value="{{ old('temperature', $consultation->temperature) }}"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                                Blood Pressure
                            </label>
                            <input type="text"
                                name="blood_pressure"
                                value="{{ old('blood_pressure', $consultation->blood_pressure) }}"
                                placeholder="e.g. 120/80"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                                Pulse Rate (bpm)
                            </label>
                            <input type="number"
                                name="pulse_rate"
                                min="0"
                                value="{{ old('pulse_rate', $consultation->pulse_rate) }}"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                                Respiratory Rate
                            </label>
                            <input type="number"
                                name="respiratory_rate"
                                min="0"
                                value="{{ old('respiratory_rate', $consultation->respiratory_rate) }}"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                                O2 Saturation (SpO2 %)
                            </label>
                            <input type="number"
                                name="o2_saturation"
                                min="0"
                                max="100"
                                value="{{ old('o2_saturation', $consultation->o2_saturation) }}"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Text areas -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                            Chief Complaint
                        </label>
                        <textarea name="chief_complaint" rows="3"
                                  class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                         dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                         focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('chief_complaint', $consultation->chief_complaint) }}</textarea>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                            Assessment
                        </label>
                        <textarea name="assessment" rows="3"
                                  class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                         dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                         focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('assessment', $consultation->assessment) }}</textarea>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2">
                            Treatment
                        </label>
                        <textarea name="treatment" rows="3"
                                  class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                         dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                         focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('treatment', $consultation->treatment) }}</textarea>
                    </div>

                   {{-- Medicines (Dynamic + Label) --}}
                    <div
                        x-data='{
                            medicine_name: "",
                            medicine_qty: "",
                            medicine_label: "",
                            medicines: @json(old("medicines", $consultation->medicines ?? [])),

                            addMedicine() {
                                const name  = this.medicine_name.trim();
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

                            removeMedicine(i) {
                                this.medicines.splice(i, 1);
                            }
                        }'
                    >
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2 mt-3">
                            Medicines Given
                        </label>

                        {{-- Pills --}}
                        <div class="flex flex-wrap gap-2 mb-2 p-2 border border-dashed border-neutral-300 dark:border-neutral-600 rounded-md min-h-[2.5rem]">
                            <template x-if="medicines.length === 0">
                                <span class="text-xs text-neutral-400">Add medicine(s) here...</span>
                            </template>

                            <template x-for="(medicine, index) in medicines" :key="index">
                                <div class="flex items-center gap-2 border border-neutral-300 dark:border-neutral-600 bg-neutral-100 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100 px-3 py-1 rounded-full text-xs">
                                    <span x-text="medicine.label
                                        ? `${medicine.name} (${medicine.qty}) — ${medicine.label}`
                                        : `${medicine.name} (${medicine.qty})`
                                    "></span>

                                    <button type="button" @click="removeMedicine(index)" class="hover:text-red-600">
                                        ✕
                                    </button>

                                    {{-- Hidden inputs posted to controller --}}
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

                        {{-- Inputs --}}
                        <div class="flex flex-col md:flex-row gap-2">
                            <input type="text"
                                x-model="medicine_name"
                                placeholder="Medicine name"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500 md:w-56">

                            <input type="number"
                                min="1"
                                x-model="medicine_qty"
                                placeholder="Qty"
                                class="w-full md:w-28 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">

                            <input type="text"
                                x-model="medicine_label"
                                placeholder="Label (optional) e.g. bottles x 250 mL"
                                class="w-full md:w-64 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">

                            <button type="button"
                                    @click="addMedicine()"
                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Add
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                            Label is optional. Example: “bottles x 250 mL”, “250mg/5mL — 10 mL given”.
                        </p>
                    </div>

                    {{-- Supplies (Dynamic) --}}
                    <div
                        x-data='{
                            supply_name: "",
                            supply_qty: "",
                            supplies: @json(old("supplies", $consultation->supplies ?? [])),

                            addSupply() {
                                const name = this.supply_name.trim();
                                const qtyRaw = String(this.supply_qty).trim();

                                if (!name || !qtyRaw) return;

                                const qty = parseInt(qtyRaw, 10);
                                if (!Number.isFinite(qty) || qty <= 0) return;

                                this.supplies.push({ name, qty });

                                this.supply_name = "";
                                this.supply_qty = "";
                            },

                            removeSupply(i) {
                                this.supplies.splice(i, 1);
                            }
                        }'
                    >
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2 mt-3">
                            Supplies Used
                        </label>

                        {{-- Pills --}}
                        <div class="flex flex-wrap gap-2 mb-2 p-2 border border-dashed border-neutral-300 dark:border-neutral-600 rounded-md min-h-[2.5rem]">
                            <template x-if="supplies.length === 0">
                                <span class="text-xs text-neutral-400">Add supply item(s) here...</span>
                            </template>

                            <template x-for="(supply, index) in supplies" :key="index">
                                <div class="flex items-center gap-2 border border-neutral-300 dark:border-neutral-600 bg-neutral-100 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100 px-3 py-1 rounded-full text-xs">
                                    <span x-text="`${supply.name} (${supply.qty})`"></span>

                                    <button type="button" @click="removeSupply(index)" class="hover:text-red-600">
                                        ✕
                                    </button>

                                    {{-- Hidden inputs posted to controller --}}
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

                        {{-- Inputs --}}
                        <div class="flex flex-col md:flex-row gap-2">
                            <input type="text"
                                x-model="supply_name"
                                placeholder="Supply name"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500 md:w-56">

                            <input type="number"
                                min="1"
                                x-model="supply_qty"
                                placeholder="Qty"
                                class="w-full md:w-28 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500">

                            <button type="button"
                                    @click="addSupply()"
                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Add
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2 mt-3">
                            Remarks
                        </label>
                        <textarea name="remarks" rows="3"
                                  class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                         dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                                         focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('remarks', $consultation->remarks) }}</textarea>
                    </div>

                  @php
                    $photos = is_array($consultation->photo_attachments) ? $consultation->photo_attachments : [];
                @endphp

                <div>
                
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200 mb-2 mt-3">
                    Photos
                </label>

                <input
                    x-ref="fileInput"
                    type="file"
                    name="photo_attachments[]"
                    accept="image/*"
                    multiple
                    @change="handleFiles($event)"
                    class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                        dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-600
                        focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />

                <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                    Existing: check “Remove” to delete on save. New: check “Remove” to exclude from upload on save.
                </p>

                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">

                    {{-- Existing photos (server-side remove) --}}
                    @foreach($photos as $photo)
                    @php
                        $url = \Illuminate\Support\Facades\Storage::disk('private_s3')
                        ->temporaryUrl($photo, now()->addMinutes(30));
                    @endphp

                    <label class="block border border-neutral-200 dark:border-neutral-700 rounded overflow-hidden bg-white dark:bg-neutral-900 hover:shadow-md transition cursor-pointer">
                        <div class="aspect-square bg-neutral-100 dark:bg-neutral-800">
                        <img src="{{ $url }}" alt="Consultation photo" class="w-full h-full object-cover">
                        </div>

                        <div class="p-2 flex items-center gap-2">
                        <input type="checkbox"
                                name="remove_existing_photos[]"
                                value="{{ $photo }}"
                                class="rounded border-neutral-300 dark:border-neutral-600">
                        <span class="text-xs text-neutral-700 dark:text-neutral-300">Remove</span>
                        </div>
                    </label>
                    @endforeach

                    {{-- New previews (mark for removal; remove applied on submit) --}}
                    <template x-for="(p, i) in previews" :key="p.id">
                    <label class="block border border-neutral-200 dark:border-neutral-700 rounded overflow-hidden bg-white dark:bg-neutral-900 hover:shadow-md transition cursor-pointer">
                        <div class="aspect-square bg-neutral-100 dark:bg-neutral-800 relative">
                        <img :src="p.url" :alt="p.name" class="w-full h-full object-cover">

                        <!-- Optional overlay when marked remove -->
                        <div x-show="p.remove"
                            class="absolute inset-0 bg-black/40 flex items-center justify-center text-white text-xs font-semibold">
                            MARKED FOR REMOVAL
                        </div>
                        </div>

                        <div class="p-2 flex items-center gap-2">
                        <input type="checkbox"
                                x-model="p.remove"
                                class="rounded border-neutral-300 dark:border-neutral-600">
                        <span class="text-xs text-neutral-700 dark:text-neutral-300 truncate" x-text="p.name"></span>
                        </div>
                    </label>
                    </template>

                </div>
                </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-2 mt-3">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 transition">
                            Update Consultation
                        </button>

                        <a href="{{ $backUrl }}"
                           class="inline-flex items-center justify-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50
                                  dark:border-neutral-600 dark:text-neutral-200 dark:hover:bg-neutral-800 transition">
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-layouts.app>

<script>
function photoUploadHandler() {
    return {
        previews: [],
        fileInput: null,

        init() {
            this.fileInput = this.$refs.fileInput;
        },

        handleFiles(e) {
            const files = Array.from(e.target.files || []);

            const newPreviews = files
                .filter(file => file && file.type && file.type.startsWith('image/'))
                .map(file => ({
                    id: crypto.randomUUID(),
                    name: file.name,
                    url: URL.createObjectURL(file),
                    file: file,
                    remove: false
                }));

            // Add new selected files instead of replacing previous previews
            this.previews.push(...newPreviews);

            // Clear the native file input so the user can select more files again
            e.target.value = '';
        },

        applyNewRemovals() {
            const dt = new DataTransfer();

            this.previews
                .filter(photo => !photo.remove)
                .forEach(photo => dt.items.add(photo.file));

            this.fileInput.files = dt.files;
        },

        beforeSubmit(e) {
            this.applyNewRemovals();
        }
    }
}
</script>