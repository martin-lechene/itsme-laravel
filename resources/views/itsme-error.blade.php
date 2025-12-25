@props([
    'message' => __('itsme::itsme.errors.authentication_failed'),
    'title' => __('itsme::itsme.errors.title'),
])

<div class="itsme-error-container">
    <div class="itsme-error-content">
        <div class="itsme-error-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>
            </svg>
        </div>
        <h2 class="itsme-error-title">{{ $title }}</h2>
        <p class="itsme-error-message">{{ $message }}</p>
        <div class="itsme-error-actions">
            <a href="{{ route('login') }}" class="itsme-error-button">
                {{ __('itsme::itsme.errors.back_to_login') }}
            </a>
        </div>
    </div>
</div>

<style>
    .itsme-error-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        padding: 20px;
    }

    .itsme-error-content {
        text-align: center;
        max-width: 500px;
        padding: 40px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .itsme-error-icon {
        color: #dc3545;
        margin-bottom: 20px;
    }

    .itsme-error-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 16px;
        color: #333;
    }

    .itsme-error-message {
        font-size: 16px;
        color: #666;
        margin-bottom: 24px;
        line-height: 1.6;
    }

    .itsme-error-actions {
        margin-top: 24px;
    }

    .itsme-error-button {
        display: inline-block;
        padding: 12px 24px;
        background: #0066CC;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background 0.3s ease;
    }

    .itsme-error-button:hover {
        background: #0052A3;
    }
</style>

