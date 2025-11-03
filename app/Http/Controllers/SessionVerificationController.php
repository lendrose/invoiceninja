<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Session Verification Controller
 * 
 * This controller provides an endpoint for the new Express.js API (v1.1)
 * to verify Laravel session authentication. This allows gradual migration
 * while maintaining existing authentication infrastructure.
 */
class SessionVerificationController extends BaseController
{
  /**
   * Verify the current session
   * 
   * Checks if the user is authenticated via Laravel session
   * and returns user/company information if valid
   * 
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function verify(Request $request)
  {
    try {
      // Check if user is authenticated
      if (!Auth::check()) {
        return response()->json([
          'valid' => false,
          'error' => 'No active session or session expired'
        ], 401);
      }

      $user = Auth::user();
      
      // Get the current company if available
      $company = null;
      
      if ($user && method_exists($user, 'company')) {
        $company = $user->company;
      }

      $response = [
        'valid' => true,
        'user' => [
          'id' => $user->id,
          'email' => $user->email,
          'first_name' => $user->first_name ?? null,
          'last_name' => $user->last_name ?? null,
        ],
        'timestamp' => now()->toIso8601String()
      ];

      // Add company info if available
      if ($company && is_object($company)) {
        $response['company'] = [
          'id' => $company->id ?? null,
          'name' => $company->settings->name ?? $company->name ?? null,
        ];
      }

      return response()->json($response, 200);

    } catch (\Exception $e) {
      // Log the error for debugging
      \Log::error('Session verification error: ' . $e->getMessage());
      
      return response()->json([
        'valid' => false,
        'error' => 'Error verifying session',
        'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }
}

