<?php

use Livewire\Volt\Volt;
use Illuminate\Http\Request;
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
use App\Http\Controllers\OnlineDayController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\OrgSettingController;
use App\Http\Controllers\VisitorLogController;
use App\Http\Controllers\PasswordLoginController;
use App\Http\Controllers\PrivateRequestDocumentController;

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
Route::controller(GoogleController::class)->group(function () {
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


    Route::middleware(['can:is-pnc'])
        ->prefix('attendance')
        ->name('attendance.')
        ->controller(AttendanceController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{attendance}/edit', 'edit')->name('edit');
            Route::put('/{attendance}', 'update')->name('update');
        });


    // Scan check in/out
    Route::get('/qr_check_in/{token}', [AttendanceController::class, 'qrCheckIn'])->name('attendance.qr_check_in');
    Route::get('/qr_check_out/{token}', [AttendanceController::class, 'qrCheckOut'])->name('attendance.qr_check_out');

    Route::post('/attendance/qr/stop', [AttendanceController::class, 'stopQr'])
        ->name('attendance.qr.stop');


    // Confirmation view
    Route::get('/attendance/qr-result', function (Request $request) {
        return view('attendance.qr-result', [
            'status' => $request->get('status'),
            'message' => $request->get('message'),
        ]);
    })->name('attendance.qr-result');


});


Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])
    ->middleware('auth')
    ->name('attendance.my_attendance');


// HR-Only Routes
Route::middleware(['auth', 'can:is-pnc'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}/payroll', [UserController::class, 'togglePayroll'])->name('users.togglePayroll');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
});

// Acad Admin

Route::middleware(['auth', 'can:is-acad-admin'])->group(function () {
    Route::get('/attendance/qr', [AttendanceController::class, 'showQr'])
        ->name('attendance.show_qr');
});

// For declaring online days to allow teachers to check-in virtually

Route::middleware(['auth', 'can:is-acad-admin'])
    ->prefix('onlinedays')
    ->name('onlinedays.')
    ->controller(OnlineDayController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{onlineday}/edit', 'edit')->name('edit');
        Route::put('/{onlineday}', 'update')->name('update');
        Route::delete('/{onlineday}', 'destroy')->name('destroy');
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
    Route::get('/my-steps/{step}/edit', [StepController::class, 'edit'])->name('my-steps.edit');
    Route::put('/my-steps/{step}', [StepController::class, 'update'])->name('my-steps.update');
    Route::delete('/my-steps/{step}', [StepController::class, 'destroy'])
        ->name('my-steps.destroy');
});


Route::get('/steps', fn() => view('steps.index'))
    ->name('steps.index')
    ->middleware('auth');

// About

Route::get('/about', [UtilityController::class, 'about'])->name('about');


// HR Admin for Leaves
Route::middleware(['auth', 'can:is-pnc'])->group(function () {
    Route::get('/requests/manage-hr', [RequestController::class, 'manageHr'])
        ->name('requests.manage-hr');
    Route::get('/requests/hr/{requestModel}', [RequestController::class, 'showHr'])->name('requests.show-hr');
    Route::delete('/requests/purge-cancelled', [RequestController::class, 'purgeCancelled'])
        ->name('requests.purgeCancelled');
});



// HR Admin for Leaves


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

// Super Admin Clean up

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

Route::get('/attendance/{attendance}/delete', [AttendanceController::class, 'forceDestroy'])
    ->middleware('can:is-super-admin')
    ->name('attendance.forceDestroy.get');


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

// HR Admin for Leaves
Route::middleware(['auth', 'can:is-pnc'])->group(function () {
    Route::get('/requests/manage-hr', [RequestController::class, 'manageHr'])
        ->name('requests.manage-hr');

    Route::delete('/requests/purge-cancelled', [RequestController::class, 'purgeCancelled'])
        ->name('requests.purgeCancelled');
});
// HR Admin for Leaves

// Password login routes
Route::get('/pwd/login', [PasswordLoginController::class, 'create'])->name('pwd.login');
Route::post('/pwd/login', [PasswordLoginController::class, 'store'])->name('pwd.login.store');

require __DIR__ . '/auth.php';

// Document Controller

Route::get('/requests/private/{path}', [PrivateRequestDocumentController::class, 'show'])
    ->where('path', '.*')
    ->middleware(['auth'])
    ->name('requests.private');

Route::get(
    '/requests/offset-proof/{request}',
    [RequestController::class, 'previewOffsetProof']
)->middleware(['auth'])->name('requests.offset-proof');

Route::get('/requests/documents/{path}', [PrivateRequestDocumentController::class, 'show'])
    ->where('path', '.*')
    ->middleware('auth')
    ->name('requests.documents.show');

