@php
    $hour = (int) now()->format('H');
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $name = explode(' ', auth()->user()->name ?? 'Admin')[0];
@endphp

<x-filament-widgets::widget>
    <div class="cb-banner">
        <div class="cb-banner__circle cb-banner__circle--1"></div>
        <div class="cb-banner__circle cb-banner__circle--2"></div>

        <div class="cb-banner__body">
            <div>
                <div class="cb-banner__date">{{ now()->format('l, F j, Y') }}</div>
                <h2 class="cb-banner__title">{{ $greeting }}, {{ $name }}!</h2>
                <p class="cb-banner__text">Here's what's happening with your store today.</p>
            </div>

            <div class="cb-banner__actions">
                <a href="{{ url('/admin/products/create') }}" class="cb-btn cb-btn--ghost">+ Add Product</a>
                <a href="{{ url('/admin/orders') }}" class="cb-btn cb-btn--solid">View Orders</a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
