<style>
    .pr-shell {
        --pr-accent: #15803d;
        --pr-accent-hover: #166534;
        --pr-accent-soft: #f0fdf4;
        --pr-accent-border: #86efac;
        --pr-accent-contrast: #ffffff;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 19rem;
        gap: 1rem;
        align-items: start;
    }

    .pr-shell.pr-theme-mitra {
        --pr-accent: #eab308;
        --pr-accent-hover: #ca8a04;
        --pr-accent-soft: #fefce8;
        --pr-accent-border: #fde047;
        --pr-accent-contrast: #422006;
    }

    .pr-catalog,
    .pr-summary {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .dark .pr-catalog,
    .dark .pr-summary {
        background: #111827;
        border-color: #374151;
    }

    .pr-catalog {
        min-width: 0;
        padding: 1rem;
    }

    .pr-catalog-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .pr-section-title {
        margin: 0;
        color: #111827;
        font-size: .9375rem;
        font-weight: 700;
        line-height: 1.35;
        letter-spacing: 0;
    }

    .dark .pr-section-title {
        color: #f9fafb;
    }

    .pr-section-meta {
        margin: .125rem 0 0;
        color: #6b7280;
        font-size: .75rem;
        line-height: 1.4;
    }

    .dark .pr-section-meta {
        color: #9ca3af;
    }

    .pr-search {
        position: relative;
        display: block;
        width: min(100%, 20rem);
    }

    .pr-search-icon {
        position: absolute;
        top: 50%;
        left: .75rem;
        width: 1rem;
        height: 1rem;
        color: #9ca3af;
        pointer-events: none;
        transform: translateY(-50%);
    }

    .pr-search-input {
        width: 100%;
        height: 2.5rem;
        padding: 0 2.5rem 0 2.25rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        outline: none;
        background: #ffffff;
        color: #111827;
        font-size: .8125rem;
        transition: border-color 150ms ease, box-shadow 150ms ease;
    }

    .pr-search-input:focus {
        border-color: var(--pr-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--pr-accent) 15%, transparent);
    }

    .dark .pr-search-input {
        border-color: #4b5563;
        background: #1f2937;
        color: #f9fafb;
    }

    .pr-search-clear {
        position: absolute;
        top: 50%;
        right: .5rem;
        display: grid;
        width: 1.75rem;
        height: 1.75rem;
        place-items: center;
        border: 0;
        border-radius: 4px;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        transform: translateY(-50%);
    }

    .pr-search-clear:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .dark .pr-search-clear:hover {
        background: #374151;
        color: #f9fafb;
    }

    .pr-search-clear svg {
        width: 1rem;
        height: 1rem;
    }

    .pr-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
        gap: .75rem;
    }

    .pr-card {
        position: relative;
        min-width: 0;
        overflow: hidden;
        padding: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
        color: inherit;
        text-align: left;
        transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
    }

    .pr-card-select {
        display: block;
        width: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
        cursor: pointer;
    }

    .pr-card:hover {
        border-color: #9ca3af;
        box-shadow: 0 6px 14px rgba(17, 24, 39, .08);
        transform: translateY(-2px);
    }

    .pr-card-select:focus-visible {
        outline: 3px solid color-mix(in srgb, var(--pr-accent) 25%, transparent);
        outline-offset: -3px;
    }

    .pr-card.is-selected {
        border-color: var(--pr-accent);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--pr-accent) 20%, transparent);
    }

    .dark .pr-card {
        border-color: #374151;
        background: #1f2937;
    }

    .dark .pr-card:hover {
        border-color: #6b7280;
    }

    .pr-card-media {
        position: relative;
        display: block;
        width: 100%;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        border-bottom: 1px solid #e5e7eb;
        background: #f3f4f6;
    }

    .dark .pr-card-media {
        border-color: #374151;
        background: #111827;
    }

    .pr-card-media img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 180ms ease;
    }

    .pr-card-media img.is-discontinued,
    .pr-product-detail-media img.is-discontinued {
        filter: grayscale(1);
        opacity: .6;
    }

    .pr-card:hover .pr-card-media img {
        transform: scale(1.025);
    }

    .pr-selected-badge {
        position: absolute;
        bottom: .5rem;
        right: .5rem;
        display: grid;
        width: 1.5rem;
        height: 1.5rem;
        place-items: center;
        border: 2px solid #ffffff;
        border-radius: 50%;
        background: var(--pr-accent);
        color: var(--pr-accent-contrast);
        box-shadow: 0 2px 5px rgba(17, 24, 39, .18);
    }

    .pr-selected-badge svg {
        width: .875rem;
        height: .875rem;
    }

    .pr-info-button {
        position: absolute;
        top: .5rem;
        right: .5rem;
        z-index: 3;
        display: grid;
        width: 2rem;
        height: 2rem;
        place-items: center;
        border: 1px solid rgba(209, 213, 219, .9);
        border-radius: 50%;
        background: rgba(255, 255, 255, .94);
        color: #4b5563;
        box-shadow: 0 2px 7px rgba(17, 24, 39, .18);
        cursor: pointer;
        transition: color 150ms ease, transform 150ms ease, background-color 150ms ease;
    }

    .pr-info-button:hover {
        background: #ffffff;
        color: var(--pr-accent);
        transform: scale(1.06);
    }

    .pr-info-button:focus-visible {
        outline: 3px solid color-mix(in srgb, var(--pr-accent) 28%, transparent);
        outline-offset: 2px;
    }

    .pr-info-button svg {
        width: 1.125rem;
        height: 1.125rem;
    }

    .pr-best-seller-badges {
        position: absolute;
        top: .5rem;
        left: .5rem;
        z-index: 2;
        display: flex;
        max-width: calc(100% - 3.25rem);
        align-items: flex-start;
        flex-direction: column;
        gap: .3rem;
    }

    .pr-best-seller-badge {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        gap: .25rem;
        padding: .25rem .45rem;
        border: 1px solid transparent;
        border-radius: 999px;
        color: #ffffff;
        font-size: .625rem;
        font-weight: 750;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(17, 24, 39, .2);
        white-space: nowrap;
    }

    .pr-best-seller-badge.is-attiin {
        border-color: #fbbf24;
        background: #d97706;
    }

    .pr-best-seller-badge.is-mitra {
        border-color: #60a5fa;
        background: #2563eb;
    }

    .pr-best-seller-badge svg {
        width: .75rem;
        height: .75rem;
        flex: none;
    }

    .pr-card-content {
        display: block;
        padding: .75rem;
    }

    .pr-card-name,
    .pr-card-price {
        display: block;
    }

    .pr-card-name {
        overflow: hidden;
        color: #1f2937;
        font-size: .8125rem;
        font-weight: 650;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dark .pr-card-name {
        color: #f3f4f6;
    }

    .pr-card-price {
        margin-top: .25rem;
        color: var(--pr-accent);
        font-size: .75rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .pr-empty-state {
        grid-column: 1 / -1;
        display: flex;
        min-height: 16rem;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 2rem;
        border: 1px dashed #d1d5db;
        border-radius: 8px;
        color: #6b7280;
        text-align: center;
    }

    .dark .pr-empty-state {
        border-color: #4b5563;
        color: #9ca3af;
    }

    .pr-empty-state strong,
    .pr-no-selection strong {
        margin-top: .75rem;
        color: #374151;
        font-size: .8125rem;
        font-weight: 700;
    }

    .dark .pr-empty-state strong,
    .dark .pr-no-selection strong {
        color: #e5e7eb;
    }

    .pr-empty-state > span:last-child,
    .pr-no-selection > span:last-child {
        margin-top: .25rem;
        font-size: .75rem;
    }

    .pr-empty-icon {
        display: grid;
        width: 2.5rem;
        height: 2.5rem;
        place-items: center;
        border-radius: 50%;
        background: #f3f4f6;
        color: #9ca3af;
    }

    .dark .pr-empty-icon {
        background: #374151;
    }

    .pr-empty-icon svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .pr-summary {
        position: sticky;
        top: 1rem;
        overflow: hidden;
    }

    .pr-summary-form {
        display: flex;
        flex-direction: column;
    }

    .pr-summary-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .dark .pr-summary-header {
        border-color: #374151;
    }

    .pr-summary-heading-icon {
        display: grid;
        width: 2.25rem;
        height: 2.25rem;
        place-items: center;
        border-radius: 6px;
        background: var(--pr-accent-soft);
        color: var(--pr-accent);
    }

    .dark .pr-summary-heading-icon {
        background: color-mix(in srgb, var(--pr-accent) 16%, transparent);
    }

    .pr-summary-heading-icon svg {
        width: 1.125rem;
        height: 1.125rem;
    }

    .pr-summary-body {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding: 1rem;
    }

    .pr-selected-product {
        display: grid;
        grid-template-columns: 3.5rem minmax(0, 1fr);
        gap: .75rem;
        align-items: center;
        padding: .625rem;
        border: 1px solid var(--pr-accent-border);
        border-radius: 6px;
        background: var(--pr-accent-soft);
    }

    .dark .pr-selected-product {
        background: color-mix(in srgb, var(--pr-accent) 10%, #111827);
    }

    .pr-selected-product img {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 5px;
        object-fit: cover;
    }

    .pr-selected-product div {
        min-width: 0;
    }

    .pr-selected-product strong,
    .pr-selected-product span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pr-selected-product strong {
        color: #1f2937;
        font-size: .8125rem;
        line-height: 1.35;
    }

    .dark .pr-selected-product strong {
        color: #f3f4f6;
    }

    .pr-selected-product span {
        margin-top: .25rem;
        color: var(--pr-accent);
        font-size: .75rem;
        font-weight: 700;
    }

    .pr-no-selection {
        display: flex;
        min-height: 8.5rem;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 1rem;
        border: 1px dashed #d1d5db;
        border-radius: 6px;
        color: #6b7280;
        text-align: center;
    }

    .pr-production-insights {
        overflow: hidden;
        border: 1px solid #bbf7d0;
        border-radius: 6px;
        background: #f0fdf4;
    }

    .dark .pr-production-insights {
        border-color: #166534;
        background: rgba(20, 83, 45, .2);
    }

    .pr-production-insight-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .pr-production-insight-grid > div {
        min-width: 0;
        padding: .65rem .45rem;
        border-right: 1px solid #dcfce7;
        border-bottom: 1px solid #dcfce7;
        text-align: center;
    }

    .pr-production-insight-grid > div:nth-child(2n) {
        border-right: 0;
    }

    .pr-production-insight-grid > div:nth-last-child(-n + 2) {
        border-bottom: 0;
    }

    .dark .pr-production-insight-grid > div {
        border-color: #166534;
    }

    .pr-production-insight-grid span,
    .pr-production-insight-grid strong {
        display: block;
    }

    .pr-production-insight-grid span {
        color: #6b7280;
        font-size: .625rem;
        line-height: 1.25;
    }

    .dark .pr-production-insight-grid span {
        color: #9ca3af;
    }

    .pr-production-insight-grid strong {
        margin-top: .2rem;
        color: #166534;
        font-size: .875rem;
        line-height: 1.2;
    }

    .dark .pr-production-insight-grid strong {
        color: #86efac;
    }

    .pr-production-insight-grid > div.is-warning strong {
        color: #b45309;
    }

    .dark .pr-no-selection {
        border-color: #4b5563;
        color: #9ca3af;
    }

    .pr-quantity-field > label,
    .pr-discount-field > label {
        display: block;
        margin-bottom: .5rem;
        color: #374151;
        font-size: .75rem;
        font-weight: 650;
    }

    .dark .pr-quantity-field > label,
    .dark .pr-discount-field > label {
        color: #d1d5db;
    }

    .pr-quantity-control {
        display: grid;
        grid-template-columns: 2.5rem minmax(0, 1fr) auto 2.5rem;
        height: 2.75rem;
        overflow: hidden;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #ffffff;
    }

    .pr-quantity-control:focus-within {
        border-color: var(--pr-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--pr-accent) 15%, transparent);
    }

    .dark .pr-quantity-control {
        border-color: #4b5563;
        background: #1f2937;
    }

    .pr-quantity-control button {
        display: grid;
        place-items: center;
        border: 0;
        background: #f9fafb;
        color: #374151;
        cursor: pointer;
    }

    .pr-quantity-control button:first-child {
        border-right: 1px solid #e5e7eb;
    }

    .pr-quantity-control button:last-child {
        border-left: 1px solid #e5e7eb;
    }

    .pr-quantity-control button:hover:not(:disabled) {
        background: var(--pr-accent);
        color: var(--pr-accent-contrast);
    }

    .pr-quantity-control button:disabled {
        color: #d1d5db;
        cursor: not-allowed;
    }

    .dark .pr-quantity-control button {
        background: #111827;
        color: #d1d5db;
    }

    .dark .pr-quantity-control button:first-child,
    .dark .pr-quantity-control button:last-child {
        border-color: #374151;
    }

    .pr-quantity-control button svg {
        width: 1rem;
        height: 1rem;
    }

    .pr-quantity-control input {
        min-width: 0;
        padding: 0 .25rem;
        border: 0;
        outline: 0;
        background: transparent;
        color: #111827;
        font-size: .875rem;
        font-weight: 700;
        text-align: right;
        -moz-appearance: textfield;
    }

    .pr-quantity-control input::-webkit-inner-spin-button,
    .pr-quantity-control input::-webkit-outer-spin-button {
        margin: 0;
        -webkit-appearance: none;
    }

    .dark .pr-quantity-control input {
        color: #f9fafb;
    }

    .pr-quantity-unit {
        display: flex;
        align-items: center;
        padding: 0 .5rem 0 .25rem;
        color: #6b7280;
        font-size: .75rem;
    }

    .pr-discount-control {
        display: grid;
        grid-template-columns: 2.5rem minmax(0, 1fr) 2rem;
        height: 2.75rem;
        overflow: hidden;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #ffffff;
    }

    .pr-discount-control:focus-within {
        border-color: var(--pr-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--pr-accent) 15%, transparent);
    }

    .dark .pr-discount-control {
        border-color: #4b5563;
        background: #1f2937;
    }

    .pr-discount-icon,
    .pr-discount-unit {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
    }

    .pr-discount-icon {
        border-right: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .dark .pr-discount-icon {
        border-color: #374151;
        background: #111827;
        color: #d1d5db;
    }

    .pr-discount-icon svg {
        width: 1rem;
        height: 1rem;
    }

    .pr-discount-control input {
        min-width: 0;
        padding: 0 .625rem;
        border: 0;
        outline: 0;
        background: transparent;
        color: #111827;
        font-size: .875rem;
        font-weight: 700;
        -moz-appearance: textfield;
    }

    .pr-discount-control input::-webkit-inner-spin-button,
    .pr-discount-control input::-webkit-outer-spin-button {
        margin: 0;
        -webkit-appearance: none;
    }

    .pr-discount-control input:disabled {
        color: #9ca3af;
        cursor: not-allowed;
    }

    .dark .pr-discount-control input {
        color: #f9fafb;
    }

    .pr-field-help {
        margin: .5rem 0 0;
        color: #6b7280;
        font-size: .7rem;
        line-height: 1.45;
    }

    .dark .pr-field-help {
        color: #9ca3af;
    }

    .pr-error {
        margin: .5rem 0 0;
        color: #dc2626;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .pr-product-detail {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .pr-product-detail-media {
        position: relative;
        width: 100%;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f3f4f6;
    }

    .pr-product-detail-media > img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .pr-product-detail-badges {
        position: absolute;
        bottom: .75rem;
        left: .75rem;
        display: flex;
        align-items: flex-start;
        flex-direction: column;
        gap: .375rem;
    }

    .pr-discontinued-notice {
        position: absolute;
        inset: 0 0 auto;
        padding: .4rem .75rem;
        background: rgba(220, 38, 38, .92);
        color: #ffffff;
        font-size: .7rem;
        font-weight: 700;
        text-align: center;
    }

    .pr-product-detail-name {
        margin: 0;
        color: #111827;
        font-size: 1rem;
        font-weight: 750;
        line-height: 1.4;
    }

    .dark .pr-product-detail-name {
        color: #f9fafb;
    }

    .pr-product-detail-description {
        margin: .35rem 0 0;
        color: #6b7280;
        font-size: .8125rem;
        line-height: 1.55;
        white-space: pre-wrap;
    }

    .dark .pr-product-detail-description {
        color: #9ca3af;
    }

    .pr-product-detail-facts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .5rem;
    }

    .pr-product-detail-fact {
        min-width: 0;
        padding: .75rem;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #f9fafb;
    }

    .dark .pr-product-detail-fact {
        border-color: #374151;
        background: #1f2937;
    }

    .pr-product-detail-fact span,
    .pr-product-detail-fact strong {
        display: block;
    }

    .pr-product-detail-fact span {
        color: #6b7280;
        font-size: .625rem;
        line-height: 1.25;
    }

    .pr-product-detail-fact strong {
        margin-top: .3rem;
        color: #111827;
        font-size: .8rem;
        line-height: 1.3;
    }

    .dark .pr-product-detail-fact span {
        color: #9ca3af;
    }

    .dark .pr-product-detail-fact strong {
        color: #f3f4f6;
    }

    .pr-product-detail-fact.is-stock {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .pr-product-detail-fact.is-stock strong {
        color: #15803d;
    }

    .pr-product-detail-loading {
        display: grid;
        min-height: 12rem;
        place-items: center;
    }

    .pr-product-detail-loading svg {
        width: 2rem;
        height: 2rem;
        color: var(--pr-accent);
    }

    .pr-summary-actions {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        padding: 0 1rem 1rem;
    }

    .pr-submit {
        display: flex;
        width: 100%;
        min-height: 2.75rem;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: .625rem 1rem;
        border: 0;
        border-radius: 6px;
        background: var(--pr-accent);
        color: var(--pr-accent-contrast);
        font-size: .8125rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 150ms ease, box-shadow 150ms ease;
    }

    .pr-submit:hover:not(:disabled) {
        background: var(--pr-accent-hover);
        box-shadow: 0 4px 10px color-mix(in srgb, var(--pr-accent) 24%, transparent);
    }

    .pr-submit:focus-visible {
        outline: 3px solid color-mix(in srgb, var(--pr-accent) 25%, transparent);
        outline-offset: 2px;
    }

    .pr-submit:disabled {
        background: #d1d5db;
        color: #6b7280;
        cursor: not-allowed;
        box-shadow: none;
    }

    .dark .pr-submit:disabled {
        background: #374151;
        color: #9ca3af;
    }

    .pr-submit svg {
        width: 1rem;
        height: 1rem;
    }

    .pr-cancel {
        display: flex;
        min-height: 2.25rem;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        color: #6b7280;
        font-size: .75rem;
        font-weight: 650;
        text-decoration: none;
    }

    .pr-cancel:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .dark .pr-cancel:hover {
        background: #1f2937;
        color: #f9fafb;
    }

    @media (max-width: 1023px) {
        .pr-shell {
            grid-template-columns: minmax(0, 1fr);
        }

        .pr-summary {
            position: static;
        }

        .pr-summary-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(14rem, .7fr);
            align-items: center;
        }
    }

    @media (max-width: 639px) {
        .pr-catalog {
            padding: .75rem;
        }

        .pr-catalog-header {
            align-items: stretch;
            flex-direction: column;
            gap: .75rem;
        }

        .pr-search {
            width: 100%;
        }

        .pr-search-input {
            font-size: 1rem;
        }

        .pr-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .625rem;
        }

        .pr-card-content {
            padding: .625rem;
        }

        .pr-summary-body {
            display: flex;
        }
    }

    @media (max-width: 359px) {
        .pr-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>
