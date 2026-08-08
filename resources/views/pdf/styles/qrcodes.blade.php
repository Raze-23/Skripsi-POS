@page { size: A4; margin: 15mm 15mm 25mm 15mm; }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            text-align: center;
            background: #ffffff;
            color: #1c3a2c;
            margin: 0;
            font-size: 12px;
        }
        footer {
            position: fixed;
            bottom: -15px; left: 0; right: 0;
            height: 30px; padding-top: 10px;
            border-top: 1.5px solid #d1fae5;
            font-size: 9px; color: #059669;
            text-align: center;
        }

        .header { margin-bottom: 25px; }
        .logo { width: 110px; height: auto; margin-bottom: 8px; }
        .header h2 {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #047857; 
        }

        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px;
            table-layout: fixed;
        }
        .grid-td {
            width: 33.33%;
            vertical-align: top;
        }

        .qrcode-card {
            padding: 12px 10px;
            border: 1px solid #e5e7eb;
            border-top: 4px solid #059669; 
            border-radius: 8px;
            background-color: #ffffff;
            page-break-inside: avoid;
        }

        .prod-name {
            font-size: 10.5px;
            font-weight: 900;
            margin-bottom: 8px;
            height: 28px;
            overflow: hidden;
            text-transform: uppercase;
            color: #064e3b; 
            line-height: 1.3;
        }

        .qrcode-box {
            margin: 0 auto 6px auto;
            display: block;
            background: #ffffff;
            padding: 5px;
            border: 1px dashed #a7f3d0;
            border-radius: 6px;
            width: 90px;
            height: 90px;
        }
        .qrcode-box img {
            width: 100%;
            height: 100%;
        }

        .sku-text {
            font-size: 10px;
            color: #374151;
            margin-top: 2px;
            letter-spacing: 1px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }

        .expiry-box {
            margin-top: 10px;
            background-color: #fef3c7; 
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 6px 4px;
        }
        .expiry-label {
            display: block;
            font-size: 8.5px;
            color: #92400e; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .expiry-date {
            display: block;
            font-size: 12px;
            font-weight: 900;
            color: #b45309; 
        }
        .expiry-safe {
            color: #059669; 
        }
