<?php

namespace App\Providers;

use App\Filament\Pages\LunarDashboard;
use App\Filament\Widgets\DashboardDateFilterWidget;
use App\Http\Middleware\EnsureIsAdmin;
use App\Listeners\LogConnectionPruned;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use App\Listeners\LogSuccessfulLogin;
use App\Lunar\Extensions\CollectionGroupCreateExtension;
use App\Lunar\Extensions\CollectionGroupEditExtension;
use App\Lunar\Extensions\CollectionGroupResourceExtension;
use App\Lunar\Extensions\CurrencyResourceExtension;
use App\Lunar\Extensions\CustomerListExtension;
use App\Lunar\Extensions\CustomerResourceExtension;
use App\Lunar\Extensions\DiscountEditExtension;
use App\Lunar\Extensions\DiscountListExtension;
use App\Lunar\Extensions\DiscountResourceExtension;
use App\Lunar\Extensions\ListProductsExtension;
use App\Lunar\Extensions\ManageOrderExtension;
use App\Lunar\Extensions\ManageProductInventoryExtension;
use App\Lunar\Extensions\ManageProductPricingExtension;
use App\Lunar\Extensions\ManageProductVariantsExtension;
use App\Lunar\Extensions\ManageVariantInventoryExtension;
use App\Lunar\Extensions\ManageVariantPricingExtension;
use App\Lunar\Extensions\ProductVariantResourceExtension;
use App\Lunar\Pages\EditShippingZonePage;
use App\Lunar\Pages\ManageProductPricingPage;
use App\Lunar\Pages\ManageShippingRatesPage;
use App\Lunar\Pages\ManageVariantPricingPage;
use App\Lunar\Extensions\ProductConditionRelationManagerExtension;
use App\Lunar\Extensions\ProductLimitationRelationManagerExtension;
use App\Lunar\Extensions\ProductResourceExtension;
use App\Lunar\Extensions\ProductRewardRelationManagerExtension;
use App\Lunar\Extensions\ShippingExclusionRelationManagerExtension;
use App\Lunar\Extensions\ShippingMethodEditExtension;
use App\Lunar\Extensions\ShippingMethodListExtension;
use App\Lunar\Extensions\ShippingZoneResourceExtension;
use App\PaymentTypes\SenangpayPayment;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\Events\ConnectionPruned;
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\CollectionGroupResource;
use Lunar\Admin\Filament\Resources\CollectionGroupResource\Pages\CreateCollectionGroup;
use Lunar\Admin\Filament\Resources\CollectionGroupResource\Pages\EditCollectionGroup;
use Lunar\Admin\Filament\Resources\CurrencyResource;
use Lunar\Admin\Filament\Resources\CustomerResource;
use Lunar\Admin\Filament\Resources\CustomerResource\Pages\ListCustomers;
use Lunar\Admin\Filament\Resources\DiscountResource;
use Lunar\Admin\Filament\Resources\DiscountResource\Pages\EditDiscount;
use Lunar\Admin\Filament\Resources\DiscountResource\Pages\ListDiscounts;
use Lunar\Admin\Filament\Resources\DiscountResource\RelationManagers\ProductConditionRelationManager;
use Lunar\Admin\Filament\Resources\DiscountResource\RelationManagers\ProductLimitationRelationManager;
use Lunar\Admin\Filament\Resources\DiscountResource\RelationManagers\ProductRewardRelationManager;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ManageOrder;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ListProducts;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductInventory;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductPricing;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductVariants;
use Lunar\Admin\Filament\Resources\ProductVariantResource;
use Lunar\Admin\Filament\Resources\ProductVariantResource\Pages\ManageVariantInventory;
use Lunar\Admin\Filament\Resources\ProductVariantResource\Pages\ManageVariantPricing;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Shipping\Filament\Resources\ShippingExclusionListResource\RelationManagers\ShippingExclusionRelationManager;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\EditShippingMethod;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\ListShippingMethod;
use Lunar\Shipping\Filament\Resources\ShippingZoneResource;
use Lunar\Shipping\ShippingPlugin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replace Lunar's hardcoded Dashboard with our custom one that includes the date filter widget
        $pagesRef = new \ReflectionProperty(\Lunar\Admin\LunarPanelManager::class, 'pages');
        $pagesRef->setAccessible(true);
        $pagesRef->setValue(null, array_map(
            fn ($page) => $page === \Lunar\Admin\Filament\Pages\Dashboard::class
                ? LunarDashboard::class
                : $page,
            $pagesRef->getValue()
        ));

        // Replace OrderTotalsChart with custom one that uses short month names
        $widgetsRef = new \ReflectionProperty(\Lunar\Admin\LunarPanelManager::class, 'widgets');
        $widgetsRef->setAccessible(true);
        $widgetsRef->setValue(null, array_map(
            fn ($widget) => $widget === \Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrderTotalsChart::class
                ? \App\Filament\Widgets\OrderTotalsChart::class
                : $widget,
            $widgetsRef->getValue()
        ));

        // Remove AttributeGroupResource and ChannelResource from Lunar panel navigation
        $resourcesRef = new \ReflectionProperty(\Lunar\Admin\LunarPanelManager::class, 'resources');
        $resourcesRef->setAccessible(true);
        $resourcesRef->setValue(null, array_values(array_filter(
            $resourcesRef->getValue(),
            fn ($resource) => ! in_array($resource, [
                \Lunar\Admin\Filament\Resources\AttributeGroupResource::class,
                \Lunar\Admin\Filament\Resources\ChannelResource::class,
                \Lunar\Admin\Filament\Resources\ProductTypeResource::class,
                \Lunar\Admin\Filament\Resources\CustomerGroupResource::class,
                \Lunar\Admin\Filament\Resources\DiscountResource::class,
            ])
        )));

        LunarPanel::disableTwoFactorAuth()
            ->extensions([
                CurrencyResource::class => CurrencyResourceExtension::class,
                CreateCollectionGroup::class => CollectionGroupCreateExtension::class,
                EditCollectionGroup::class => CollectionGroupEditExtension::class,
                CollectionGroupResource::class => CollectionGroupResourceExtension::class,
                CustomerResource::class => CustomerResourceExtension::class,
                ListCustomers::class => CustomerListExtension::class,
                EditDiscount::class => DiscountEditExtension::class,
                ListDiscounts::class => DiscountListExtension::class,
                DiscountResource::class => DiscountResourceExtension::class,
                ProductConditionRelationManager::class => ProductConditionRelationManagerExtension::class,
                ProductLimitationRelationManager::class => ProductLimitationRelationManagerExtension::class,
                ProductRewardRelationManager::class => ProductRewardRelationManagerExtension::class,
                ManageOrder::class => ManageOrderExtension::class,
                ListProducts::class => ListProductsExtension::class,
                ManageProductInventory::class => ManageProductInventoryExtension::class,
                ManageProductPricing::class => ManageProductPricingExtension::class,
                ManageProductPricingPage::class => ManageProductPricingExtension::class,
                ManageProductVariants::class => ManageProductVariantsExtension::class,
                ManageVariantInventory::class => ManageVariantInventoryExtension::class,
                ManageVariantPricing::class => ManageVariantPricingExtension::class,
                ManageVariantPricingPage::class => ManageVariantPricingExtension::class,
                ProductResource::class => ProductResourceExtension::class,
                ProductVariantResource::class => ProductVariantResourceExtension::class,
                ShippingExclusionRelationManager::class => ShippingExclusionRelationManagerExtension::class,
                EditShippingMethod::class => ShippingMethodEditExtension::class,
                ListShippingMethod::class => ShippingMethodListExtension::class,
                ShippingZoneResource::class => ShippingZoneResourceExtension::class,
            ])
            ->panel(function (Panel $panel) {
                return $panel
                    ->authGuard('web')
                    ->authPasswordBroker('users')
                    ->authMiddleware([
                        Authenticate::class,
                        EnsureIsAdmin::class,
                    ])
                    ->userMenuItems([
                        'admin-panel' => \Filament\Navigation\MenuItem::make()
                            ->label('Back to Admin Panel')
                            ->url('/dashboard')
                            ->icon('heroicon-o-arrow-left-circle'),
                    ])
                    ->brandLogo(asset('logo.png'))
                    ->darkModeBrandLogo(asset('logo.png'))
                    ->favicon(asset('favicon.ico'))
                    ->plugin(new ShippingPlugin)
                    ->widgets([
                        DashboardDateFilterWidget::class,
                    ]);
            })->register();

        // Register SenangPay payment driver
        \Lunar\Facades\Payments::extend('senangpay', fn ($app) => $app->make(SenangpayPayment::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Lunar\Facades\Telemetry::optOut();

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Discount::class,
            \App\Models\Discount::class,
        );

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\ProductVariant::class,
            \App\Models\ProductVariant::class,
        );

        Livewire::component('app.lunar.widgets.product-options-widget', \App\Lunar\Widgets\ProductOptionsWidget::class);


        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(ConnectionPruned::class, LogConnectionPruned::class);

        // Allow all admin users to access Lunar admin panel
        Gate::before(function ($user, $ability) {
            // Check if this is a Lunar admin panel permission
            if (str_starts_with($ability, 'lunar:') || str_starts_with($ability, 'catalog:') || str_starts_with($ability, 'sales:') || str_starts_with($ability, 'settings:')) {
                return $user && $user->is_admin ? true : null;
            }
        });
    }
}
