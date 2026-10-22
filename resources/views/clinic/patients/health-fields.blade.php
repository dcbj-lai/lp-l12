<div class="space-y-4">
    <fieldset class="space-y-3" x-data="{
        entries: @js(array_values(old('allergies', isset($patient) ? ($patient->allergies ?? []) : []))),
        draft: '',
        add() {
            const value = this.draft.trim();
            if (!value || this.entries.length >= 50) return;
            if (!this.entries.some(entry => String(entry).toLowerCase() === value.toLowerCase())) this.entries.push(value);
            this.draft = '';
            this.$refs.allergyInput.focus();
        }
    }">
        <legend class="text-sm font-medium text-neutral-700 dark:text-neutral-200">Allergies</legend>
        <input type="hidden" name="allergies_present" value="1">
        <p id="allergy-help" class="text-xs text-neutral-500 dark:text-neutral-400">Type an allergy and press Enter or Add allergy. Include the reaction if known. An empty list means no allergies have been recorded.</p>
        <ul class="flex flex-wrap gap-1.5" aria-label="Recorded allergies">
            <template x-for="(entry, index) in entries" :key="index">
                <li class="inline-flex max-w-full items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                    <input type="hidden" name="allergies[]" :value="entry">
                    <span class="min-w-0 break-words" x-text="entry"></span>
                    <button type="button" @click="entries.splice(index, 1)" :aria-label="'Remove allergy: ' + entry" class="shrink-0 rounded-full px-1 font-semibold hover:bg-red-200 dark:hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500"><span aria-hidden="true">×</span></button>
                </li>
            </template>
        </ul>
        <div class="flex flex-col gap-2 sm:flex-row">
            <input x-ref="allergyInput" type="text" name="allergies[]" x-model="draft" @keydown.enter.prevent="add()" :disabled="entries.length >= 50" aria-label="New allergy" aria-describedby="allergy-help" maxlength="255" placeholder="e.g., Penicillin — rash"
                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="button" @click="add()" :disabled="!draft.trim() || entries.length >= 50" class="shrink-0 rounded-md bg-indigo-600 hover:bg-indigo-700 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">Add allergy</button>
        </div>
        <p x-show="entries.length >= 50" x-cloak class="text-xs text-neutral-500 dark:text-neutral-400">Maximum of 50 allergies reached.</p>
        @error('allergies')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        @foreach($errors->get('allergies.*') as $messages)@foreach($messages as $message)<p class="text-sm text-red-600">{{ $message }}</p>@endforeach@endforeach
    </fieldset>
    <div>
        <label for="medical_history" class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Medical History</label>
        <textarea id="medical_history" name="medical_history" rows="5" maxlength="10000" placeholder="Past conditions, surgeries, hospitalizations, and relevant dates or notes"
            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('medical_history', $patient->medical_history ?? '') }}</textarea>
        @error('medical_history')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
