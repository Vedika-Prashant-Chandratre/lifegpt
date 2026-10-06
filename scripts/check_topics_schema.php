<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $cols = DB::fetchAll("DESCRIBE lg_interview_topics");
    print_r($cols);
    $topics = DB::fetchAll("SELECT * FROM lg_interview_topics");
    print_r($topics);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
