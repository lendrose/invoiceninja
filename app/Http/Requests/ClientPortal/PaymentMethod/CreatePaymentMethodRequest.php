<?php

namespace App\Http\Requests\ClientPortal\PaymentMethod;

use App\Http\Requests\Request;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

use function auth;
use function collect;

class CreatePaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {

        auth()->guard('contact')->user()->loadMissing(['client' => function ($query) {
            $query->without('gateway_tokens', 'documents', 'contacts.company', 'contacts'); // Exclude 'grandchildren' relation of 'client'
        }]);

        /** @var Client $client */
        $client = auth()->guard('contact')->user()->client;

        // Check query params first, then fall back to session (for redirects that lose the method param)
        $method = $this->query('method') ?? session('payment_method_create_method');

        if ($method === null) {
            return false;
        }

        // Convert method to integer for comparison (query params come as strings)
        $method = (int) $method;

        $available_methods = collect($client->service()->getPaymentMethods(-1))
            ->pluck('gateway_type_id')
            ->map(fn($id) => (int) $id) // Ensure all are integers
            ->toArray();

        return in_array($method, $available_methods, true); // Strict comparison

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
