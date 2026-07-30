<style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    .pos-shell {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        height: calc(100vh - 7rem);
        overflow: hidden;
    }

    .pos-left {
        flex: 1 1 0;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
        height: 100%;
        overflow: hidden;
    }

    .pos-search-wrap {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
    }

    .pos-search-input {
        flex: 1;
        background: #fff;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 12px 8px 36px;
        font-size: 13px;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        color: #1f2937;
    }

    .dark .pos-search-input {
        background: #111827;
        border-color: #374151;
        color: #f3f4f6;
    }

    .pos-search-input:focus {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, .12);
    }

    .pos-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        pointer-events: none;
    }

    .pos-scan-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #059669;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: background .15s, transform .1s;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .pos-scan-btn:hover {
        background: #047857;
    }

    .pos-scan-btn:active {
        transform: scale(.96);
    }

    .pos-product-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
        gap: 8px !important;
        overflow-y: auto;
        flex: 1;
        padding-right: 2px;
        align-content: start;
    }

    .pos-product-grid::-webkit-scrollbar {
        width: 3px;
    }

    .pos-product-grid::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 99px;
    }

    .dark .pos-product-grid::-webkit-scrollbar-thumb {
        background: #374151;
    }

    .product-card {
        background: #fff !important;
        border: 1.5px solid #f3f4f6 !important;
        border-radius: 10px !important;
        overflow: hidden !important;
        cursor: pointer !important;
        transition: transform .18s, box-shadow .18s, border-color .18s !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    .dark .product-card {
        background: #111827 !important;
        border-color: #1f2937 !important;
    }

    .product-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px -4px rgba(0, 0, 0, .12) !important;
        border-color: #a7f3d0 !important;
    }

    .product-card:active {
        transform: scale(.97) !important;
    }

    .product-img {
        position: relative !important;
        width: 100% !important;
        aspect-ratio: 1 / 1 !important;
        background: #f9fafb !important;
        overflow: hidden !important;
        flex-shrink: 0 !important;
        border-bottom: 1px solid #f3f4f6 !important;
    }

    .dark .product-img {
        background: #1f2937 !important;
        border-color: #374151 !important;
    }

    .product-img img {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        transition: transform .3s !important;
    }

    .product-card:hover .product-img img {
        transform: scale(1.05) !important;
    }

    .product-img .no-img {
        position: absolute !important;
        inset: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .product-body {
        padding: 6px 8px 7px;
        flex: 1;
    }

    .product-name {
        font-size: 11px;
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
    }

    .dark .product-name {
        color: #e5e7eb;
    }

    .product-card:hover .product-name {
        color: #059669;
    }

    .product-price {
        font-size: 11.5px;
        font-weight: 800;
        color: #059669;
        font-variant-numeric: tabular-nums;
    }

    .stock-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #fffdfd;
        color: rgb(255, 0, 0);
        font-size: 14px;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 999px;
        min-width: 70px;
        text-align: center;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
        z-index: 10;
    }

    .empty-state {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 16px;
        border: 2px dashed #e5e7eb;
        border-radius: 12px;
        color: #9ca3af;
        gap: 6px;
    }

    .dark .empty-state {
        border-color: #374151;
    }

    .pos-right {
        flex: 0 0 280px;
        width: 280px;
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1.5px solid #f3f4f6;
        border-radius: 14px;
        overflow: hidden;
    }

    .dark .pos-right {
        background: #111827;
        border-color: #1f2937;
    }

    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        border-bottom: 1px solid #f3f4f6;
        background: #fafafa;
        flex-shrink: 0;
    }

    .dark .cart-header {
        background: #0d1117;
        border-color: #1f2937;
    }

    .cart-header-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cart-icon-wrap {
        width: 28px;
        height: 28px;
        background: #ecfdf5;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dark .cart-icon-wrap {
        background: rgba(5, 150, 105, .15);
    }

    .cart-title {
        font-size: 13px;
        font-weight: 700;
        color: #1f2937;
    }

    .dark .cart-title {
        color: #f3f4f6;
    }

    .cart-count {
        font-size: 11px;
        color: #6b7280;
        font-weight: 600;
    }

    .cart-clear-btn {
        font-size: 11px;
        font-weight: 700;
        color: #ef4444;
        background: #fef2f2;
        border: none;
        padding: 3px 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: background .15s;
    }

    .cart-clear-btn:hover {
        background: #fee2e2;
    }

    .dark .cart-clear-btn {
        background: rgba(239, 68, 68, .12);
    }

    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 8px 10px;
    }

    .cart-items::-webkit-scrollbar {
        width: 3px;
    }

    .cart-items::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 99px;
    }

    .cart-empty {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        color: #9ca3af;
    }

    .cart-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 0;
        border-bottom: 1px solid #f9fafb;
    }

    .dark .cart-item {
        border-color: #1f2937;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .cart-item-info {
        flex: 1;
        min-width: 0;
    }

    .cart-item-name {
        font-size: 11.5px;
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dark .cart-item-name {
        color: #e5e7eb;
    }

    .cart-item-price {
        font-size: 10.5px;
        color: #9ca3af;
        font-variant-numeric: tabular-nums;
    }

    .qty-wrap {
        display: flex;
        align-items: center;
        gap: 3px;
        background: #f3f4f6;
        border-radius: 7px;
        padding: 2px;
        flex-shrink: 0;
    }

    .dark .qty-wrap {
        background: #1f2937;
    }

    .qty-btn {
        width: 22px;
        height: 22px;
        border: none;
        border-radius: 5px;
        background: #fff;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s, color .15s, transform .1s;
        line-height: 1;
    }

    .dark .qty-btn {
        background: #374151;
        color: #d1d5db;
    }

    .qty-btn:hover {
        background: #059669;
        color: #fff;
    }

    .qty-btn:active {
        transform: scale(.88);
    }

    .qty-val {
        width: 40px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: #1f2937;
        border: none;
        background: transparent;
        outline: none;
        padding: 0;
        -moz-appearance: textfield;
    }

    .qty-val::-webkit-inner-spin-button,
    .qty-val::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .dark .qty-val {
        color: #f3f4f6;
    }

    .cart-item-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
        flex-shrink: 0;
    }

    .cart-item-sub {
        font-size: 11.5px;
        font-weight: 700;
        color: #1f2937;
        font-variant-numeric: tabular-nums;
    }

    .dark .cart-item-sub {
        color: #e5e7eb;
    }

    .cart-remove-btn {
        font-size: 10px;
        font-weight: 600;
        color: #f87171;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        transition: color .15s;
    }

    .cart-remove-btn:hover {
        color: #dc2626;
    }

    .cart-footer {
        flex-shrink: 0;
        border-top: 1px solid #f3f4f6;
        padding: 10px 12px 12px;
        background: #fafafa;
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .dark .cart-footer {
        background: #0d1117;
        border-color: #1f2937;
    }

    .pay-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pay-label {
        font-size: 11.5px;
        color: #6b7280;
    }

    .pay-val {
        font-size: 12px;
        font-weight: 700;
        color: #1f2937;
        font-variant-numeric: tabular-nums;
    }

    .dark .pay-val {
        color: #e5e7eb;
    }

    .pay-divider {
        border: none;
        border-top: 1px dashed #e5e7eb;
        margin: 2px 0;
    }

    .dark .pay-divider {
        border-color: #374151;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .total-label {
        font-size: 12px;
        font-weight: 700;
        color: #1f2937;
    }

    .dark .total-label {
        color: #f3f4f6;
    }

    .total-val {
        font-size: 18px;
        font-weight: 900;
        color: #111827;
        font-variant-numeric: tabular-nums;
    }

    .dark .total-val {
        color: #f9fafb;
    }

    .diskon-input {
        width: 72px;
        text-align: right;
        border: 1.5px solid #e5e7eb;
        border-radius: 7px;
        padding: 3px 8px;
        font-size: 12px;
        font-weight: 700;
        background: #fff;
        color: #1f2937;
        outline: none;
        transition: border-color .2s;
        -moz-appearance: textfield;
    }

    .diskon-input::-webkit-inner-spin-button,
    .diskon-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
    }

    .diskon-input:focus {
        border-color: #059669;
    }

    .dark .diskon-input {
        background: #111827;
        border-color: #374151;
        color: #f3f4f6;
    }

    .cash-wrap {
        display: flex;
        align-items: center;
        gap: 0;
        border: 2px solid #d1d5db;
        border-radius: 9px;
        overflow: hidden;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
    }

    .dark .cash-wrap {
        background: #111827;
        border-color: #374151;
    }

    .cash-wrap:focus-within {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, .12);
    }

    .cash-prefix {
        padding: 0 8px;
        font-size: 12px;
        font-weight: 600;
        color: #9ca3af;
        background: #f9fafb;
        border-right: 1px solid #e5e7eb;
        white-space: nowrap;
        line-height: 34px;
    }

    .dark .cash-prefix {
        background: #1f2937;
        border-color: #374151;
    }

    .cash-input {
        flex: 1;
        border: none;
        outline: none;
        padding: 6px 8px;
        font-size: 13px;
        font-weight: 700;
        text-align: right;
        background: transparent;
        color: #1f2937;
        font-variant-numeric: tabular-nums;
        -moz-appearance: textfield;
    }

    .cash-input::-webkit-inner-spin-button,
    .cash-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
    }

    .dark .cash-input {
        color: #f3f4f6;
    }

    .change-badge {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 11.5px;
        font-weight: 700;
    }

    .change-badge.surplus {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .dark .change-badge.surplus {
        background: rgba(5, 150, 105, .1);
        border-color: rgba(5, 150, 105, .3);
        color: #6ee7b7;
    }

    .change-badge.shortage {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .dark .change-badge.shortage {
        background: rgba(239, 68, 68, .1);
        border-color: rgba(239, 68, 68, .3);
        color: #fca5a5;
    }

    .change-badge.exact {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }

    .dark .change-badge.exact {
        background: rgba(59, 130, 246, .1);
        border-color: rgba(59, 130, 246, .3);
        color: #93c5fd;
    }

    .submit-btn {
        width: 100%;
        padding: 11px;
        border: none;
        border-radius: 10px;
        background: #059669;
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        transition: background .15s, transform .1s, opacity .15s;
        letter-spacing: .01em;
    }

    .submit-btn:hover:not(:disabled) {
        background: #047857;
    }

    .submit-btn:active:not(:disabled) {
        transform: scale(.98);
    }

    .submit-btn:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .scanner-overlay-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9998;
        background: rgba(0, 0, 0, .72);
        backdrop-filter: blur(4px);
        animation: fadeIn .2s ease;
    }

    .scanner-panel-wrap {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        pointer-events: none;
    }

    .scanner-panel {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 25px 60px -10px rgba(0, 0, 0, .35);
        animation: slideUp .25s ease;
        pointer-events: all;
    }

    .dark .scanner-panel {
        background: #111827;
    }

    #qrcode-scanner-modal {
        display: none;
    }

    #qrcode-scanner-modal.open {
        display: block;
    }

    #qrcode-reader {
        width: 100% !important;
        position: relative;
    }

    #qrcode-reader video {
        width: 100% !important;
        border-radius: 0;
        object-fit: cover;
        display: block;
    }

    #qrcode-reader img {
        display: none !important;
    }

    #qrcode-reader__scan_region {
        min-height: 260px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    #qrcode-reader__dashboard,
    #qrcode-reader__dashboard_section {
        display: none !important;
    }

    .receipt-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9990;
        background: rgba(0, 0, 0, .6);
        backdrop-filter: blur(4px);
    }

    .receipt-panel {
        position: fixed;
        inset: 0;
        z-index: 9991;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        pointer-events: none;
    }

    .receipt-card {
        background: #fff;
        border-radius: 20px;
        padding: 28px 24px;
        width: 100%;
        max-width: 340px;
        text-align: center;
        box-shadow: 0 30px 70px -12px rgba(0, 0, 0, .35);
        animation: slideUp .25s ease;
        pointer-events: all;
    }

    .dark .receipt-card {
        background: #111827;
    }

    .receipt-icon {
        width: 56px;
        height: 56px;
        background: #ecfdf5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px;
    }

    .dark .receipt-icon {
        background: rgba(5, 150, 105, .2);
    }

    #pos-toast {
        visibility: hidden;
        min-width: 260px;
        max-width: 400px;
        background-color: #ffffff;
        color: #1f2937;
        border: 1px solid #f3f4f6;
        border-radius: 12px;
        padding: 12px 16px;
        position: fixed;
        z-index: 99999;
        left: 50%;
        top: 24px;
        transform: translate(-50%, -20px);
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.2px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.025);
        opacity: 0;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #pos-toast.show {
        visibility: visible;
        opacity: 1;
        transform: translate(-50%, 0);
    }

    .toast-icon-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .toast-icon {
        width: 14px;
        height: 14px;
    }

    #pos-toast.success .toast-icon-wrap {
        background-color: #ecfdf5;
        color: #059669;
    }

    #pos-toast.error .toast-icon-wrap {
        background-color: #fef2f2;
        color: #dc2626;
    }

    #pos-toast.warn .toast-icon-wrap {
        background-color: #fffbeb;
        color: #d97706;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
    }

    #scan-flash-overlay {
        position: fixed;
        inset: 0;
        z-index: 99998;
        background: rgba(5, 150, 105, 0.25);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    #scan-flash-overlay.active {
        opacity: 1;
        animation: scanFlash 0.35s ease-out forwards;
    }

    #scan-flash-overlay.flash-error {
        background-color: #ef4444;
        opacity: 0.6;
        transition: opacity 0.05s ease-in;
    }

    @keyframes scanFlash {
        0% { opacity: 1; }
        100% { opacity: 0; }
    }

    #qrcode-reader__scan_region::after {
        content: '';
        position: absolute;
        left: 10%;
        right: 10%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #059669, #34d399, #059669, transparent);
        box-shadow: 0 0 8px rgba(5, 150, 105, 0.6);
        animation: scanLine 2s ease-in-out infinite;
        z-index: 10;
        border-radius: 2px;
    }

    @keyframes scanLine {
        0%, 100% { top: 20%; }
        50% { top: 80%; }
    }
</style>
