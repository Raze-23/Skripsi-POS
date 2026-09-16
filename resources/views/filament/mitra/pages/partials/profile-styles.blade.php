<style>
    .mitra-profile-page {
        --profile-ink: #2d2a12;
        --profile-muted: #6b7280;
        --profile-border: #e5e7eb;
        --profile-yellow: #facc15;
        --profile-focus: #d97706;
        background: #ffffff;
    }

    .mitra-profile-page .fi-simple-main-ctn {
        align-items: flex-start;
        padding: 0 2rem;
    }

    .mitra-profile-page .fi-simple-main {
        margin: 0;
        padding: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .mitra-profile {
        color: var(--profile-ink);
        letter-spacing: 0;
    }

    .mitra-profile-topbar,
    .mitra-profile-brand,
    .mitra-profile-navigation,
    .mitra-profile-heading {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .mitra-profile-topbar {
        justify-content: space-between;
        flex-wrap: wrap;
        min-height: 5rem;
        padding-block: 0.5rem;
        border-bottom: 1px solid var(--profile-border);
    }

    .mitra-profile-brand {
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .mitra-profile-brand img {
        width: 5rem;
        height: 3.5rem;
        object-fit: contain;
    }

    .mitra-profile-navigation .fi-link {
        color: var(--profile-muted);
    }

    .mitra-profile-heading {
        justify-content: space-between;
        flex-wrap: wrap;
        padding-block: 2rem;
        border-bottom: 1px solid var(--profile-border);
    }

    .mitra-profile-heading h1 {
        font-size: 1.75rem;
        line-height: 2.25rem;
        font-weight: 600;
        letter-spacing: 0;
        overflow-wrap: anywhere;
    }

    .mitra-profile .fi-form,
    .mitra-profile .fi-form > .fi-fo-component-ctn {
        gap: 0;
    }

    .mitra-profile .fi-section {
        padding-block: 1.75rem;
        border-bottom: 1px solid var(--profile-border);
    }

    .mitra-profile .fi-section-content-ctn {
        min-width: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .mitra-profile .fi-section-content {
        padding: 0;
    }

    .mitra-profile .fi-section-header-heading {
        font-size: 1rem;
        line-height: 1.5rem;
        font-weight: 600;
    }

    .mitra-profile .fi-section-header-description {
        color: var(--profile-muted);
        line-height: 1.6;
    }

    .mitra-profile .fi-section-header-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0.125rem;
        color: var(--profile-focus);
    }

    .mitra-profile .fi-input-wrp {
        border-radius: 6px;
    }

    .mitra-profile .fi-input-wrp:not(.fi-invalid):not(.fi-disabled) {
        box-shadow: 0 0 0 1px #d1d5db;
    }

    .mitra-profile .fi-input-wrp:not(.fi-invalid):not(.fi-disabled):focus-within {
        box-shadow: 0 0 0 1px var(--profile-focus), 0 0 0 3px rgba(250, 204, 21, 0.2);
    }

    .mitra-profile .fi-input {
        min-height: 2.75rem;
    }

    .mitra-profile .fi-input-wrp-prefix,
    .mitra-profile .fi-input-wrp-suffix {
        border: 0;
    }

    .mitra-profile .fi-input-wrp-prefix {
        padding-inline-end: 0;
    }

    .mitra-profile .fi-input-wrp-icon {
        color: var(--profile-muted);
    }

    .mitra-profile-actions {
        position: sticky;
        bottom: 0;
        z-index: 10;
        padding-block: 1rem;
        border-top: 1px solid var(--profile-border);
        background: #ffffff;
    }

    .mitra-profile-actions .fi-btn {
        min-height: 2.75rem;
        border-radius: 6px;
    }

    .mitra-profile-actions .fi-btn.fi-color-primary {
        min-width: 11rem;
        background-color: var(--profile-yellow);
        background-image: none;
        color: var(--profile-ink);
        box-shadow: none;
    }

    .mitra-profile-actions .fi-btn.fi-color-primary:hover:not(:disabled) {
        background-color: #eab308;
    }

    .mitra-profile-actions .fi-btn.fi-color-primary:focus-visible {
        outline: 2px solid var(--profile-focus);
        outline-offset: 3px;
    }

    @media (max-width: 767px) {
        .mitra-profile-page .fi-simple-main-ctn {
            padding-inline: 1.25rem;
        }

        .mitra-profile-heading {
            padding-block: 1.5rem;
        }

        .mitra-profile .fi-section {
            padding-block: 1.5rem;
        }

        .mitra-profile .fi-input,
        .mitra-profile textarea {
            font-size: 1rem;
        }
    }
</style>
