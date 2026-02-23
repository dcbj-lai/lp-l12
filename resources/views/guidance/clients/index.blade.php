<x-layouts.app>

    <div 
        class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen"
        x-data="{
            search: '',
            students: [
                { id: 1, name: 'Airist Moncilla', email: 'airist.moncilla@laicollege.edu.ph' },
                { id: 2, name: 'Andie Jimeno', email: 'andie.jimeno@laicollege.edu.ph' },
                { id: 3, name: 'Brandon Liberia', email: 'brandon.liberia@laicollege.edu.ph' },
                { id: 4, name: 'Charise Laureanti', email: 'charise.laureanti@laicollege.edu.ph' },
                { id: 5, name: 'Christine Rivera', email: 'christine.rivera@laicollege.edu.ph' },
                { id: 6, name: 'Derick Tiu', email: 'derick.tiu@laicollege.edu.ph' },
                { id: 7, name: 'Earl Tomas', email: 'earl.tomas@laicollege.edu.ph' },
                { id: 8, name: 'Elisha Balane', email: 'elisha.balane@laicollege.edu.ph' },
                { id: 9, name: 'Emmanuel Cruz', email: 'emmanuel.cruz@laicollege.edu.ph' },
                { id: 10, name: 'Evan Daniel Royola', email: 'evan.royola@laicollege.edu.ph' }
            ],
            filteredStudents() {
                return this.students.filter(student =>
                    student.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    student.email.toLowerCase().includes(this.search.toLowerCase())
                )
            }
        }"
    >

        <!-- Breadcrumb -->
        <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
            Health & Wellness 
            <span class="mx-1">›</span>
            Guidance
            <span class="mx-1">›</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">
                Clients
            </span>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Guidance – Student Clients
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Official list of students under the Guidance Office.
            </p>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input 
                type="text"
                x-model="search"
                placeholder="Search students..."
                class="w-full md:w-1/3 px-4 py-2 rounded-lg border 
                       bg-white text-gray-900 border-gray-300
                       dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600
                       focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-lg shadow 
                    bg-white dark:bg-gray-800">

            <table class="min-w-full border-collapse">

                <!-- Head -->
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            ID
                        </th>
                        <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            Name
                        </th>
                        <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            Email
                        </th>
                        <th class="text-center px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            Action
                        </th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody class="text-gray-800 dark:text-gray-200">

                    <template x-for="student in filteredStudents()" :key="student.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                            <td class="px-4 py-2 border border-gray-200 dark:border-gray-600"
                                x-text="student.id"></td>

                            <td class="px-4 py-2 border border-gray-200 dark:border-gray-600"
                                x-text="student.name"></td>

                            <td class="px-4 py-2 border border-gray-200 dark:border-gray-600"
                                x-text="student.email"></td>

                            <td class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-center">
                                <a
                                    :href="`/guidance/clients/${student.id}`"
                                    class="px-3 py-1 rounded-md text-sm font-medium
                                           bg-indigo-600 text-white
                                           hover:bg-indigo-700
                                           dark:bg-indigo-500 dark:hover:bg-indigo-600
                                           transition"
                                >
                                    View Details
                                </a>
                            </td>

                        </tr>
                    </template>

                    <!-- No Results -->
                    <tr x-show="filteredStudents().length === 0">
                        <td colspan="4" 
                            class="text-center py-4 text-gray-500 dark:text-gray-400">
                            No students found.
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</x-layouts.app>
