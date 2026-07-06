<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitorLogController;
use App\Http\Controllers\AccessRoleController;
use App\Http\Controllers\FacilityReservationController;
use App\Http\Controllers\LeaveCreditController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'active.user']);

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    Route::get('/users', [UserController::class, 'apiIndex'])
        ->middleware('permission:users.list')
        ->name('users.api.index');

    Route::post('/users/employee-numbers/backfill', [UserController::class, 'apiBackfillEmployeeNumbers'])
        ->middleware('permission:users.edit')
        ->name('users.api.employee-numbers.backfill');

    Route::post('/users/avatar', [UserController::class, 'apiUpdateAvatar'])
        ->middleware('permission:users.edit')
        ->name('users.api.avatar.update');

    Route::get('/leave-credits', [LeaveCreditController::class, 'apiIndex'])
        ->middleware('permission:leave-credits.view')
        ->name('leave-credits.api.index');

    Route::get('/leave-requests', [RequestController::class, 'apiIndex'])
        ->middleware('permission:requests.hr.view')
        ->name('leave-requests.api.index');

    Route::post('/leave-requests/{requestModel}/reject-carry-over', [RequestController::class, 'apiRejectCarryOver'])
        ->middleware('permission:requests.hr.view')
        ->name('leave-requests.api.reject-carry-over');

    Route::post('/leave-requests/{requestModel}/approve-carry-over', [RequestController::class, 'apiApproveCarryOver'])
        ->middleware('permission:requests.hr.view')
        ->name('leave-requests.api.approve-carry-over');

    Route::middleware('role:facility.admin|facility.approver')
        ->prefix('facility-reservations')
        ->name('facility-reservations.api.')
        ->group(function () {
            Route::get('/', [FacilityReservationController::class, 'index'])->name('index');
            Route::get('/{reservation}', [FacilityReservationController::class, 'show'])->name('show');
            Route::post('/{reservation}/approve', [FacilityReservationController::class, 'approve'])->name('approve');
            Route::post('/{reservation}/reject', [FacilityReservationController::class, 'reject'])->name('reject');
            Route::post('/{reservation}/cleanup-google-calendar', [FacilityReservationController::class, 'cleanupGoogleCalendar'])
                ->name('cleanup-google-calendar');
        });

    Route::middleware('role:facility.admin')
        ->prefix('facility-reservations')
        ->name('facility-reservations.api.')
        ->group(function () {
            Route::post('/', [FacilityReservationController::class, 'store'])->name('store');
            Route::patch('/{reservation}', [FacilityReservationController::class, 'update'])->name('update');
            Route::put('/{reservation}', [FacilityReservationController::class, 'update'])->name('replace');
            Route::delete('/{reservation}', [FacilityReservationController::class, 'destroy'])->name('destroy');
        });

    Route::patch('/leave-credits', [LeaveCreditController::class, 'apiBulkUpdate'])
        ->middleware('permission:leave-credits.assign')
        ->name('leave-credits.api.bulk-update');

    Route::patch('/leave-credits/{user}', [LeaveCreditController::class, 'apiUpdate'])
        ->middleware('permission:leave-credits.assign')
        ->name('leave-credits.api.update');

    Route::middleware('role:access.admin')->group(function () {
        Route::get('/access/roles', [AccessRoleController::class, 'roles'])
            ->name('access.api.roles.index');

        Route::get('/users/{user}/roles', [AccessRoleController::class, 'userRoles'])
            ->name('users.api.roles.show');

        Route::post('/users/{user}/roles', [AccessRoleController::class, 'assign'])
            ->name('users.api.roles.assign');

        Route::put('/users/{user}/roles', [AccessRoleController::class, 'sync'])
            ->name('users.api.roles.sync');

        Route::delete('/users/{user}/roles/{role}', [AccessRoleController::class, 'revoke'])
            ->where('role', '.*')
            ->name('users.api.roles.revoke');
    });
});

Route::post('/webhook/preapproved-visitor', [VisitorLogController::class, 'webhookPreapproved']);
Route::post('/test', function () {
    return ['success' => true];
});
