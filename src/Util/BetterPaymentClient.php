<?php declare(strict_types=1);

namespace BetterPayment\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;

class BetterPaymentClient
{
	private ConfigReader $configReader;

	public function __construct(
        ConfigReader $configReader,
    ) {
		$this->configReader = $configReader;
	}

	private function getClient(?string $salesChannelId = null): Client
    {
        return new Client([
            'base_uri' => $this->configReader->getAPIUrl($salesChannelId)
        ]);
    }

    private function getHeaders(?string $salesChannelId = null): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey($salesChannelId).':'.$this->configReader->getOutgoingKey($salesChannelId)),
            'Content-Type' => 'application/json'
        ];
    }

    public function requestPayment(array $parameters, ?string $salesChannelId = null)
    {
        $body = json_encode($parameters);
        $request = new Request('POST', 'rest/payment', $this->getHeaders($salesChannelId), $body);
        try {
            $response = $this->getClient($salesChannelId)->send($request);
            $responseBody = json_decode((string) $response->getBody(), true);
            if ($responseBody['error_code'] == 0) {
                return $responseBody;
            }
            else {
                throw new RuntimeException('Better Payment Client ERROR: ' . $response->getBody());
            }
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Better Payment Client ERROR: ' . $exception->getMessage());
        }
    }

    public function getTransaction(string $id, ?string $salesChannelId = null) {
        $request = new Request('GET', 'rest/transactions/'.$id, $this->getHeaders($salesChannelId));
        try {
            $response = $this->getClient($salesChannelId)->send($request);
            $responseBody = json_decode((string) $response->getBody(), true);
            if (!isset($responseBody['error_code'])) {
                return $responseBody;
            }
            else {
                throw new RuntimeException('Better Payment Client ERROR: ' . $response->getBody());
            }
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Better Payment Client ERROR: ' . $exception->getMessage());
        }
    }

    public function capture(array $parameters, ?string $salesChannelId = null) {
		$body = json_encode($parameters);
        $request = new Request('POST', 'rest/capture', $this->getHeaders($salesChannelId), $body);
        try {
            $response = $this->getClient($salesChannelId)->send($request);
            $responseBody = json_decode((string) $response->getBody(), true);
            if ($responseBody['error_code'] == 0) {
                return $responseBody;
            }
            else {
                throw new RuntimeException('Better Payment Client ERROR: ' . $response->getBody());
            }
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Better Payment Client ERROR: ' . $exception->getMessage());
        }
    }
}