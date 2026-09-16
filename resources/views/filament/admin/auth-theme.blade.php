<style>
    body.fi-panel-admin {
        --admin-ink: #10231d;
        --admin-muted: #5d746b;
        --admin-green: #15803d;
        --admin-green-dark: #14532d;
        --admin-panel: #ffffff;
    }

    body.fi-panel-admin .fi-simple-layout {
        min-height: 100svh;
        background: #f7fbf4;
    }

    body.fi-panel-admin .fi-simple-main-ctn {
        padding: 2.5rem 1rem;
    }

    body.fi-panel-admin .fi-simple-main {
        position: relative;
        margin-block: 0;
        padding: 2rem;
        border-radius: 8px;
        border: 1px solid rgba(16, 35, 29, 0.12);
        background: var(--admin-panel);
        box-shadow: 0 4px 16px rgba(16, 35, 29, 0.05);
        letter-spacing: 0;
    }

    body.fi-panel-admin .fi-simple-main::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 3px;
        border-radius: 8px 8px 0 0;
        background: var(--admin-green);
    }

    body.fi-panel-admin .fi-simple-page > section {
        gap: 1.5rem;
    }

    body.fi-panel-admin .fi-form {
        row-gap: 1.5rem;
    }

    body.fi-panel-admin .fi-fo-component-ctn {
        gap: 1.25rem;
    }

    body.fi-panel-admin .fi-simple-header {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(16, 35, 29, 0.08);
    }

    body.fi-panel-admin .fi-simple-header .fi-logo {
        max-height: 5rem;
        max-width: 100%;
        width: auto;
        object-fit: contain;
        margin-bottom: 0.75rem;
    }

    body.fi-panel-admin .fi-simple-header-heading {
        color: var(--admin-ink);
        font-size: 1.5rem;
        line-height: 2rem;
        font-weight: 600;
        letter-spacing: 0;
        overflow-wrap: anywhere;
    }

    body.fi-panel-admin .fi-simple-header-subheading {
        max-width: 27rem;
        color: var(--admin-muted);
        font-size: 0.875rem;
        line-height: 1.6;
    }

    body.fi-panel-admin .fi-fo-field-wrp-label > span {
        color: var(--admin-ink);
        font-weight: 500;
    }

    body.fi-panel-admin .fi-input-wrp {
        min-height: 2.75rem;
        border-radius: 6px;
    }

    body.fi-panel-admin .fi-input-wrp:not(.fi-invalid):not(.fi-disabled) {
        background: var(--admin-panel);
        box-shadow: 0 0 0 1px rgba(16, 35, 29, 0.2);
    }

    body.fi-panel-admin .fi-input-wrp:not(.fi-invalid):not(.fi-disabled):focus-within {
        box-shadow:
            0 0 0 1px var(--admin-green),
            0 0 0 3px rgba(21, 128, 61, 0.12);
    }

    body.fi-panel-admin .fi-input-wrp-prefix,
    body.fi-panel-admin .fi-input-wrp-suffix {
        border: 0;
    }

    body.fi-panel-admin .fi-input-wrp-prefix {
        padding-inline-end: 0;
    }

    body.fi-panel-admin .fi-input-wrp-icon {
        color: var(--admin-green);
    }

    body.fi-panel-admin .fi-input {
        min-height: 2.75rem;
        color: var(--admin-ink);
    }

    body.fi-panel-admin .fi-input::placeholder {
        color: var(--admin-muted);
    }

    body.fi-panel-admin .fi-checkbox-input {
        border-radius: 4px;
        color: var(--admin-green);
    }

    body.fi-panel-admin .fi-simple-main .fi-btn.fi-color-primary {
        min-height: 2.75rem;
        border-radius: 6px;
        background-color: var(--admin-green);
        background-image: none;
        box-shadow: none;
        font-weight: 600;
    }

    body.fi-panel-admin .fi-simple-main .fi-btn.fi-color-primary:hover:not(:disabled) {
        background-color: var(--admin-green-dark);
        filter: none;
        transform: none;
    }

    body.fi-panel-admin .fi-simple-main .fi-btn.fi-color-primary:focus-visible {
        outline: 2px solid var(--admin-green);
        outline-offset: 3px;
    }

    @media (max-width: 640px) {
        body.fi-panel-admin .fi-simple-main-ctn {
            align-items: flex-start;
            padding: 1.5rem 1rem;
        }

        body.fi-panel-admin .fi-simple-main {
            padding: 1.5rem;
        }

        body.fi-panel-admin .fi-input {
            font-size: 1rem;
        }
    }
</style>
