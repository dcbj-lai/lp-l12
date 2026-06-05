<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 28px 34px 30px;
        }

        body {
            background: #ffffff;
            color: #1f1f1f;
            font-family: DejaVu Serif, serif;
            font-size: 11.5px;
            line-height: 1.5;
            margin: 0;
        }

        .top-rule {
            background: #9E1D20;
            height: 10px;
            margin-bottom: 14px;
            width: 100%;
        }

        .header-table {
            border-bottom: 2px solid #9E1D20;
            margin-bottom: 18px;
            padding-bottom: 12px;
            width: 100%;
        }

        .logo-cell {
            width: 102px;
        }

        .logo {
            height: 96px;
            width: auto;
        }

        .school-name {
            color: #690F0D;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0;
            line-height: 1.1;
            margin: 0;
            text-transform: uppercase;
        }

        .tagline {
            color: #9E1D20;
            font-size: 12px;
            font-weight: bold;
            margin-top: 4px;
        }

        .address {
            color: #4b5563;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            margin-top: 5px;
        }

        .document-label {
            background: #F2E8DC;
            border: 1px solid #C9C9C9;
            color: #690F0D;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            font-weight: bold;
            padding: 6px 8px;
            text-align: center;
            text-transform: uppercase;
            width: 150px;
        }

        h1 {
            color: #690F0D;
            font-size: 19px;
            margin: 0 0 12px;
            text-transform: uppercase;
        }

        .intro {
            background: #F2E8DC;
            border-left: 5px solid #9E1D20;
            color: #2f2f2f;
            font-family: DejaVu Sans, sans-serif;
            margin: 0 0 16px;
            padding: 10px 12px;
        }

        .section {
            margin-top: 16px;
        }

        .label {
            color: #690F0D;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .value {
            border-bottom: 1px solid #C9C9C9;
            min-height: 18px;
            padding: 2px 0 3px;
        }

        .grid {
            border-collapse: collapse;
            width: 100%;
        }

        .grid td {
            padding: 6px 8px 8px 0;
            vertical-align: top;
            width: 50%;
        }

        .box {
            border: 1px solid #C9C9C9;
            min-height: 72px;
            padding: 8px;
            white-space: pre-line;
        }

        .statement {
            border: 1px solid #9E1D20;
            color: #2f2f2f;
            font-family: DejaVu Sans, sans-serif;
            margin-top: 18px;
            padding: 12px;
        }

        .signature {
            margin-top: 58px;
            text-align: right;
        }

        .line {
            border-top: 1px solid #690F0D;
            display: inline-block;
            padding-top: 5px;
            text-align: center;
            width: 240px;
        }

        .signatory-name {
            color: #690F0D;
            font-weight: bold;
            text-transform: uppercase;
        }

        .footer {
            border-top: 1px solid #C9C9C9;
            color: #6b7280;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin-top: 26px;
            padding-top: 8px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="top-rule"></div>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/lci-logo.png') }}" class="logo" alt="Life College International Logo">
            </td>
            <td>
                <p class="school-name">Life College International</p>
                <div class="tagline">Learn and Live Fully.</div>
                <div class="address">
                    LAI College, Ortigas East, Ortigas Ave. cor. C-5 Road, Ugong, Pasig City, Metro Manila, Philippines
                </div>
            </td>
            <td align="right">
                <div class="document-label">Clinic Office<br>Medical Clearance</div>
            </td>
        </tr>
    </table>

    <h1>Pre-enrollment Medical Clearance</h1>

    <div class="intro">
        This form is issued for admission processing and confirms the applicant's pre-enrollment medical assessment status.
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="label">Applicant Name</div>
                <div class="value">{{ $clearance->applicant_name }}</div>
            </td>
            <td>
                <div class="label">Intended Course / Program</div>
                <div class="value">{{ $clearance->intended_course ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Email</div>
                <div class="value">{{ $clearance->email ?? '-' }}</div>
            </td>
            <td>
                <div class="label">Contact Number</div>
                <div class="value">{{ $clearance->contact_number ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Assessment Date</div>
                <div class="value">{{ $clearance->assessment_date?->format('F d, Y') ?? '-' }}</div>
            </td>
            <td>
                <div class="label">Clearance Status</div>
                <div class="value">{{ $clearance->statusLabel() }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="label">Findings</div>
        <div class="box">{{ $clearance->findings ?: '-' }}</div>
    </div>

    <div class="section">
        <div class="label">Recommendations / Pending Requirements</div>
        <div class="box">{{ $clearance->recommendations ?: '-' }}</div>
    </div>

    <div class="statement">
        This certifies that the applicant named above has undergone pre-enrollment medical assessment by the Clinic Office.
        The clearance status indicated in this form is based on the information and findings recorded as of the assessment date.
    </div>

    <div class="signature">
        <div class="line">
            <span class="signatory-name">{{ $clearance->signatoryName() }}</span><br>
            Clinic Admin Signature
        </div>
    </div>

    <div class="footer">
        Issued {{ $clearance->issued_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }} |
        Life College International - Vita Abundans MMXXV
    </div>
</body>

</html>
