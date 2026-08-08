* { box-sizing: border-box; }

        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #1c3a2c;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            background-color: #eef1f4;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 34px 40px 30px;
        }

        .text-light { color: #4b7c63; }

        .letterhead {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .letterhead td { padding: 0; vertical-align: top; }
        .letterhead .col-date { width: 200px; text-align: right; }

        .brand-logo-img {
            max-height: 52px;
            max-width: 260px;
            display: block;
            margin-bottom: 8px;
        }

        .brand-logo-fallback {
            display: inline-block;
            font-weight: bold;
            font-size: 13px;
            letter-spacing: 0.5px;
            color: #047857;
            border: 1.5px dashed #059669;
            border-radius: 4px;
            padding: 10px 16px;
            margin-bottom: 8px;
        }

        .brand-contact {
            font-size: 10px;
            color: #4b7c63;
            margin-top: 2px;
            line-height: 1.6;
            max-width: 280px;
        }

        .print-date {
            font-size: 11px;
            color: #4b7c63;
            white-space: nowrap;
            padding-top: 4px;
        }
        .print-date strong { color: #1c3a2c; }

        .title-banner {
            background-color: #047857;
            border-left: 5px solid #f59e0b;
            padding: 16px 20px;
            margin-bottom: 22px;
        }
        .title-banner h1 {
            margin: 0 0 4px 0;
            font-size: 17px;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: #ffffff;
            text-transform: uppercase;
        }
        .title-banner p {
            margin: 0;
            font-size: 10.5px;
            color: #e6f3ee;
            max-width: 560px;
            line-height: 1.5;
        }
        .title-banner .badge-row { margin-top: 10px; }
        .badge {
            display: inline-block;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #1c3a2c;
            background-color: #f59e0b;
            border: 1px solid #d97706;
            border-radius: 3px;
            padding: 3px 8px;
            margin-right: 6px;
            text-transform: uppercase;
        }

        .apotek-section { margin-bottom: 34px; page-break-inside: avoid; }

        .apotek-header {
            background-color: #f0fdf4;
            border-left: 4px solid #f59e0b;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .apotek-header-table { width: 100%; border-collapse: collapse; }
        .apotek-header-table td { padding: 0; vertical-align: middle; }

        .apotek-index {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9.5px;
            font-weight: bold;
            color: #059669;
            background-color: #ffffff;
            border: 1px solid #059669;
            border-radius: 3px;
            padding: 3px 7px;
            white-space: nowrap;
        }
        .col-index { width: 78px; }

        .apotek-title {
            font-size: 13.5px;
            font-weight: bold;
            color: #047857;
            display: block;
        }
        .apotek-subtitle { color: #4b7c63; font-size: 10px; display: block; margin-top: 1px; }

        .col-stats {
            width: 130px;
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            color: #1c3a2c;
        }
        .stat-value { color: #b45309; font-weight: bold; }
        .stat-label { color: #4b7c63; font-family: 'Helvetica', Arial, sans-serif; font-size: 9px; }
        .stat-block { margin-bottom: 4px; }
        .stat-block:last-child { margin-bottom: 0; }

        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items-table thead { display: table-header-group; }
        table.items-table tr { page-break-inside: avoid; }

        table.items-table th, table.items-table td {
            padding: 9px 8px;
            text-align: left;
            border-bottom: 1px solid #d7e6dc;
        }
        table.items-table th {
            background-color: #059669;
            color: #ffffff;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }
        table.items-table tbody tr:nth-child(even) { background-color: #f3faf6; }

        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; color: #1c3a2c; font-weight: bold; font-size: 11px; }

        .fill-box {
            display: inline-block;
            width: 100%;
            height: 22px;
            background-color: #fdfaf3;
            border: 1px dashed #e0c088;
            border-radius: 2px;
        }

        .notes-box { margin-bottom: 16px; }
        .notes-label {
            font-size: 9.5px;
            font-weight: bold;
            color: #4b7c63;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .notes-lines {
            height: 40px;
            border: 1px dashed #e0c088;
            border-radius: 2px;
            background-color: #fdfaf3;
        }

        .final-signoff {
            margin-top: 10px;
            padding-top: 18px;
            border-top: 1.5px solid #d7e6dc;
            page-break-inside: avoid;
        }
        .final-signoff-label {
            font-size: 9.5px;
            font-weight: bold;
            color: #4b7c63;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: center;
            margin-bottom: 4px;
        }
        .final-signoff-sub {
            font-size: 9.5px;
            color: #4b7c63;
            text-align: center;
            margin-bottom: 16px;
        }

        .signatures { width: 100%; border-collapse: collapse; }
        .signatures td { width: 50%; vertical-align: top; padding: 0; }
        .signatures td.sig-left { padding-right: 14px; }
        .signatures td.sig-right { padding-left: 14px; }

        .sig-title { font-size: 10.5px; color: #4b7c63; margin-bottom: 55px; text-align: center; }
        .sig-line { border-bottom: 1px solid #1c3a2c; margin-bottom: 5px; width: 85%; margin-left: auto; margin-right: auto; }
        .sig-name { font-size: 10.5px; font-weight: bold; color: #1c3a2c; text-align: center; }
        .sig-date { font-size: 9.5px; color: #4b7c63; text-align: center; margin-top: 6px; }

        .doc-footer {
            margin-top: 26px;
            padding-top: 12px;
            border-top: 1px solid #d7e6dc;
            text-align: center;
            font-size: 9px;
            color: #4b7c63;
        }

        @media print {
            @page { size: A4; margin: 12mm 14mm; }
            body { background-color: white; padding: 0; }
            .print-container { padding: 0; max-width: 100%; }
            * { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; print-color-adjust: exact !important; }
        }
