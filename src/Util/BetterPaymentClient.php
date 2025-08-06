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

	private function getClient(): Client
    {
        return new Client([
            'base_uri' => $this->configReader->getAPIUrl()
        ]);
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey().':'.$this->configReader->getOutgoingKey()),
            'Content-Type' => 'application/json'
        ];
    }

    public function requestPayment(array $parameters)
    {
        $body = json_encode($parameters);
        $request = new Request('POST', 'rest/payment', $this->getHeaders(), $body);
        try {
            $response = $this->getClient()->send($request);
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

    public function getTransaction(string $id) {
        $request = new Request('GET', 'rest/transactions/'.$id, $this->getHeaders());
        try {
            $response = $this->getClient()->send($request);
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

    public function capture(array $parameters) {
		$body = json_encode($parameters);
        $request = new Request('POST', 'rest/capture', $this->getHeaders(), $body);
        try {
            $response = $this->getClient()->send($request);
            $responseBody = json_decode((string) $response->getBody());
            if ($responseBody->error_code == 0) {
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