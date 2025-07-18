<?php

namespace Stripe;

/**
 * Client used to send requests to Stripe's API.
 *
 * @property \Stripe\Service\OAuthService $oauth
 * // The beginning of the section generated from our OpenAPI spec
 * @property \Stripe\Service\AccountLinkService $accountLinks
 * @property \Stripe\Service\AccountService $accounts
 * @property \Stripe\Service\AccountSessionService $accountSessions
 * @property \Stripe\Service\ApplePayDomainService $applePayDomains
 * @property \Stripe\Service\ApplicationFeeService $applicationFees
 * @property \Stripe\Service\Apps\AppsServiceFactory $apps
 * @property \Stripe\Service\BalanceService $balance
 * @property \Stripe\Service\BalanceTransactionService $balanceTransactions
 * @property \Stripe\Service\Billing\BillingServiceFactory $billing
 * @property \Stripe\Service\BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property \Stripe\Service\ChargeService $charges
 * @property \Stripe\Service\Checkout\CheckoutServiceFactory $checkout
 * @property \Stripe\Service\Climate\ClimateServiceFactory $climate
 * @property \Stripe\Service\ConfirmationTokenService $confirmationTokens
 * @property \Stripe\Service\CountrySpecService $countrySpecs
 * @property \Stripe\Service\CouponService $coupons
 * @property \Stripe\Service\CreditNoteService $creditNotes
 * @property \Stripe\Service\CustomerService $customers
 * @property \Stripe\Service\CustomerSessionService $customerSessions
 * @property \Stripe\Service\DisputeService $disputes
 * @property \Stripe\Service\Entitlements\EntitlementsServiceFactory $entitlements
 * @property \Stripe\Service\EphemeralKeyService $ephemeralKeys
 * @property \Stripe\Service\EventService $events
 * @property \Stripe\Service\ExchangeRateService $exchangeRates
 * @property \Stripe\Service\FileLinkService $fileLinks
 * @property \Stripe\Service\FileService $files
 * @property \Stripe\Service\FinancialConnections\FinancialConnectionsServiceFactory $financialConnections
 * @property \Stripe\Service\Forwarding\ForwardingServiceFactory $forwarding
 * @property \Stripe\Service\Identity\IdentityServiceFactory $identity
 * @property \Stripe\Service\InvoiceItemService $invoiceItems
 * @property \Stripe\Service\InvoiceService $invoices
 * @property \Stripe\Service\Issuing\IssuingServiceFactory $issuing
 * @property \Stripe\Service\MandateService $mandates
 * @property \Stripe\Service\PaymentIntentService $paymentIntents
 * @property \Stripe\Service\PaymentLinkService $paymentLinks
 * @property \Stripe\Service\PaymentMethodConfigurationService $paymentMethodConfigurations
 * @property \Stripe\Service\PaymentMethodDomainService $paymentMethodDomains
 * @property \Stripe\Service\PaymentMethodService $paymentMethods
 * @property \Stripe\Service\PayoutService $payouts
 * @property \Stripe\Service\PlanService $plans
 * @property \Stripe\Service\PriceService $prices
 * @property \Stripe\Service\ProductService $products
 * @property \Stripe\Service\PromotionCodeService $promotionCodes
 * @property \Stripe\Service\QuoteService $quotes
 * @property \Stripe\Service\Radar\RadarServiceFactory $radar
 * @property \Stripe\Service\RefundService $refunds
 * @property \Stripe\Service\Reporting\ReportingServiceFactory $reporting
 * @property \Stripe\Service\ReviewService $reviews
 * @property \Stripe\Service\SetupAttemptService $setupAttempts
 * @property \Stripe\Service\SetupIntentService $setupIntents
 * @property \Stripe\Service\ShippingRateService $shippingRates
 * @property \Stripe\Service\Sigma\SigmaServiceFactory $sigma
 * @property \Stripe\Service\SourceService $sources
 * @property \Stripe\Service\SubscriptionItemService $subscriptionItems
 * @property \Stripe\Service\SubscriptionService $subscriptions
 * @property \Stripe\Service\SubscriptionScheduleService $subscriptionSchedules
 * @property \Stripe\Service\Tax\TaxServiceFactory $tax
 * @property \Stripe\Service\TaxCodeService $taxCodes
 * @property \Stripe\Service\TaxIdService $taxIds
 * @property \Stripe\Service\TaxRateService $taxRates
 * @property \Stripe\Service\Terminal\TerminalServiceFactory $terminal
 * @property \Stripe\Service\TestHelpers\TestHelpersServiceFactory $testHelpers
 * @property \Stripe\Service\TokenService $tokens
 * @property \Stripe\Service\TopupService $topups
 * @property \Stripe\Service\TransferService $transfers
 * @property \Stripe\Service\Treasury\TreasuryServiceFactory $treasury
 * @property \Stripe\Service\WebhookEndpointService $webhookEndpoints
 * // The end of the section generated from our OpenAPI spec
 */
class StripeClient extends BaseStripeClient {

	/**
	 * @var \Stripe\Service\CoreServiceFactory
	 */
	private $coreServiceFactory;

	public function __get( $name ) {
		return $this->getService( $name );
	}

	public function getService( $name ) {
		if ( null === $this->coreServiceFactory ) {
			$this->coreServiceFactory = new \Stripe\Service\CoreServiceFactory( $this );
		}

		return $this->coreServiceFactory->getService( $name );
	}
}
