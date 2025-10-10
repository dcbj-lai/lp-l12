<?php

use App\Models\Step;
use App\Models\Ripple;
use Livewire\Volt\Volt;
use App\Mail\RequestMade;
use App\Livewire\RippleFeed;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StepController;
use App\Http\Controllers\UserController;
use App\Livewire\Attendance\MyAttendance;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UtilityController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\OrgSettingController;
use App\Http\Controllers\VisitorLogController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');
Route::get('dashboard', function () {
    return view('dashboard');
})
->middleware(['auth', 'verified'])
->name('dashboard');


Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

// Google Routes
Route::controller(GoogleController::class)->group(function() {
    Route::get('auth/google', 'googleLogin')->name('auth.google');
    Route::get('auth/google-callback', 'googleAuthenticate')->name('auth.google-callback');
});

Route::middleware(['auth', 'can:is-admin'])->group(function () {
    Route::get('/google-credentials', [GoogleController::class, 'showForm'])->name('google.credentials.form');
    Route::post('/google-credentials', [GoogleController::class, 'upload'])->name('google.credentials.upload');
});

/**Attendance routes */
Route::middleware('auth')->group(function () {
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.check_in');

    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->name('attendance.check_out');
    Route::get('/attendance', [AttendanceController::class, 'index'])
    ->middleware('can:is-pnc')
    ->name('attendance.index');
    Route::get('/attendance/week', [AttendanceController::class, 'week'])->name('attendance.week');

});
Route::get('/my-attendance',[AttendanceController::class, 'myAttendance'])
    ->middleware('auth')
    ->name('attendance.my_attendance');


 // HR-Only Routes
Route::middleware(['auth','can:is-pnc'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}/payroll', [UserController::class, 'togglePayroll'])->name('users.togglePayroll');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update'); 
});


// Finance Routes
Route::middleware(['auth', 'can:is-finance'])->group(function () {
    Route::get('/payouts', [PayrollController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/{control_number}/edit', [PayrollController::class, 'edit'])->name('payouts.edit');
    Route::post('/payouts', [PayrollController::class, 'store'])->name('payouts.store');
    Route::post('/payroll/generate/{control_number}', [PayrollController::class, 'generate'])->name('payroll.generate');

    Route::post('/users/{user}/adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
    Route::delete('/adjustments/{adjustment}', [AdjustmentController::class, 'destroy'])->name('adjustments.destroy');

    Route::post('/adjustments/package', [AdjustmentController::class, 'applyPackage'])->name('adjustments.package');
    Route::delete('/adjustments/package', [AdjustmentController::class, 'applyPackage'])->name('adjustments.package');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/payslips', [PayrollController::class, 'userPayslips'])->name('payslips.index');
    Route::get('/payslips/{payslip}', [PayrollController::class, 'showPayslip'])->name('payslips.show');
    Route::get('/payslips/{payslip}/download', [PayrollController::class, 'downloadPayslip'])->name('payslips.download');
    
});


/**Steps */


Route::middleware('auth')->group(function () {
    Route::get('/my-steps', [StepController::class, 'index'])->name('my-steps.index');
    Route::post('/my-steps', [StepController::class, 'store'])->name('my-steps.store');
});


Route::get('/steps', fn() => view('steps.index'))
    ->name('steps.index')
    ->middleware('auth');

// About

Route::get('/about', [UtilityController::class, 'about'])->name('about');

// Requests

Route::middleware(['auth'])->group(function () {
    Route::get('/my-requests', [RequestController::class, 'index'])->name('my-requests');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::delete('/requests/{request}', [RequestController::class, 'destroy'])->name('requests.destroy');
    Route::get('/requests/view/{requestModel}', [RequestController::class, 'view'])->name('requests.view');
    Route::put('/requests/{requestModel}', [RequestController::class, 'update'])->name('requests.update');

});

Route::middleware(['auth', 'can:is-manager-or-hr'])->group(function () {
    Route::get('/requests/manage', [RequestController::class, 'manage'])->name('requests.manage');
    Route::post('/requests/{request}/process', [RequestController::class, 'process'])->name('requests.process');
    Route::get('/requests/{request}', [RequestController::class, 'show'])->name('requests.show');
});

Route::middleware(['auth'])->group(function () {
    Route::put('/requests/{request}/archive', [RequestController::class, 'archive'])
        ->name('requests.archive');
});


Route::middleware(['auth', 'can:is-pnc'])->group(function () {
    // Initialize leave credits for all users
    Route::post('/initiate-leave', [RequestController::class, 'initiateLeave'])
        ->name('org-settings.initiate-leave');

    // Org settings
    Route::get('/org-settings', [OrgSettingController::class, 'index'])
        ->name('org-settings.index');
    Route::post('/org-settings', [OrgSettingController::class, 'update'])
        ->name('org-settings.update');

    Route::put('/users/{user}/leave-credits', [UserController::class, 'updateLeaveCredits'])
    ->name('users.leave-credits.update');

});



Route::get('/users/{user}/delete', [UserController::class, 'delete'])
    ->middleware(['auth', 'can:is-super-admin'])
    ->name('users.delete');

Route::get('/requests/{request}/delete', [RequestController::class, 'forceDestroy'])
    ->middleware('can:is-super-admin')
    ->name('requests.forceDestroy.get');
    
Route::get('/frontdesk/visitors/{visitor}/delete', [VisitorLogController::class, 'visitorDestroy'])
    ->middleware('can:is-super-admin')
    ->name('requests.visitorDestroy.get');

Route::get('/frontdesk/visitors/clean-up', [VisitorLogController::class, 'cleanUp'])
    ->middleware(['auth', 'can:is-super-admin']);


/**Visitor */


Route::get('/visitor/start', [VisitorLogController::class, 'showStart'])->name('visitor.start');
Route::post('/visitor/start', [VisitorLogController::class, 'sendOtp'])->name('visitor.sendOtp');
Route::post('/visitor/verify', [VisitorLogController::class, 'verifyOtp'])->name('visitor.verifyOtp');

Route::get('/visitor/form/{id}', [VisitorLogController::class, 'showForm'])->name('visitor.form');
Route::post('/visitor/form/{id}', [VisitorLogController::class, 'submitForm'])->name('visitor.form.submit');

Route::get('/visitor/thankyou', function () {
    return view('visitor.thankyou');
})->name('visitor.thankyou');

Route::middleware(['auth', 'can:is-frontdesk'])->group(function () {
    // Route::get('/visitors/verify/{batch_id}', [VisitorLogController::class, 'showValidQr'])
    // ->name('visitors.verify');
    Route::get('/visitors/{visitor_id}/verify/{batch_id}', [VisitorLogController::class, 'showValidQr'])
    ->name('visitors.verify');
    Route::get('frontdesk/visitors', [VisitorLogController::class, 'frontdeskIndex'])->name('frontdesk.visitors');
    Route::post('frontdesk/visitors/{visitor}/endorse', [VisitorLogController::class, 'endorse'])->name('frontdesk.endorse');
    Route::post('frontdesk/visitors/{visitor}/checkin', [VisitorLogController::class, 'checkIn'])->name('frontdesk.checkin');
    Route::post('frontdesk/visitors/{visitor}/checkout', [VisitorLogController::class, 'checkOut'])->name('frontdesk.checkout');
    Route::get('/frontdesk/visitors/csv', [VisitorLogController::class, 'downloadCsv'])
    ->name('frontdesk.visitors.csv')
    ->middleware('auth');

});


Route::middleware(['auth'])->group(function () {
    Route::post('visitor/{visitor}/approve', [VisitorLogController::class, 'approveVisit'])
        ->name('visitor.approve');
});


// Visited-user routes
Route::middleware(['auth'])->group(function () {
    // Logged-in user's own visitor logs
    Route::get('/visitors/mine', [VisitorLogController::class, 'mine'])
        ->name('visitors.mine');

    // View a single visitor's details
    Route::get('/visitors/{visitor}', [VisitorLogController::class, 'showVisitor'])
        ->name('visitors.show');

    Route::get('/visitors/preapproved/create', [VisitorLogController::class, 'createPreapproved'])
    ->name('visitors.create-preapproved');

});


Route::get('/frontdesk/visitors/{visitor}', [VisitorLogController::class, 'show'])
    ->name('frontdesk.visitors.show');

// Pre-approved visit creation (by visited user)
Route::get('/visitors/pre-approve', [VisitorLogController::class, 'createPreApproved'])
    ->middleware('auth')
    ->name('visitors.preapprove.create');

Route::post('/visitors/pre-approve', [VisitorLogController::class, 'storePreApproved'])
    ->middleware('auth')
    ->name('visitors.preapprove.store');

Route::delete('/visitors/cancel-batch/{batchId}', [VisitorLogController::class, 'cancelBatch'])
    ->name('visitor.cancel-batch');

// Test

Route::get('/phpinfo', function () {
    phpinfo();
});

Route::get('/test-qr', function () {
    $data = 'https://lifeacademy.ph/visitors/checkin?id=12345'; // sample data

    $qr = base64_encode(QrCode::format('png')->size(200)->generate($data));

    return view('test-qr', ['qr' => $qr, 'data' => $data]);
});

require __DIR__.'/auth.php';
