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

use App\Models\Client;
use App\Libraries\MultiDB;
use Illuminate\Http\Response;
use App\Models\CompanyGateway;
use App\Utils\Traits\MakesHash;
use App\DataMapper\FeesAndLimits;
use App\Jobs\Util\ApplePayDomain;
use Illuminate\Support\Facades\Cache;
use App\Factory\CompanyGatewayFactory;
use App\Filters\CompanyGatewayFilters;
use App\Repositories\CompanyRepository;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Transformers\CompanyGatewayTransformer;
use App\PaymentDrivers\Stripe\Jobs\StripeWebhook;
use App\PaymentDrivers\CheckoutCom\CheckoutSetupWebhook;
use App\Http\Requests\CompanyGateway\BulkCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\EditCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\ShowCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\TestCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\CloneCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\StoreCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\CreateCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\UpdateCompanyGatewayRequest;
use App\Http\Requests\CompanyGateway\DestroyCompanyGatewayRequest;

/**
 * Class CompanyGatewayController.
 */
class CompanyGatewayController extends BaseController
{
    use DispatchesJobs;
    use MakesHash;

    protected $entity_type = CompanyGateway::class;

    protected $entity_transformer = CompanyGatewayTransformer::class;

    protected $company_repo;

    public $forced_includes = [];

    private array $stripe_keys = ['d14dd26a47cecc30fdd65700bfb67b34', 'd14dd26a37cecc30fdd65700bfb55b23'];

    private string $checkout_key = '3758e7f7c6f4cecf0f4f348b9a00f456';

    private string $forte_key = 'kivcvjexxvdiyqtj3mju5d6yhpeht2xs';

    private string $cbapowerboard_key = 'b67581d804dbad1743b61c57285142ad';

    /**
     * CompanyGatewayController constructor.
     * @param CompanyRepository $company_repo
     */
    public function __construct(CompanyRepository $company_repo)
    {
        parent::__construct();

        $this->company_repo = $company_repo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     *
     * @OA\Get(
     *      path="/api/v1/company_gateways",
     *      operationId="getCompanyGateways",
     *      tags={"company_gateways"},
     *      summary="Gets a list of company_gateways",
     *      description="Lists company_gateways, search and filters allow fine grained lists to be generated.

        Query parameters can be added to performed more fine grained filtering of the company_gateways, these are handled by the CompanyGatewayFilters class which defines the methods available",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Response(
     *          response=200,
     *          description="A list of company_gateways",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/CompanyGateway"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),

     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function index(CompanyGatewayFilters $filters)
    {
        $company_gateways = CompanyGateway::filter($filters);

        return $this->listResponse($company_gateways);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param CreateCompanyGatewayRequest $request
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     *
     * @OA\Get(
     *      path="/api/v1/company_gateways/create",
     *      operationId="getCompanyGatewaysCreate",
     *      tags={"company_gateways"},
     *      summary="Gets a new blank CompanyGateway object",
     *      description="Returns a blank object with default values",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Response(
     *          response=200,
     *          description="A blank CompanyGateway object",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/CompanyGateway"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function create(CreateCompanyGatewayRequest $request)
    {

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $company_gateway = CompanyGatewayFactory::create($user->company()->id, auth()->user()->id);

        return $this->itemResponse($company_gateway);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCompanyGatewayRequest $request
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     *
     * @OA\Post(
     *      path="/api/v1/company_gateways",
     *      operationId="storeCompanyGateway",
     *      tags={"company_gateways"},
     *      summary="Adds a CompanyGateway",
     *      description="Adds an CompanyGateway to the system",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the saved CompanyGateway object",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/CompanyGateway"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function store(StoreCompanyGatewayRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $company_gateway = CompanyGatewayFactory::create($user->company()->id, $user->id);
        $company_gateway->fill($request->all());
        $company_gateway->save();

        /*Always ensure at least one fees and limits object is set per gateway*/
        $gateway_types = $company_gateway->driver(new Client())->getAvailableMethods();

        $fees_and_limits = $company_gateway->fees_and_limits;

        foreach ($gateway_types as $key => $gateway_type) {
            if (!property_exists($fees_and_limits, $key)) {
                $fees_and_limits->{$key} = new FeesAndLimits();
            }
        }

        $company_gateway->fees_and_limits = $fees_and_limits;
        $company_gateway->save();

        ApplePayDomain::dispatch($company_gateway, $company_gateway->company->db);

        switch ($company_gateway->gateway_key) {
            case in_array($company_gateway->gateway_key, $this->stripe_keys):
                StripeWebhook::dispatch($company_gateway->company->company_key, $company_gateway->id);
                break;

            case $this->checkout_key:
                CheckoutSetupWebhook::dispatch($company_gateway->company->company_key, $company_gateway->id);
                break;

            case $this->forte_key:

                $config = $company_gateway->getConfig();

                $config->authOrganizationId = !str_starts_with($config->authOrganizationId, 'org_')
                    ? 'org_' . $config->authOrganizationId
                    : $config->authOrganizationId;

                $config->organizationId = !str_starts_with($config->organizationId, 'org_')
                    ? 'org_' . $config->organizationId
                    : $config->organizationId;

                $config->locationId = !str_starts_with($config->locationId, 'loc_')
                    ? 'loc_' . $config->locationId
                    : $config->locationId;

                $company_gateway->setConfig($config);
                $company_gateway->save();

                dispatch(function () use ($company_gateway) {
                    MultiDB::setDb($company_gateway->company->db);
                    $company_gateway->driver()->updateFees();
                })->afterResponse();

                break;

            case $this->cbapowerboard_key:

                dispatch(function () use ($company_gateway) {
                    MultiDB::setDb($company_gateway->company->db);
                    $company_gateway->driver()->init()->settings()->updateSettings();
                })->afterResponse();

                $config = $company_gateway->getConfig();
                $config->visa = true;
                $config->mastercard = true;
                $company_gateway->setConfig($config);
                $company_gateway->save();

                break;

            default:
                # code...
                break;

        }

        return $this->itemResponse($company_gateway);
    }

    /**
     * Display the specified resource.
     *
     * @param ShowCompanyGatewayRequest $request
     * @param CompanyGateway $company_gateway
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     * @OA\Get(
     *      path="/api/v1/company_gateways/{id}",
     *      operationId="showCompanyGateway",
     *      tags={"company_gateways"},
     *      summary="Shows an CompanyGateway",
     *      description="Displays an CompanyGateway by id",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The CompanyGateway Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the CompanyGateway object",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/CompanyGateway"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function show(ShowCompanyGatewayRequest $request, CompanyGateway $company_gateway)
    {
        return $this->itemResponse($company_gateway);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param EditCompanyGatewayRequest $request
     * @param CompanyGateway $company_gateway
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     * @OA\Get(
     *      path="/api/v1/company_gateways/{id}/edit",
     *      operationId="editCompanyGateway",
     *      tags={"company_gateways"},
     *      summary="Shows an CompanyGateway for editting",
     *      description="Displays an CompanyGateway by id",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The CompanyGateway Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the CompanyGateway object",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/CompanyGateway"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function edit(EditCompanyGatewayRequest $request, CompanyGateway $company_gateway)
    {
        return $this->itemResponse($company_gateway);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCompanyGatewayRequest $request
     * @param CompanyGateway $company_gateway
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     * @OA\Put(
     *      path="/api/v1/company_gateways/{id}",
     *      operationId="updateCompanyGateway",
     *      tags={"company_gateways"},
     *      summary="Updates an CompanyGateway",
     *      description="Handles the updating of an CompanyGateway by id",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The CompanyGateway Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the CompanyGateway object",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/CompanyGateway"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function update(UpdateCompanyGatewayRequest $request, CompanyGateway $company_gateway)
    {
        $company_gateway->fill($request->all());

        /*Always ensure at least one fees and limits object is set per gateway*/
        $gateway_types = $company_gateway->driver(new Client())->getAvailableMethods();

        $fees_and_limits = $company_gateway->fees_and_limits;

        foreach ($gateway_types as $key => $gateway_type) {
            if (!property_exists($fees_and_limits, $key)) {
                $fees_and_limits->{$key} = new FeesAndLimits();
            }
        }

        $company_gateway->fees_and_limits = $fees_and_limits;
        $company_gateway->save();

        switch ($company_gateway->gateway_key) {

            case $this->checkout_key:
                CheckoutSetupWebhook::dispatch($company_gateway->company->company_key, $company_gateway->id);
                break;

            case $this->forte_key:

                $config = $company_gateway->getConfig();

                $config->authOrganizationId = !str_starts_with($config->authOrganizationId, 'org_')
                    ? 'org_' . $config->authOrganizationId
                    : $config->authOrganizationId;

                $config->organizationId = !str_starts_with($config->organizationId, 'org_')
                    ? 'org_' . $config->organizationId
                    : $config->organizationId;

                $config->locationId = !str_starts_with($config->locationId, 'loc_')
                    ? 'loc_' . $config->locationId
                    : $config->locationId;

                $company_gateway->setConfig($config);
                $company_gateway->save();


                dispatch(function () use ($company_gateway) {
                    MultiDB::setDb($company_gateway->company->db);
                    $company_gateway->driver()->updateFees();
                })->afterResponse();

                break;

            default:
                # code...
                break;

        }

        return $this->itemResponse($company_gateway);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyCompanyGatewayRequest $request
     * @param CompanyGateway $company_gateway
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     * @throws \Exception
     * @OA\Delete(
     *      path="/api/v1/company_gateways/{id}",
     *      operationId="deleteCompanyGateway",
     *      tags={"company_gateways"},
     *      summary="Deletes a CompanyGateway",
     *      description="Handles the deletion of an CompanyGateway by id",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The CompanyGateway Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns a HTTP status",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function destroy(DestroyCompanyGatewayRequest $request, CompanyGateway $company_gateway)
    {
        $company_gateway->driver(new Client())
                         ->disconnect();

        $company_gateway->delete();

        return $this->itemResponse($company_gateway->fresh());
    }

    /**
     * Perform bulk actions on the list view.
     *
     * @return Response| \Illuminate\Http\JsonResponse
     *
     *
     * @OA\Post(
     *      path="/api/v1/company_gateways/bulk",
     *      operationId="bulkCompanyGateways",
     *      tags={"company_gateways"},
     *      summary="Performs bulk actions on an array of company_gateways",
     *      description="",
     *      @OA\Parameter(ref="#/components/parameters/X-API-TOKEN"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/index"),
     *      @OA\RequestBody(
     *         description="Array of company gateway IDs",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="array",
     *                 @OA\Items(
     *                     type="integer",
     *                     description="Array of hashed IDs to be bulk 'actioned",
     *                     example="[0,1,2,3]",
     *                 ),
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="The Company Gateways response",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/CompanyGateway"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function bulk(BulkCompanyGatewayRequest $request)
    {
        $action = $request->input('action');

        $company_gateways = CompanyGateway::withTrashed()
                                          ->whereIn('id', $request->ids)
                                          ->company()
                                          ->cursor()
                                          ->each(function ($company_gateway, $key) use ($action) {
                                              $this->company_repo->{$action}($company_gateway);
                                          });

        return $this->listResponse(CompanyGateway::withTrashed()->company()->whereIn('id', $request->ids));
    }

    public function clone(CloneCompanyGatewayRequest $request, CompanyGateway $company_gateway)
    {
        $new_company_gateway = $company_gateway->replicate();
        $new_company_gateway->label .= ' ('.ctrans('texts.clone').') ' . now()->format('Y-m-d H:i:s');
        $new_company_gateway->save();
        return $this->itemResponse($new_company_gateway);
    }

    public function test(TestCompanyGatewayRequest $request, CompanyGateway $company_gateway)
    {
        try {
            // Enhanced initial logging
            \Log::info('=== GATEWAY TEST STARTED ===', [
                'gateway_id' => $company_gateway->id,
                'gateway_key' => $company_gateway->gateway_key,
                'gateway_label' => $company_gateway->label,
                'user_id' => auth()->user()->id,
                'user_email' => auth()->user()->email,
                'timestamp' => now()->toISOString()
            ]);
            
            // Check gateway relationship
            $gateway = $company_gateway->gateway;
            if (!$gateway) {
                \Log::error('GATEWAY RELATIONSHIP MISSING', [
                    'company_gateway_id' => $company_gateway->id,
                    'gateway_key' => $company_gateway->gateway_key,
                    'error' => 'Gateway record not found in database'
                ]);
                
                return response()->json([
                    'message' => 'Gateway configuration not found',
                    'error' => 'GATEWAY_NOT_FOUND',
                    'details' => 'The gateway record does not exist in the database'
                ], 400);
            }
            
            \Log::info('Gateway relationship found', [
                'gateway_id' => $gateway->id,
                'gateway_name' => $gateway->name,
                'gateway_provider' => $gateway->provider,
                'gateway_visible' => $gateway->visible,
                'gateway_sort_order' => $gateway->sort_order
            ]);
            
            // Check driver class
            $expectedClass = 'App\\PaymentDrivers\\' . $gateway->provider . 'PaymentDriver';
            $expectedClass = str_replace('_', '', $expectedClass);
            
            \Log::info('Driver class analysis', [
                'expected_class' => $expectedClass,
                'class_exists' => class_exists($expectedClass),
                'provider' => $gateway->provider
            ]);
            
            if (!class_exists($expectedClass)) {
                \Log::error('DRIVER CLASS NOT FOUND', [
                    'expected_class' => $expectedClass,
                    'provider' => $gateway->provider,
                    'available_drivers' => $this->getAvailablePaymentDrivers()
                ]);
                
                return response()->json([
                    'message' => 'Payment driver not found for this gateway type',
                    'error' => 'DRIVER_NOT_FOUND',
                    'details' => 'The payment driver class does not exist for provider: ' . $gateway->provider,
                    'expected_class' => $expectedClass
                ], 400);
            }
            
            // Get driver instance
            $driver = $company_gateway->driver();
            
            if (!$driver) {
                $errorInfo = $company_gateway->getDriverErrorInfo();
                
                \Log::error('DRIVER CREATION FAILED', [
                    'gateway_id' => $company_gateway->id,
                    'gateway_key' => $company_gateway->gateway_key,
                    'provider' => $gateway->provider,
                    'expected_class' => $expectedClass,
                    'error_info' => $errorInfo
                ]);
                
                return response()->json([
                    'message' => 'Payment driver creation failed',
                    'error' => 'DRIVER_CREATION_FAILED',
                    'details' => 'Failed to create payment driver instance',
                    'debug_info' => $errorInfo
                ], 400);
            }
            
            \Log::info('Driver created successfully', [
                'driver_class' => get_class($driver),
                'gateway_id' => $company_gateway->id
            ]);
            
            // Log gateway configuration (masked for security)
            $config = $company_gateway->getConfig();
            $maskedConfig = $this->maskSensitiveConfig($config);
            
            \Log::info('Gateway configuration', [
                'gateway_id' => $company_gateway->id,
                'config_fields' => array_keys((array) $config),
                'masked_config' => $maskedConfig,
                'has_api_login_id' => !empty($config->apiLoginId ?? null),
                'has_transaction_key' => !empty($config->transactionKey ?? null),
                'has_signature_key' => !empty($config->signatureKey ?? null),
                'test_mode' => $config->testMode ?? null,
                'developer_mode' => $config->developerMode ?? null
            ]);
            
            // Test authentication
            \Log::info('Starting gateway authentication test', [
                'gateway_id' => $company_gateway->id,
                'driver_class' => get_class($driver)
            ]);
            
            $message = $driver->auth();
            
            \Log::info('Gateway authentication completed', [
                'gateway_id' => $company_gateway->id,
                'auth_result' => $message,
                'auth_success' => $message === 'ok',
                'timestamp' => now()->toISOString()
            ]);
            
            if ($message !== 'ok') {
                \Log::warning('Gateway authentication failed', [
                    'gateway_id' => $company_gateway->id,
                    'gateway_provider' => $gateway->provider,
                    'auth_result' => $message,
                    'possible_causes' => [
                        'Invalid API credentials (apiLoginId, transactionKey)',
                        'Missing signatureKey (required for newer Authorize.net)',
                        'Network connectivity issues',
                        'Wrong test/live mode configuration',
                        'API endpoint configuration issues'
                    ]
                ]);
            }
            
            return response()->json([
                'message' => $message,
                'status' => $message === 'ok' ? 'success' : 'error',
                'gateway_id' => $company_gateway->id,
                'gateway_key' => $company_gateway->gateway_key,
                'gateway_provider' => $gateway->provider
            ], 200);
            
        } catch (\App\Exceptions\GenericPaymentDriverFailure $e) {
            \Log::error('Gateway authentication failed with payment driver error', [
                'gateway_id' => $company_gateway->id,
                'error' => $e->getMessage(),
                'gateway_key' => $company_gateway->gateway_key
            ]);
            
            return response()->json([
                'message' => 'Gateway authentication failed: ' . $e->getMessage(),
                'error' => 'PAYMENT_DRIVER_ERROR',
                'details' => $e->getMessage(),
                'gateway_id' => $company_gateway->id
            ], 400);
            
        } catch (\Exception $e) {
            \Log::error('Gateway test failed with exception', [
                'gateway_id' => $company_gateway->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'gateway_key' => $company_gateway->gateway_key
            ]);
            
            return response()->json([
                'message' => 'Gateway authentication failed: ' . $e->getMessage(),
                'error' => 'AUTH_FAILED',
                'details' => $e->getMessage(),
                'gateway_id' => $company_gateway->id
            ], 400);
            
        } catch (\Throwable $e) {
            \Log::error('Gateway test failed with fatal error', [
                'gateway_id' => $company_gateway->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'gateway_key' => $company_gateway->gateway_key
            ]);
            
            return response()->json([
                'message' => 'Unexpected error during gateway test',
                'error' => 'UNEXPECTED_ERROR',
                'details' => $e->getMessage(),
                'gateway_id' => $company_gateway->id
            ], 500);
        }
    }

    /**
     * Get list of available payment drivers for debugging
     */
    private function getAvailablePaymentDrivers()
    {
        $paymentDriverPath = app_path('PaymentDrivers/');
        $files = glob($paymentDriverPath . '*PaymentDriver.php');
        
        $drivers = [];
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $drivers[] = $className;
        }
        
        return $drivers;
    }

    /**
     * Mask sensitive configuration values for logging
     */
    private function maskSensitiveConfig($config)
    {
        $masked = [];
        $sensitiveFields = ['apiLoginId', 'transactionKey', 'signatureKey', 'apiKey', 'secretKey', 'password'];
        
        // Convert stdClass to array if needed
        $configArray = (array) $config;
        
        foreach ($configArray as $key => $value) {
            if (in_array($key, $sensitiveFields)) {
                if (is_string($value) && strlen($value) > 0) {
                    $masked[$key] = strlen($value) > 4 ? substr($value, 0, 4) . '...' : '***';
                } else {
                    $masked[$key] = empty($value) ? 'Not Set' : 'Set';
                }
            } else {
                $masked[$key] = $value;
            }
        }
        
        return $masked;
    }

    public function importCustomers(TestCompanyGatewayRequest $request, CompanyGateway $company_gateway)
    {

        //Throttle here
        // if (Cache::has("throttle_polling:import_customers:{$company_gateway->company->company_key}:{$company_gateway->hashed_id}")) {
        //     return response()->json(['message' => 'Please wait whilst your previous attempts complete.'], 200);
        // }

        dispatch(function () use ($company_gateway) {
            MultiDB::setDb($company_gateway->company->db);
            $company_gateway->driver()->importCustomers();
        })->afterResponse();

        Cache::put("throttle_polling:import_customers:{$company_gateway->company->company_key}:{$company_gateway->hashed_id}", true, 300);

        return response()->json(['message' => ctrans('texts.import_started')], 200);
    }

}
