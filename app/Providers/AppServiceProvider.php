<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::BODY_END,
            fn(): string => view('filament.pages.actions.print-purchase-note')->render(),
            scopes: \App\Filament\Resources\Gudangs\Pages\ListGudangs::class,
        );

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::BODY_END,
            fn(): string => view('filament.pages.actions.print-delivery-note')->render(),
            scopes: \App\Filament\Resources\GudangKeluars\Pages\ListGudangKeluars::class,
        );

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::BODY_END,
            fn(): string => view('filament.pages.actions.print-opname-note')->render(),
            scopes: \App\Filament\Resources\OpnameTokos\Pages\ListOpnameTokos::class,
        );

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::BODY_END,
            fn(): string => view('filament.pages.actions.print-opname-gudang-note')->render(),
            scopes: \App\Filament\Resources\OpnameGudangs\Pages\ListOpnameGudangs::class,
        );

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::BODY_END,
            fn(): string => view('filament.pages.actions.print-sales-report')->render(),
            scopes: \Filament\Pages\Dashboard::class,
        );

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::BODY_END,
            fn(): string => view('filament.pages.actions.print-gudang-report')->render(),
            scopes: \Filament\Pages\Dashboard::class,
        );
    }
}
