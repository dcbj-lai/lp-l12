<x-layouts.app title="About Life Portal">
    <div class="max-w-4xl mx-auto py-10 px-6 space-y-10 text-gray-800 dark:text-gray-200">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-2 flex justify-center items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 stroke-current" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11.25 3L2.25 9l9 6 9-6-9-6zm0 0v6m9 3l-9 6-9-6" />
                </svg>
                About Life Portal
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Version <span class="font-semibold text-primary-600 dark:text-primary-400">v2.5.1</span>
            </p>
        </div>

        <!-- What is Life Portal -->
        <section class="space-y-4">
            <h2 class="text-2xl font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M3 12h16.5" />
                </svg>
                What is Life Portal?
            </h2>
            <p>
                Life Portal (LP) is Life Academy International College’s internal ERP system, built to streamline
                operations and automate core processes — from HR to finance and campus engagement.
            </p>
        </section>

        <!-- Who is it for -->
        <section class="space-y-4">
            <h2 class="text-2xl font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 7.5h3M19.5 7.5V9m-3-1.5h-1.5m0 0V6M15 12h3m-3 0v-1.5M15 12v1.5M3 12h6m-6 0V9m0 3v1.5M3 12H1.5m3 0h6m0 0v1.5m0-1.5V9" />
                </svg>
                Who is Life Portal for?
            </h2>
            <ul class="list-disc list-inside space-y-1 pl-4">
                <li><strong>Employees</strong> — Manage attendance, requests, and payslips.</li>
                <li><strong>HR</strong> — Monitor performance, leaves, and staff data.</li>
                <li><strong>Finance</strong> — Automate payroll cycles and compliance reports.</li>
                <li><strong>Admin</strong> — Centralize campus data and digital operations.</li>
            </ul>
        </section>

        <!-- Release Notes -->
        <section class="space-y-4">
            <h2 class="text-2xl font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-current text-primary-500" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6l3 3m6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Release Notes
            </h2>

            <div
                class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-5 shadow-sm">
                <!-- Current Version -->
                <div>
                    <h3 class="font-semibold text-lg text-primary-600 dark:text-primary-400">v2.5.1 <span
                            class="text-sm text-gray-500 dark:text-gray-400">– Oct 2025</span></h3>
                    <ul class="list-disc list-inside mt-2 space-y-1 text-sm">
                        <li>Added Google Drive storage for HR document uploads.</li>
                        <li>Refined visitor management QR check-in flow.</li>
                        <li>Improved request cancellation and notifications.</li>
                        <li>UI polishing across dark mode and mobile views.</li>
                    </ul>
                </div>

                <!-- Previous Version -->
                <div>
                    <h3 class="font-semibold text-lg text-primary-600 dark:text-primary-400">v2.4.9 <span
                            class="text-sm text-gray-500 dark:text-gray-400">– Aug 2025</span></h3>
                    <ul class="list-disc list-inside mt-2 space-y-1 text-sm">
                        <li>Payroll PDF redesign (elegant layout, better data grouping).</li>
                        <li>Added support for de minimis package presets in adjustments.</li>
                        <li>Improved request approval workflows.</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Closing Statement -->
        <section class="text-center mt-6 space-y-2">
            <h2 class="text-2xl font-semibold">Welcome to the Future of LAIC Operations!</h2>
            <p>Life Portal empowers every team to work smarter, faster, and more connected than ever.</p>
        </section>

        <!-- Button -->
        <div class="text-center mt-8">
            <flux:button variant="outline" href="{{ route('dashboard') }}">
                Go to Dashboard
            </flux:button>
        </div>

        <!-- Footer -->
        <footer class="mt-10 text-center text-gray-500 dark:text-gray-400 text-sm">
            <p>&copy; {{ date('Y') }} Life Academy International College. All rights reserved.</p>
            <p>Powered by the <span class="font-semibold">Digital Transformation Team</span> @ LAIC</p>
        </footer>
    </div>
</x-layouts.app>
