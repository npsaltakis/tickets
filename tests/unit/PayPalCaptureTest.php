<?php

use App\Libraries\PayPalClient;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * PayPalClient::captureOrder is shared by the browser (onApprove) and the CHECKOUT.ORDER.APPROVED
 * webhook, so the second caller must get the existing capture back instead of a failure.
 *
 * @internal
 */
final class PayPalCaptureTest extends CIUnitTestCase
{
    private const ENV = [
        'paypal.clientId' => 'client',
        'paypal.secret'   => 'secret',
        'paypal_secret'   => 'secret',
        'paypal.baseUrl'  => 'https://api-m.sandbox.paypal.com',
    ];

    private object $http;

    /** @var array<string, mixed> values from the local .env, restored after each test */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENV as $name => $value) {
            $this->savedEnv[$name] = $_ENV[$name] ?? null;
            $_ENV[$name] = $value;
        }

        $this->http = new class () {
            /** @var list<array{0: string, 1: string, 2: array}> */
            public array $calls = [];

            /** @var array<string, array{0: int, 1: array}> URL suffix => [status, body] */
            public array $responses = [];

            public function post(string $url, array $options = []): object
            {
                return $this->respond('POST', $url, $options);
            }

            public function get(string $url, array $options = []): object
            {
                return $this->respond('GET', $url, $options);
            }

            private function respond(string $method, string $url, array $options): object
            {
                $this->calls[] = [$method, $url, $options];

                foreach ($this->responses as $suffix => [$status, $body]) {
                    if (str_ends_with($url, $suffix)) {
                        return new class ($status, $body) {
                            public function __construct(private int $status, private array $body)
                            {
                            }

                            public function getStatusCode(): int
                            {
                                return $this->status;
                            }

                            public function getBody(): string
                            {
                                return json_encode($this->body);
                            }
                        };
                    }
                }

                throw new RuntimeException('Unexpected request ' . $method . ' ' . $url);
            }
        };

        $this->http->responses['/v1/oauth2/token'] = [200, ['access_token' => 'token']];
        Services::injectMock('curlrequest', $this->http);
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $name => $value) {
            if ($value === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $value;
            }
        }

        Services::reset();
        parent::tearDown();
    }

    public function testCapturesApprovedOrder(): void
    {
        $this->http->responses['/v2/checkout/orders/ORDER1/capture'] = [201, $this->order('event:5|user:9|seats:2|donation:10.00')];

        [$capture, $customId, $error] = (new PayPalClient())->captureOrder('ORDER1');

        $this->assertNull($error);
        $this->assertSame('CAP1', $capture['id']);
        $this->assertSame('event:5|user:9|seats:2|donation:10.00', $customId);

        // Same idempotency key from both callers, so PayPal hands back the first capture.
        [, , $options] = $this->http->calls[1];
        $this->assertSame('capture-ORDER1', $options['headers']['PayPal-Request-Id']);
    }

    public function testReturnsExistingCaptureWhenAlreadyCaptured(): void
    {
        $this->http->responses['/v2/checkout/orders/ORDER1/capture'] = [422, [
            'name'    => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => 'ORDER_ALREADY_CAPTURED']],
        ]];
        $this->http->responses['/v2/checkout/orders/ORDER1'] = [200, $this->order('event:5|user:9|seats:1|donation:7.50')];

        [$capture, $customId, $error] = (new PayPalClient())->captureOrder('ORDER1');

        $this->assertNull($error);
        $this->assertSame('CAP1', $capture['id']);
        $this->assertSame('event:5|user:9|seats:1|donation:7.50', $customId);
    }

    public function testDeclinedPaymentIsRejected(): void
    {
        $this->http->responses['/v2/checkout/orders/ORDER1/capture'] = [422, [
            'name'    => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => 'INSTRUMENT_DECLINED']],
        ]];

        $this->assertSame([null, '', 'rejected'], (new PayPalClient())->captureOrder('ORDER1'));
    }

    public function testMissingConfigurationIsReported(): void
    {
        $_ENV['paypal.secret'] = '';
        $_ENV['paypal_secret'] = '';

        $this->assertSame([null, '', 'config'], (new PayPalClient())->captureOrder('ORDER1'));
        $this->assertSame([], $this->http->calls);
    }

    private function order(string $customId): array
    {
        return [
            'id'             => 'ORDER1',
            'status'         => 'COMPLETED',
            'purchase_units' => [[
                'custom_id' => $customId,
                'payments'  => ['captures' => [[
                    'id'     => 'CAP1',
                    'status' => 'COMPLETED',
                    'amount' => ['currency_code' => 'EUR', 'value' => '20.00'],
                ]]],
            ]],
        ];
    }
}
