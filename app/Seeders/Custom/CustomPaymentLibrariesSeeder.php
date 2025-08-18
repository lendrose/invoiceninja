<?php

namespace App\Seeders\Custom;

use Database\Seeders\PaymentLibrariesSeeder as OriginalPaymentLibrariesSeeder;

class CustomPaymentLibrariesSeeder extends OriginalPaymentLibrariesSeeder
{
  public function run()
  {
    // Call the parent method to get the original behavior
    parent::run();
    
    // Add your custom modifications here
    $this->applyCustomModifications();
  }

  /**
   * Apply your custom modifications to the payment libraries.
   */
  private function applyCustomModifications(): void
  {
    // Example: Add custom payment gateways
    // Example: Modify existing gateway configurations
    // Example: Change visibility settings
    
    // You can access the Gateway model and make any changes you need
    // \App\Models\Gateway::where('name', 'Your Custom Gateway')->update(['visible' => 1]);
    
    // Or add completely new gateways
    // \App\Models\Gateway::create([
    //   'name' => 'Your Custom Gateway',
    //   'provider' => 'CustomProvider',
    //   'key' => 'your-unique-key',
    //   'fields' => json_encode(['apiKey' => '', 'testMode' => false]),
    //   'visible' => 1,
    //   'sort_order' => 100
    // ]);
  }
}
