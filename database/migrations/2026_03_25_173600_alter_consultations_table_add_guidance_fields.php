<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'check_in_teacher')) {
                $table->string('check_in_teacher')->nullable()->after('time_out');
            }

            if (!Schema::hasColumn('consultations', 'check_in_teacher_email')) {
                $table->string('check_in_teacher_email')->nullable()->after('check_in_teacher');
            }

            if (!Schema::hasColumn('consultations', 'current_teacher')) {
                $table->string('current_teacher')->nullable()->after('check_in_teacher_email');
            }

            if (!Schema::hasColumn('consultations', 'teacher_email')) {
                $table->string('teacher_email')->nullable()->after('current_teacher');
            }

            if (!Schema::hasColumn('consultations', 'after_consultation')) {
                $table->string('after_consultation')->nullable()->after('teacher_email');
            }

            if (!Schema::hasColumn('consultations', 'going_home_method')) {
                $table->string('going_home_method')->nullable()->after('after_consultation');
            }

            if (!Schema::hasColumn('consultations', 'fetcher_name')) {
                $table->string('fetcher_name')->nullable()->after('going_home_method');
            }

            if (!Schema::hasColumn('consultations', 'self_approved_by')) {
                $table->string('self_approved_by')->nullable()->after('fetcher_name');
            }

            if (!Schema::hasColumn('consultations', 'type_of_session')) {
                $table->text('type_of_session')->nullable()->after('self_approved_by');
            }

            if (!Schema::hasColumn('consultations', 'risk_assessment')) {
                $table->text('risk_assessment')->nullable()->after('type_of_session');
            }

            if (!Schema::hasColumn('consultations', 'issue_concern')) {
                $table->text('issue_concern')->nullable()->after('risk_assessment');
            }

            if (!Schema::hasColumn('consultations', 'intervention')) {
                $table->text('intervention')->nullable()->after('issue_concern');
            }

            if (!Schema::hasColumn('consultations', 'remarks')) {
                $table->text('remarks')->nullable()->after('intervention');
            }

            if (!Schema::hasColumn('consultations', 'deleted_at')) {
                $table->softDeletes()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $columns = [
                'check_in_teacher',
                'check_in_teacher_email',
                'current_teacher',
                'teacher_email',
                'after_consultation',
                'going_home_method',
                'fetcher_name',
                'self_approved_by',
                'type_of_session',
                'risk_assessment',
                'issue_concern',
                'intervention',
                'remarks',
                'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('consultations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};