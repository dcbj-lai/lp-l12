<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 26px 42px 42px;
            size: A4 portrait;
        }

        body {
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
            margin: 0;
        }

        .letterhead {
            margin-bottom: 12px;
            text-align: center;
        }

        .letterhead img {
            max-width: 455px;
            width: 100%;
        }

        .document-title {
            margin: 0 0 14px;
            text-align: center;
        }

        .document-title h1 {
            font-family: DejaVu Serif, serif;
            font-size: 20px;
            line-height: 1.1;
            margin: 0;
        }

        .document-title div {
            font-family: DejaVu Serif, serif;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .section {
            margin-top: 12px;
        }

        .section-title {
            border-bottom: 1px solid #111;
            font-family: DejaVu Serif, serif;
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 8px;
            padding-bottom: 3px;
            text-transform: uppercase;
        }

        .details {
            border-collapse: collapse;
            width: 100%;
        }

        .details td {
            padding: 5px 7px;
            vertical-align: top;
        }

        .details .label {
            color: #333;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            width: 27%;
        }

        .details .value {
            border-bottom: 1px solid #c7c7c7;
            min-height: 15px;
            width: 73%;
        }

        .two-column {
            border-collapse: collapse;
            width: 100%;
        }

        .two-column td {
            padding: 0;
            vertical-align: top;
            width: 50%;
        }

        .two-column td:first-child {
            padding-right: 10px;
        }

        .two-column td:last-child {
            padding-left: 10px;
        }

        .recommendation {
            border-collapse: collapse;
            margin-bottom: 6px;
            width: 100%;
        }

        .recommendation td {
            padding: 2px 0;
            vertical-align: top;
        }

        .check {
            border: 1px solid #111;
            display: inline-block;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            height: 9px;
            line-height: 8px;
            text-align: center;
            width: 9px;
        }

        .check-cell {
            width: 18px;
        }

        .note-block {
            background: #fafafa;
            border-left: 3px solid #9e1d20;
            margin: 5px 0 8px 18px;
            min-height: 22px;
            padding: 6px 8px;
            white-space: pre-line;
        }

        .muted {
            color: #666;
        }

        .certification {
            text-align: justify;
        }

        .signature-table {
            border-collapse: collapse;
            margin-left: auto;
            margin-top: 16px;
            width: 245px;
        }

        .signature-table td {
            padding: 3px 0;
        }

        .signature-line {
            border-bottom: 1px solid #111;
            padding: 0 4px 4px;
        }

        .signature-label {
            font-size: 9px;
            font-weight: bold;
            white-space: nowrap;
            width: 64px;
        }

        .signature-value {
            border-bottom: 1px solid #111;
            min-height: 13px;
            padding-left: 4px;
        }

        .footer-note {
            border-top: 1px solid #ccc;
            bottom: -26px;
            color: #555;
            font-size: 8px;
            font-style: italic;
            padding-top: 7px;
            position: fixed;
            text-align: justify;
            width: 100%;
        }
    </style>
</head>

<body>
    @php
        $status = $clearance->clearance_status;
        $isCleared = $status === \App\Models\PreEnrollmentMedicalClearance::STATUS_CLEARED;
        $hasConditions = $status === \App\Models\PreEnrollmentMedicalClearance::STATUS_CLEARED_WITH_CONDITIONS;
        $isPending = $status === \App\Models\PreEnrollmentMedicalClearance::STATUS_PENDING;
        $isNotCleared = $status === \App\Models\PreEnrollmentMedicalClearance::STATUS_NOT_CLEARED;
        $mark = fn (bool $checked) => $checked ? '&#10003;' : '';
        $findingsHeading = match ($status) {
            \App\Models\PreEnrollmentMedicalClearance::STATUS_CLEARED_WITH_CONDITIONS => 'Conditions / Restrictions',
            \App\Models\PreEnrollmentMedicalClearance::STATUS_NOT_CLEARED => 'Reason for Not Cleared',
            default => 'Findings',
        };
        $recommendationsHeading = $isPending ? 'Additional Requirements' : 'Recommendations and Follow-up Actions';
        $showFindings = $hasConditions || $isNotCleared || filled($clearance->findings);
        $showRecommendations = $isPending || filled($clearance->recommendations);
        $preparedDate = $clearance->issued_at?->format('F d, Y') ?? $clearance->assessment_date?->format('F d, Y') ?? now()->format('F d, Y');
    @endphp

    <div class="letterhead">
        <img src="{{ public_path('images/pre-enrollment-medclear-letterhead.png') }}" alt="Life College International">
    </div>

    <div class="document-title">
        <h1>School Clinic / Health Services</h1>
        <div>Pre-enrollment Medical Clearance</div>
    </div>

    <div class="section">
        <div class="section-title">Student Information</div>

        <table class="details">
            <tr>
                <td class="label">Student Name</td>
                <td class="value">{{ $clearance->applicant_name }}</td>
            </tr>
        </table>

        <table class="two-column">
            <tr>
                <td>
                    <table class="details">
                        <tr>
                            <td class="label">Program / Course</td>
                            <td class="value">{{ $clearance->intended_course ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="details">
                        <tr>
                            <td class="label">Year Level</td>
                            <td class="value">-</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="details">
                        <tr>
                            <td class="label">Mobile Number</td>
                            <td class="value">{{ $clearance->contact_number ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="details">
                        <tr>
                            <td class="label">Age / Sex</td>
                            <td class="value">-</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="details">
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $clearance->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Assessment Date</td>
                <td class="value">{{ $clearance->assessment_date?->format('F d, Y') ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Medical Clearance Recommendation</div>

        <table class="recommendation">
            <tr>
                <td class="check-cell"><span class="check">{!! $mark(true) !!}</span></td>
                <td>Submitted medical result from accredited clinic</td>
            </tr>
            <tr>
                <td class="check-cell"><span class="check">{!! $mark($isCleared) !!}</span></td>
                <td>Medically cleared for enrollment</td>
            </tr>
            <tr>
                <td class="check-cell"><span class="check">{!! $mark($hasConditions) !!}</span></td>
                <td>Medically cleared for enrollment subject to following conditions/restrictions</td>
            </tr>
            <tr>
                <td class="check-cell"><span class="check">{!! $mark($isPending) !!}</span></td>
                <td>Temporarily not cleared pending submission of additional requirements</td>
            </tr>
            <tr>
                <td class="check-cell"><span class="check">{!! $mark($isNotCleared) !!}</span></td>
                <td>Not cleared</td>
            </tr>
        </table>

        @if ($showFindings)
            <div class="section-title" style="border-bottom: none; margin-top: 8px;">{{ $findingsHeading }}</div>
            <div class="note-block" style="margin-left: 0;">{{ $clearance->findings ?: '-' }}</div>
        @endif

        @if ($showRecommendations)
            <div class="section-title" style="border-bottom: none; margin-top: 8px;">{{ $recommendationsHeading }}</div>
            <div class="note-block" style="margin-left: 0;">{{ $clearance->recommendations ?: '-' }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Certification</div>
        <div class="certification">
            Based on the medical documents submitted and the evaluation conducted by the School Health Services, the
            above-named student has been assessed in accordance with the institution's health requirements. This
            clearance is issued solely for enrollment purposes and shall form part of the student's official health
            record.
        </div>

        <table class="signature-table">
            <tr>
                <td colspan="2" style="font-weight: bold;">Prepared By:</td>
            </tr>
            <tr>
                <td class="signature-label">Name</td>
                <td class="signature-value">{{ $clearance->signatoryName() }}</td>
            </tr>
            <tr>
                <td class="signature-label">Role</td>
                <td class="signature-value">School Nurse</td>
            </tr>
            <tr>
                <td class="signature-label">Date</td>
                <td class="signature-value">{{ $preparedDate }}</td>
            </tr>
            <tr>
                <td class="signature-label">Signature</td>
                <td class="signature-line">&nbsp;</td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        This form is intended for official school enrollment and health record purposes only. Medical information
        contained herein shall be handled with appropriate confidentiality by authorized school personnel.
    </div>
</body>

</html>
