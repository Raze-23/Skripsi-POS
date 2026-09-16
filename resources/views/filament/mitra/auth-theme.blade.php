<style>
    body.fi-panel-mitra {
        --attiin-ink: #2d2a12;
        --attiin-muted: #756b3d;
        --attiin-yellow: #facc15;
        --attiin-amber: #d97706;
        --attiin-amber-dark: #92400e;
        --attiin-olive: #56621f;
    }

    body.fi-panel-mitra .fi-simple-layout {
        min-height: 100svh;
        background: #fffef7;
    }

    body.fi-panel-mitra .fi-simple-main-ctn {
        padding: 2.5rem 1rem;
    }

    body.fi-panel-mitra .fi-simple-main {
        position: relative;
        margin-block: 0;
        padding: 2rem;
        border-radius: 8px;
        border: 1px solid rgba(45, 42, 18, 0.12);
        background: #ffffff;
        box-shadow: 0 4px 16px rgba(45, 42, 18, 0.05);
        letter-spacing: 0;
    }

    body.fi-panel-mitra .fi-simple-main::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 3px;
        border-radius: 8px 8px 0 0;
        background: var(--attiin-yellow);
    }

    body.fi-panel-mitra .fi-simple-page > section {
        gap: 1.5rem;
    }

    body.fi-panel-mitra .fi-form {
        row-gap: 1.5rem;
    }

    body.fi-panel-mitra .fi-fo-component-ctn {
        gap: 1.25rem;
    }

    body.fi-panel-mitra .fi-simple-header {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(45, 42, 18, 0.08);
    }

    body.fi-panel-mitra .fi-simple-header .fi-logo {
        max-height: 5rem;
        max-width: 100%;
        width: auto;
        object-fit: contain;
        margin-bottom: 0.75rem;
    }

    body.fi-panel-mitra .fi-simple-header-heading {
        color: var(--attiin-ink);
        font-size: 1.5rem;
        line-height: 2rem;
        font-weight: 600;
        letter-spacing: 0;
        overflow-wrap: anywhere;
    }

    body.fi-panel-mitra .fi-simple-header-subheading {
        max-width: 25rem;
        color: var(--attiin-muted);
        font-size: 0.875rem;
        line-height: 1.6;
    }

    body.fi-panel-mitra .fi-simple-main .fi-link {
        color: var(--attiin-amber-dark) !important;
        font-weight: 600;
        text-underline-offset: 3px;
    }

    body.fi-panel-mitra .fi-simple-main .fi-link span {
        color: inherit !important;
    }

    body.fi-panel-mitra .fi-simple-main .fi-link:hover {
        color: var(--attiin-olive) !important;
    }

    body.fi-panel-mitra .fi-fo-field-wrp-label > span {
        color: var(--attiin-ink);
        font-weight: 500;
    }

    body.fi-panel-mitra .fi-input-wrp {
        min-height: 2.75rem;
        border-radius: 6px;
    }

    body.fi-panel-mitra .fi-input-wrp:not(.fi-invalid):not(.fi-disabled) {
        background: #ffffff;
        box-shadow: 0 0 0 1px rgba(45, 42, 18, 0.2);
    }

    body.fi-panel-mitra .fi-input-wrp:not(.fi-invalid):not(.fi-disabled):focus-within {
        box-shadow:
            0 0 0 1px var(--attiin-amber),
            0 0 0 3px rgba(250, 204, 21, 0.2);
    }

    body.fi-panel-mitra .fi-input-wrp-prefix,
    body.fi-panel-mitra .fi-input-wrp-suffix {
        border: 0;
    }

    body.fi-panel-mitra .fi-input-wrp-prefix {
        padding-inline-end: 0;
    }

    body.fi-panel-mitra .fi-input-wrp-icon {
        color: var(--attiin-amber);
    }

    body.fi-panel-mitra .fi-input {
        min-height: 2.75rem;
        color: var(--attiin-ink);
    }

    body.fi-panel-mitra .fi-input::placeholder {
        color: var(--attiin-muted);
    }

    body.fi-panel-mitra .fi-simple-main .fi-btn.fi-color-primary {
        min-height: 2.75rem;
        border-radius: 6px;
        background-color: var(--attiin-yellow);
        background-image: none;
        color: #2d2a12;
        box-shadow: none;
        font-weight: 600;
    }

    body.fi-panel-mitra .fi-simple-main .fi-btn.fi-color-primary .fi-btn-label,
    body.fi-panel-mitra .fi-simple-main .fi-btn.fi-color-primary .fi-btn-icon {
        color: #2d2a12;
    }

    body.fi-panel-mitra .fi-simple-main .fi-btn.fi-color-primary:hover:not(:disabled) {
        background-color: #eab308;
        filter: none;
        transform: none;
    }

    body.fi-panel-mitra .fi-simple-main .fi-btn.fi-color-primary:focus-visible {
        outline: 2px solid var(--attiin-amber-dark);
        outline-offset: 3px;
    }

    body.fi-panel-mitra .fi-checkbox-input {
        border-radius: 4px;
        color: var(--attiin-amber);
    }

    @media (max-width: 640px) {
        body.fi-panel-mitra .fi-simple-main-ctn {
            align-items: flex-start;
            padding: 1.5rem 1rem;
        }

        body.fi-panel-mitra .fi-simple-main {
            padding: 1.5rem;
        }

        body.fi-panel-mitra .fi-input {
            font-size: 1rem;
        }
    }
</style>
