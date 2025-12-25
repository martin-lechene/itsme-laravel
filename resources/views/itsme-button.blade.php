@props([
    'text' => __('itsme::itsme.button_text'),
    'class' => '',
    'size' => 'default', // 'default', 'small', 'large'
])

@php
    $sizeClasses = [
        'small' => 'itsme-btn-small',
        'default' => 'itsme-btn-default',
        'large' => 'itsme-btn-large',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
@endphp

<a href="{{ route('itsme.redirect') }}" 
   class="itsme-button {{ $sizeClass }} {{ $class }}"
   aria-label="{{ $text }}">
    <span class="itsme-button-content">
        <svg class="itsme-logo" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" fill="currentColor"/>
        </svg>
        <span class="itsme-button-text">{{ $text }}</span>
    </span>
</a>

<style>
    .itsme-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        background: linear-gradient(135deg, #0066CC 0%, #004499 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 102, 204, 0.3);
        border: none;
        cursor: pointer;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .itsme-button:hover {
        background: linear-gradient(135deg, #0052A3 0%, #003366 100%);
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.4);
        transform: translateY(-1px);
    }

    .itsme-button:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(0, 102, 204, 0.3);
    }

    .itsme-button-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .itsme-logo {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
    }

    .itsme-button-text {
        white-space: nowrap;
    }

    .itsme-btn-small {
        padding: 8px 16px;
        font-size: 14px;
    }

    .itsme-btn-small .itsme-logo {
        width: 20px;
        height: 20px;
    }

    .itsme-btn-large {
        padding: 16px 32px;
        font-size: 18px;
    }

    .itsme-btn-large .itsme-logo {
        width: 28px;
        height: 28px;
    }

    @media (max-width: 640px) {
        .itsme-button {
            width: 100%;
            padding: 14px 20px;
        }
    }
</style>

