<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Database\Seeders\PaymentLibrariesSeeder;

class CustomModificationsServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    // Check if custom modifications should be enabled
    if ($this->shouldEnableCustomModifications()) {
      $this->registerCustomBindings();
    }
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    if ($this->shouldEnableCustomModifications()) {
      $this->applyCustomModifications();
    }
  }

  /**
   * Determine if custom modifications should be enabled.
   */
  private function shouldEnableCustomModifications(): bool
  {
    // You can control this via environment variable, config, or any other logic
    return env('ENABLE_CUSTOM_MODIFICATIONS', false) === 'true';
  }

  /**
   * Register custom bindings and overrides.
   */
  private function registerCustomBindings(): void
  {
    // Override the PaymentLibrariesSeeder with your custom version
    if (class_exists('App\Seeders\Custom\CustomPaymentLibrariesSeeder')) {
      App::bind(PaymentLibrariesSeeder::class, 'App\Seeders\Custom\CustomPaymentLibrariesSeeder');
    }

    // Add more custom bindings here as needed
    // App::bind(OriginalClass::class, CustomClass::class);
  }

  /**
   * Apply custom modifications that need to happen during boot.
   */
  private function applyCustomModifications(): void
  {
    // Add any other custom modifications that need to happen during boot
    // For example, custom middleware, custom routes, etc.
  }
}
