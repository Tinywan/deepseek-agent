<?php

namespace DeepSeek\Wan;

use DeepSeek\Wan\Exceptions\DeepSeekException;

class HttpClient
{
    public function __construct(private readonly Config $config)
    {
    }

    public function request(string $method, string $path, array $body = []): array
    {
        $url = rtrim($this->config->baseUrl, '/') . $path;
        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->config->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 120,
        ]);

        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new DeepSeekException("HTTP request failed: {$error}");
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            $message = $data['error']['message'] ?? $response;
            throw new DeepSeekException("API error [{$httpCode}]: {$message}", $httpCode, null, $data);
        }

        return $data;
    }

    public function streamRequest(string $method, string $path, array $body = []): \Generator
    {
        $url = rtrim($this->config->baseUrl, '/') . $path;
        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->config->apiKey,
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ];

        $chunks = new \SplQueue();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function ($ch, $data) use ($chunks): int {
                $chunks->enqueue($data);
                return strlen($data);
            },
        ]);

        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new DeepSeekException("HTTP request failed: {$error}");
        }

        $buffer = '';
        foreach ($chunks as $rawData) {
            $buffer .= $rawData;

            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $chunk = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                foreach (explode("\n", $chunk) as $line) {
                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);

                        if ($json === '[DONE]') {
                            return;
                        }

                        $decoded = json_decode($json, true);
                        if ($decoded && $httpCode < 400) {
                            yield $decoded;
                        }
                    }
                }
            }
        }

        // Emit any remaining data in buffer
        if (trim($buffer) !== '') {
            $buffer = trim($buffer);
            foreach (explode("\n", $buffer) as $line) {
                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    if ($json !== '[DONE]') {
                        $decoded = json_decode($json, true);
                        if ($decoded && $httpCode < 400) {
                            yield $decoded;
                        }
                    }
                }
            }
        }
    }
}
