<?php
/**
 * LifeGPT - Batch Embedding Generation Script
 * 
 * Generates and stores semantic vector embeddings for all eligible RAG knowledge chunks.
 * Safe to run repeatedly (idempotent). Skips chunks already embedded with the active model.
 * 
 * Usage:
 *   php scripts/generate_embeddings.php
 *   php scripts/generate_embeddings.php --force
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/services/EmbeddingService.php';

$force = in_array('--force', $argv ?? []);
$modelName = EmbeddingService::getModelName();

echo "======================================================\n";
echo "   LifeGPT Batch Embedding Generator\n";
echo "======================================================\n";
echo "Active Embedding Model: {$modelName}\n";
echo "Force Regeneration:     " . ($force ? "YES" : "NO") . "\n\n";

// 1. Query eligible chunks based on strict RAG consent & approval rules
$sql = "
    SELECT kc.chunk_id, kc.interview_id, kc.content_type, kc.anonymized_text, kc.embedding, kc.embedding_model
    FROM lg_knowledge_chunks kc
    JOIN lg_interviews i ON kc.interview_id = i.interview_id
    JOIN lg_consents c ON i.interview_id = c.interview_id
    WHERE kc.status = 'approved'
      AND kc.approved_for_rag = 1
      AND c.rag_consent = 1
      AND c.withdrawn = 0
      AND CHAR_LENGTH(TRIM(kc.anonymized_text)) >= 25
      AND LOWER(TRIM(kc.anonymized_text)) NOT LIKE 'greeting%'
    ORDER BY kc.chunk_id ASC
";

$eligibleChunks = DB::fetchAll($sql);
$totalEligible = count($eligibleChunks);
echo "Total eligible RAG chunks in database: {$totalEligible}\n";

// Filter to those needing embeddings
$toProcess = [];
foreach ($eligibleChunks as $chunk) {
    if ($force || empty($chunk['embedding']) || $chunk['embedding_model'] !== $modelName) {
        $toProcess[] = $chunk;
    }
}

$pendingCount = count($toProcess);
echo "Chunks requiring embeddings: {$pendingCount}\n\n";

if ($pendingCount === 0) {
    echo "All eligible chunks are already embedded with model '{$modelName}'. Nothing to do.\n";
    echo "Use --force to regenerate all embeddings.\n";
    exit(0);
}

// 2. Process in batches
$batchSize = 25;
$batches = array_chunk($toProcess, $batchSize);
$totalBatches = count($batches);
$successCount = 0;
$failedChunkIds = [];

$startTime = microtime(true);

foreach ($batches as $bIdx => $batch) {
    $batchNum = $bIdx + 1;
    $texts = array_column($batch, 'anonymized_text');

    echo "Processing batch {$batchNum}/{$totalBatches} (" . count($batch) . " chunks)... ";

    try {
        $vectors = EmbeddingService::embedBatch($texts);

        // Update database with generated vectors
        DB::beginTransaction();
        foreach ($batch as $i => $chunk) {
            $chunkId = $chunk['chunk_id'];
            $vectorJson = json_encode($vectors[$i] ?? []);

            DB::query(
                "UPDATE lg_knowledge_chunks 
                 SET embedding = :emb, embedding_model = :model, embedded_at = CURRENT_TIMESTAMP 
                 WHERE chunk_id = :id",
                [
                    'emb'   => $vectorJson,
                    'model' => $modelName,
                    'id'    => $chunkId,
                ]
            );
            $successCount++;
        }
        DB::commit();
        echo "OK\n";
    } catch (Exception $e) {
        DB::rollBack();
        echo "FAILED: " . $e->getMessage() . "\n";
        foreach ($batch as $chunk) {
            $failedChunkIds[] = $chunk['chunk_id'];
        }
    }
}

$elapsed = round(microtime(true) - $startTime, 2);

echo "\n======================================================\n";
echo "   Embedding Generation Summary\n";
echo "======================================================\n";
echo "Successfully embedded: {$successCount} chunks\n";
echo "Failed chunks:         " . count($failedChunkIds) . "\n";
if (!empty($failedChunkIds)) {
    echo "Failed chunk IDs:      " . implode(', ', $failedChunkIds) . "\n";
}
echo "Elapsed Time:          {$elapsed} seconds\n";
echo "Done!\n";
