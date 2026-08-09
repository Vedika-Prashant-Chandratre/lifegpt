<?php
/**
 * LifeGPT - OpenAI API Client Wrapper
 * Communicates with the OpenAI API strictly from the PHP backend.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class OpenAIClient {
    /**
     * Call the OpenAI API chat completion endpoint
     * Returns the structured response or throws an exception
     */
    public static function chatCompletion(array $messages, array $responseSchema = null): array {
        $apiKey = config('OPENAI_API_KEY');
        
        // Check if API key is valid or placeholder
        if (empty($apiKey) || $apiKey === 'your_openai_api_key_here' || strpos($apiKey, 'your_') === 0) {
            throw new Exception("OpenAI API key not configured. Mock Mode active.");
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Prepare payload
        $payload = [
            'model' => 'gpt-4o-mini', // Cost-effective fast model
            'messages' => $messages,
            'temperature' => 0.7
        ];

        // If structured output is requested, enforce JSON schema
        if ($responseSchema) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'interview_response',
                    'strict' => true,
                    'schema' => $responseSchema
                ]
            ];
        } else {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];

        $startTime = microtime(true);
        
        // Execute HTTP Request via cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Handle SSL locally for XAMPP
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        $endTime = microtime(true);
        $latencyMs = round(($endTime - $startTime) * 1000);

        if ($err) {
            self::logUsage('error', 'gpt-4o-mini', 0, $latencyMs, 'failed', 0.0);
            throw new Exception("cURL Error: " . $err);
        }

        if ($httpCode !== 200) {
            self::logUsage('error', 'gpt-4o-mini', 0, $latencyMs, 'failed_code_' . $httpCode, 0.0);
            error_log("OpenAI API returned error code $httpCode. Response: " . $response);
            throw new Exception("OpenAI API Error: Received HTTP status code " . $httpCode);
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            self::logUsage('error', 'gpt-4o-mini', 0, $latencyMs, 'malformed', 0.0);
            throw new Exception("OpenAI API returned malformed JSON response.");
        }

        $content = $data['choices'][0]['message']['content'];
        $promptTokens = $data['usage']['prompt_tokens'] ?? 0;
        $completionTokens = $data['usage']['completion_tokens'] ?? 0;
        $totalTokens = $promptTokens + $completionTokens;
        
        // Estimate cost: gpt-4o-mini rates: $0.150 / 1M input, $0.600 / 1M output tokens
        $costEstimate = (($promptTokens * 0.15) + ($completionTokens * 0.60)) / 1000000;

        self::logUsage('chat_completion', 'gpt-4o-mini', $totalTokens, $latencyMs, 'success', $costEstimate);

        return json_decode($content, true) ?: [];
    }

    /**
     * Log OpenAI token usage, latency, and estimated cost in lg_ai_usage
     */
    private static function logUsage(string $type, string $model, int $tokens, int $latency, string $status, float $cost): void {
        try {
            DB::insert(
                "INSERT INTO lg_ai_usage (request_type, model, tokens, latency, status, cost_estimate) 
                 VALUES (:type, :model, :tokens, :latency, :status, :cost)",
                [
                    'type' => $type,
                    'model' => $model,
                    'tokens' => $tokens,
                    'latency' => $latency,
                    'status' => $status,
                    'cost' => $cost
                ]
            );
        } catch (Exception $e) {
            // Log database error silently to not interrupt the user
            error_log("Failed to insert AI usage stats: " . $e->getMessage());
        }
    }
}
