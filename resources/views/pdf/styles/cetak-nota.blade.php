@page {
            size: 58mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        body {
            width: 58mm;
            max-width: 58mm;
            margin: 0 auto;
            color: #000;
            background: #fff;
            font-size: 11px;
            line-height: 1.5;
        }

        .ticket {
            width: 48mm;
            max-width: 48mm;
            margin: 0 auto;
            padding: 7mm 0 6mm;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .store-name {
            font-size: 13.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 5px;
        }
        .store-desc {
            font-size: 9px;
            font-weight: 400;
            line-height: 1.5;
            letter-spacing: 0.2px;
        }

        .divider {
            border-top: 1px solid #000;
            margin: 10px 0;
        }

        .meta-info {
            font-size: 9px;
            display: flex;
            justify-content: space-between;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            table-layout: fixed;
        }
        td { vertical-align: top; padding: 3px 0; }
        .item-row td { padding-top: 9px; }
        .item-name {
            display: block;
            font-weight: 600;
            font-size: 11px;
            margin-bottom: 2px;
            word-wrap: break-word;
            letter-spacing: 0.1px;
        }
        .item-qty-price {
            font-size: 9.5px;
            font-weight: 400;
        }
        .item-subtotal {
            font-size: 10.5px;
            font-weight: 500;
        }

        .total-area { margin-top: 2px; }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            font-weight: 400;
            margin-bottom: 4px;
            letter-spacing: 0.2px;
        }
        .total-row.grand-total {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin: 8px 0 6px;
            padding-top: 8px;
            border-top: 1px solid #000;
        }

        .footer { margin-top: 14px; text-align: center; }
        .footer-greeting {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .footer-quote {
            font-size: 9px;
            font-weight: 400;
            line-height: 1.5;
        }

        ::-webkit-scrollbar { display: none; }
