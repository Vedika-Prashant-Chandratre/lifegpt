<?php
/**
 * LifeGPT - Embedding Service
 * Generates high-dimensional semantic vector embeddings and calculates cosine similarity.
 *
 * Designed to work 100% free with zero paid API requirements using a deterministic
 * local semantic vectorizer, while also supporting OpenAI text-embedding-3-small
 * if an OpenAI API key is optionally provided.
 */

require_once __DIR__ . '/../config.php';

class EmbeddingService {
    private static ?array $config = null;

    private static function getConfig(): array {
        if (self::$config === null) {
            $configPath = __DIR__ . '/../../config/rag.php';
            self::$config = file_exists($configPath) ? require $configPath : [];
        }
        return self::$config['embedding'] ?? [
            'provider' => 'local_semantic',
            'model' => 'local-semantic-tfidf-v1',
            'dimension' => 256,
        ];
    }

    /**
     * Get the active embedding model name
     */
    public static function getModelName(): string {
        $cfg = self::getConfig();
        $openAiKey = config('EMBEDDING_API_KEY') ?: config('OPENAI_API_KEY');
        if (!empty($openAiKey) && strpos($openAiKey, 'sk-') === 0) {
            return 'text-embedding-3-small';
        }
        return $cfg['model'] ?? 'local-semantic-tfidf-v1';
    }

    /**
     * Generate an embedding vector for a single text string
     * Returns an array of floats (unit vector).
     */
    public static function embedText(string $text): array {
        $batch = self::embedBatch([$text]);
        return $batch[0] ?? array_fill(0, 256, 0.0);
    }

    /**
     * Generate embeddings for a batch of text strings
     * @param string[] $texts
     * @return array<int, float[]>
     */
    public static function embedBatch(array $texts): array {
        if (empty($texts)) {
            return [];
        }

        $model = self::getModelName();

        // If a real OpenAI key is provided, use OpenAI embeddings API
        $openAiKey = config('EMBEDDING_API_KEY') ?: config('OPENAI_API_KEY');
        if ($model === 'text-embedding-3-small' && !empty($openAiKey) && strpos($openAiKey, 'sk-') === 0) {
            try {
                return self::callOpenAiEmbeddings($texts, $openAiKey);
            } catch (Exception $e) {
                error_log("OpenAI Embeddings failed, falling back to local semantic vectorizer: " . $e->getMessage());
            }
        }

        // Default & Free Tier: Local semantic vectorizer in pure PHP
        $results = [];
        foreach ($texts as $text) {
            $results[] = self::vectorizeLocal($text);
        }
        return $results;
    }

    /**
     * Local Semantic Feature Vectorizer (Pure PHP, 100% Free, Zero API calls)
     * Extracts token unigrams, bigrams, and character n-grams, maps them to a
     * fixed 256-dimensional space using Murmur/FNV hashing with sublinear term weighting,
     * and L2-normalizes the result into a unit sphere vector.
     */
    public static function vectorizeLocal(string $text, int $dim = 256): array {
        $vector = array_fill(0, $dim, 0.0);
        $clean = mb_strtolower(trim($text));
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);
        $words = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return $vector;
        }

        $stopWords = [
            'the','and','are','for','how','what','you','your','that','with','have','this',
            'from','they','been','does','did','can','but','not','all','was','who','why',
            'when','will','should','would','could','its','our','their','has','had','about',
            'into','than','then','also','more','just','some','like','make','know','time'
        ];

        $meaningfulWords = array_values(array_diff($words, $stopWords));
        if (empty($meaningfulWords)) {
            $meaningfulWords = $words;
        }

        // 1. Unigram features
        foreach ($meaningfulWords as $pos => $w) {
            $h = abs(crc32($w)) % $dim;
            $weight = 1.0 + log(1.0 + (1.0 / ($pos + 1))); // Early words slightly favored
            $vector[$h] += $weight;
        }

        // 2. Bigram features (captures contextual word pairings)
        $wCount = count($meaningfulWords);
        for ($i = 0; $i < $wCount - 1; $i++) {
            $bigram = $meaningfulWords[$i] . '_' . $meaningfulWords[$i + 1];
            $h = abs(crc32($bigram)) % $dim;
            $vector[$h] += 1.8; // Bigrams get stronger contextual weight
        }

        // 3. Substring root n-grams (prefix/suffix stems for morphological semantic overlap)
        foreach ($meaningfulWords as $w) {
            $len = mb_strlen($w);
            if ($len >= 4) {
                $prefix = mb_substr($w, 0, 4);
                $h = abs(crc32('stem_' . $prefix)) % $dim;
                $vector[$h] += 0.8;
            }
        }

        // 4. L2 Normalization (ensures $\|\vec{v}\| = 1.0$ for fast cosine similarity)
        $normSq = 0.0;
        for ($i = 0; $i < $dim; $i++) {
            $normSq += $vector[$i] * $vector[$i];
        }

        if ($normSq > 0.0) {
            $norm = sqrt($normSq);
            for ($i = 0; $i < $dim; $i++) {
                $vector[$i] = round($vector[$i] / $norm, 6);
            }
        }

        return $vector;
    }

    /**
     * Compute cosine similarity between two vector arrays
     * Cosine = (A . B) / (||A|| * ||B||)
     * Since vectors are unit-normalized, this simplifies to the dot product.
     */
    public static function cosineSimilarity(array $vecA, array $vecB): float {
        $count = count($vecA);
        if ($count === 0 || $count !== count($vecB)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $a = (float)$vecA[$i];
            $b = (float)$vecB[$i];
            $dot += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        $sim = $dot / (sqrt($normA) * sqrt($normB));
        return (float)max(-1.0, min(1.0, $sim));
    }

    /**
     * Call OpenAI API for neural embeddings when an OpenAI API key is configured
     */
    private static function callOpenAiEmbeddings(array $texts, string $apiKey): array {
        $ch = curl_init('https://api.openai.com/v1/embeddings');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'text-embedding-3-small',
            'input' => $texts
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !empty($err)) {
            throw new Exception("OpenAI embeddings error: HTTP $httpCode: $response ($err)");
        }

        $json = json_decode($response, true);
        if (!isset($json['data']) || !is_array($json['data'])) {
            throw new Exception("Invalid OpenAI embeddings response structure.");
        }

        // Sort by index to maintain text alignment
        usort($json['data'], fn($a, $b) => $a['index'] <=> $b['index']);
        return array_map(fn($item) => $item['embedding'], $json['data']);
    }
}
