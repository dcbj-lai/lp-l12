<?php

namespace App\Services;

use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Http;

class OpenAISecretService
{
    protected SecretsManagerClient $client;

    public function __construct()
    {
        $this->client = new SecretsManagerClient([
            'version' => '2017-10-17',
            'region' => config('services.aws.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
        ]);
    }

    public function getOpenAIKey(): string
    {
        $secretName = 'ibl.ai/staging/openai';

        try {
            $result = $this->client->getSecretValue([
                'SecretId' => $secretName,
            ]);
        } catch (AwsException $e) {
            throw new \Exception('AWS Secrets Manager error: ' . $e->getMessage());
        }

        $secretString = $result['SecretString'] ?? null;

        if (!$secretString) {
            throw new \Exception('SecretString is empty');
        }

        // Try decoding JSON
        $decoded = json_decode($secretString, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (!isset($decoded['OPENAI_API_KEY'])) {
                throw new \Exception('OPENAI_API_KEY not found in secret');
            }

            return $decoded['OPENAI_API_KEY'];
        }

        // Fallback: raw string
        return $secretString;
    }

    public function testOpenAI(): array
    {
        $apiKey = $this->getOpenAIKey();

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/models');

        if (!$response->successful()) {
            throw new \Exception('OpenAI request failed: ' . $response->body());
        }

        return $response->json();
    }
}
