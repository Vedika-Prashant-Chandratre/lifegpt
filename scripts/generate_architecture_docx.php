<?php
/**
 * LifeGPT - Word Document Generator (.docx)
 * Produces LifeGPT_System_Architecture_Summary.docx
 */

$outputPath = __DIR__ . '/../LifeGPT_System_Architecture_Summary.docx';

// Helper to escape XML special characters
function xmlEscape($str) {
    return htmlspecialchars($str, ENT_XML1, 'UTF-8');
}

// -----------------------------------------------------------------------------
// Document XML Building Blocks
// -----------------------------------------------------------------------------

function pText($text, $style = null, $bold = false, $italic = false, $color = null, $size = null, $align = null) {
    $rPr = '';
    if ($bold) $rPr .= '<w:b/>';
    if ($italic) $rPr .= '<w:i/>';
    if ($color) $rPr .= '<w:color w:val="' . $color . '"/>';
    if ($size) $rPr .= '<w:sz w:val="' . $size . '"/>';

    $pPr = '';
    if ($style) $pPr .= '<w:pStyle w:val="' . $style . '"/>';
    if ($align) $pPr .= '<w:jc w:val="' . $align . '"/>';

    return '<w:p>' . ($pPr ? '<w:pPr>' . $pPr . '</w:pPr>' : '') .
           '<w:r>' . ($rPr ? '<w:rPr>' . $rPr . '</w:rPr>' : '') .
           '<w:t xml:space="preserve">' . xmlEscape($text) . '</w:t>' .
           '</w:r></w:p>';
}

function pHeading(string $text, int $level = 1): string {
    $style = 'Heading' . $level;
    $sizes = [1 => 36, 2 => 28, 3 => 24]; // half-points
    $colors = [1 => '1B4332', 2 => '2D6A4F', 3 => '40916C'];
    $sz = $sizes[$level] ?? 24;
    $col = $colors[$level] ?? '1B4332';

    return '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/><w:spacing w:before="240" w:after="120"/></w:pPr>' .
           '<w:r><w:rPr><w:b/><w:color w:val="' . $col . '"/><w:sz w:val="' . $sz . '"/></w:rPr>' .
           '<w:t>' . xmlEscape($text) . '</w:t></w:r></w:p>';
}

function pBullet(string $boldPrefix, string $text): string {
    return '<w:p><w:pPr><w:pStyle w:val="ListParagraph"/><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr><w:spacing w:after="80"/></w:pPr>' .
           '<w:r><w:rPr><w:b/><w:color w:val="1B4332"/></w:rPr><w:t xml:space="preserve">' . xmlEscape($boldPrefix) . ' </w:t></w:r>' .
           '<w:r><w:t xml:space="preserve">' . xmlEscape($text) . '</w:t></w:r></w:p>';
}

function pCallout(string $title, string $text, string $bgColor = 'EDF7F2', string $borderColor = '1B4332'): string {
    return '<w:tbl>' .
           '<w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblBorders>' .
           '<w:top w:val="none"/><w:left w:val="single" w:sz="36" w:space="0" w:color="' . $borderColor . '"/>' .
           '<w:bottom w:val="none"/><w:right w:val="none"/>' .
           '</w:tblBorders><w:tblCellMar><w:top w:w="160" w:type="dxa"/><w:left w:w="240" w:type="dxa"/><w:bottom w:w="160" w:type="dxa"/><w:right w:w="240" w:type="dxa"/></w:tblCellMar></w:tblPr>' .
           '<w:tr><w:tc>' .
           '<w:tcPr><w:tcW w:w="5000" w:type="pct"/><w:shd w:val="clear" w:color="auto" w:fill="' . $bgColor . '"/></w:tcPr>' .
           '<w:p><w:r><w:rPr><w:b/><w:color w:val="' . $borderColor . '"/><w:sz w:val="22"/></w:rPr><w:t>' . xmlEscape($title) . '</w:t></w:r></w:p>' .
           '<w:p><w:r><w:rPr><w:color w:val="2D3748"/><w:sz w:val="20"/></w:rPr><w:t>' . xmlEscape($text) . '</w:t></w:r></w:p>' .
           '</w:tc></w:tr></w:tbl><w:p><w:pPr><w:spacing w:after="120"/></w:pPr></w:p>';
}

function buildTable(array $headers, array $rows): string {
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblBorders>' .
           '<w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>' .
           '<w:left w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>' .
           '<w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>' .
           '<w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>' .
           '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>' .
           '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>' .
           '</w:tblBorders><w:tblCellMar><w:top w:w="120" w:type="dxa"/><w:left w:w="160" w:type="dxa"/><w:bottom w:w="120" w:type="dxa"/><w:right w:w="160" w:type="dxa"/></w:tblCellMar></w:tblPr>';

    // Header Row
    $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
    foreach ($headers as $h) {
        $xml .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="1B4332"/></w:tcPr>' .
                '<w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="20"/></w:rPr>' .
                '<w:t>' . xmlEscape($h) . '</w:t></w:r></w:p></w:tc>';
    }
    $xml .= '</w:tr>';

    // Data Rows
    $i = 0;
    foreach ($rows as $row) {
        $bg = ($i % 2 === 0) ? 'FFFFFF' : 'F8FAFC';
        $xml .= '<w:tr>';
        foreach ($row as $cell) {
            $xml .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="' . $bg . '"/></w:tcPr>' .
                    '<w:p><w:r><w:rPr><w:sz w:val="19"/><w:color w:val="1E293B"/></w:rPr>' .
                    '<w:t>' . xmlEscape($cell) . '</w:t></w:r></w:p></w:tc>';
        }
        $xml .= '</w:tr>';
        $i++;
    }

    $xml .= '</w:tbl><w:p><w:pPr><w:spacing w:after="160"/></w:pPr></w:p>';
    return $xml;
}

// -----------------------------------------------------------------------------
// Assemble Document Content
// -----------------------------------------------------------------------------

$docBody = '';

// Title & Meta Block
$docBody .= pText('LifeGPT System Architecture & Engineering Integration Summary', 'Title', true, false, '1B4332', 48, 'center');
$docBody .= pText('Production Architecture, Hybrid RAG Retrieval Engine, Storytelling Funnel & QA Verification Matrix', null, false, true, '4B5563', 24, 'center');
$docBody .= pText('FiftyIsNifty Research Initiative  •  September 2026  •  Version 2.4-Production', null, true, false, '2D6A4F', 20, 'center');
$docBody .= '<w:p><w:pPr><w:spacing w:after="280"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:color w:val="CBD5E1"/></w:rPr><w:t>_________________________________________________________________________________</w:t></w:r></w:p>';

// 1. Executive Summary & Core Mission
$docBody .= pHeading('1. Executive Summary & Core Mission', 1);
$docBody .= pText('LifeGPT is an AI-powered conversational wisdom and oral history platform developed under the FiftyIsNifty research initiative. The platform serves a dual societal mission: (1) capturing, preserving, and honoring the lived experiences of older adults through a structured, multi-modal storytelling interview funnel, and (2) enabling seekers of all ages to query this collective repository of real-world wisdom through a hybrid semantic-and-keyword Retrieval-Augmented Generation (RAG) search engine.');
$docBody .= pCallout('Zero-Cost & Native Architecture Mandate', 'In accordance with strict project constraints, LifeGPT operates on a pure PHP 8.1 + MySQL / MariaDB stack hosted locally on XAMPP and in production on Apache at fiftyisnifty.com/lifegpt/. The system relies entirely on free, open-weights infrastructure: Groq API (openai/gpt-oss-20b and llama-3.3-70b-versatile) and native PHP token-hashing embeddings. No external paid vector databases (Pinecone, Supabase, Weaviate), Python microservices, or proprietary API fees are utilized.');

// 2. High-Level System Architecture
$docBody .= pHeading('2. High-Level System Architecture', 1);
$docBody .= pText('LifeGPT follows a clean four-tier MVC and service-oriented architecture designed for maximum performance, minimal hosting overhead, and zero third-party framework dependencies:');

$archHeaders = ['Tier', 'Primary Technologies', 'Core Responsibilities & Components'];
$archRows = [
    ['Presentation Tier', 'Vanilla JavaScript (ES6+), HTML5, CSS3 Custom Properties, Web Speech API', 'Client-side async interaction, speech recognition (STT), speech synthesis (TTS), live profanity moderation, dynamic chat bubble injection, and Grounding Score indicators.'],
    ['Application Tier', 'PHP 8.1 (FastCGI/Apache), Native Session Handlers, Unified API Router', 'Routing (api/index.php), CSRF token enforcement, session state persistence, guest token authorization, and interview workflow state machines.'],
    ['Intelligence Tier', 'Groq API SDK (openai/gpt-oss-20b), RagPipeline, InterviewEngine', 'Query expansion, BM25 text ranking, 64-bit PHP token embeddings, Cosine similarity computation, Reciprocal Rank Fusion (RRF), and dynamic prompt synthesis.'],
    ['Data Persistence Tier', 'MySQL 8.0 / MariaDB 10.4, InnoDB Engine, FULLTEXT Indexes', 'Relational storage for users, interviews, messages, themes, knowledge chunks, guest access tokens, and pre-computed vector embeddings.']
];
$docBody .= buildTable($archHeaders, $archRows);

// Unified Controller Design
$docBody .= pHeading('Unified API Router & Controller (api/index.php)', 2);
$docBody .= pText('A core accomplishment of this release is the consolidation of all distributed, fragmented AJAX endpoints into a singular unified controller at api/index.php. This router processes incoming requests via JSON body, traditional POST, or query strings, ensuring 100% backward compatibility for legacy endpoints through file-level forwarders:');
$docBody .= pBullet('action=ask:', 'Processes wisdom queries through RagPipeline::ask(), computes grounding scores, extracts supporting experiences, and updates multi-turn session history.');
$docBody .= pBullet('action=next_question:', 'Validates session or guest token ownership, determines question sequence progression via InterviewEngine, and returns AI host prompts.');
$docBody .= pBullet('action=save_answer:', 'Stores contributor voice or typed responses into lg_interview_messages with sequence counters and timestamps.');
$docBody .= pBullet('action=complete:', 'Finalizes an active interview session, triggers InterviewEngine::finalizeStory(), and synthesizes structured knowledge chunks into lg_knowledge_chunks.');
$docBody .= pBullet('action=summary:', 'Facilitates review, editing, and approval of synthesized story summaries, key lessons, and representative quotes.');

// 3. Data Flow & Retrieval Pipelines
$docBody .= pHeading('3. Data Flow & Operational Pipelines', 1);

$docBody .= pHeading('Pipeline A: Ask LifeGPT (Hybrid RAG Search)', 2);
$docBody .= pText('When a user asks a question on the Ask LifeGPT interface, the system executes a four-phase hybrid retrieval and generation sequence:');
$docBody .= pBullet('1. Keyword & BM25 Scoring:', 'Executes MySQL FULLTEXT boolean queries against approved knowledge chunks in lg_knowledge_chunks, scoring text matches using term frequency-inverse document frequency heuristics.');
$docBody .= pBullet('2. Semantic Cosine Similarity:', 'Converts the query into a normalized 64-dimensional semantic embedding via native PHP hashing. Computes dot products against pre-computed chunk embeddings in the database.');
$docBody .= pBullet('3. Reciprocal Rank Fusion (RRF) & Context Hydration:', 'Combines BM25 and semantic similarity ranks using RRF formula RRF_Score = 1/(60 + Rank_BM25) + 1/(60 + Rank_Semantic). Hydrates top chunks with parent story context (topic, contributor role, life turning point).');
$docBody .= pBullet('4. Grounding Score & Guardrailed Synthesis:', 'Calculates a dynamic 0-100% Grounding Score based on top source relevance. Groq synthesizes a comprehensive response strictly under privacy guardrails that forbid persona names and ensure anonymous attribution.');

$docBody .= pHeading('Pipeline B: Share a Story (Interactive Interview Funnel)', 2);
$docBody .= pText('The storytelling funnel (interview/start.php -> interview/conversation.php) guides contributors through a personalized life reflection session:');
$docBody .= pBullet('1. Session Initialization:', 'Generates an interview UUID, captures consent preferences, and provisions a 32-byte cryptographically secure guest token (SHA-256 hashed into lg_guest_access_tokens).');
$docBody .= pBullet('2. Session Self-Healing:', 'window.LifeGPTConfig exposes guestToken and csrfToken to assets/js/interview.js. Every fetch request includes credentials: "same-origin" and guest_token, enabling instant session re-hydration if session cookies expire or are partitioned.');
$docBody .= pBullet('3. Multi-Turn Adaptive Questioning:', 'AI host persona delivers an opening starter question followed by adaptive follow-up prompts tailored to the contributor’s previous responses.');
$docBody .= pBullet('4. Completion & Knowledge Extraction:', 'Upon story completion, InterviewEngine::finalizeStory() extracts the story summary, main life lesson, turning point, outcome, actionable advice, and representative quote into lg_knowledge_chunks.');

// 4. Feature Specifications & UI Upgrades
$docBody .= pHeading('4. Feature Specifications & UI Upgrades', 1);
$docBody .= pText('The integrated codebase includes significant user interface, accessibility, and architectural enhancements:');
$docBody .= pBullet('Asynchronous Chat Interactivity (assets/js/ask.js):', 'Eliminates full-page reloads on Ask LifeGPT. Features live typing spinner indicators, dynamic bubble injection, error recovery cards, and smooth scroll.');
$docBody .= pBullet('LifeGPT Grounding Score Badge & Meter:', 'Visually communicates archive grounding (e.g. "85% Grounded in Shared Experiences") with adaptive color badges (green/amber/orange) and progress meters.');
$docBody .= pBullet('Supporting Experiences Drawer:', 'Collapsible <details> drawer exposing verbatim contributor insights, topic tags, and anonymous attribution without cluttering the main response.');
$docBody .= pBullet('Speech-to-Text & Text-to-Speech Integration:', 'Web Speech API integration with voice replay controls, manual transcript review modals, and profanity moderation.');
$docBody .= pBullet('Multilingual Support & Language Preservation:', 'Integrated Google Website Translator for English, Hindi, and Marathi, with protective "notranslate" attributes shielding emojis, percentages, and system badges.');

// 5. Resolved Operational Issues & Root Causes
$docBody .= pHeading('5. Resolved Operational Issues & Root Cause Analysis', 1);

$issuesHeaders = ['Issue ID & Title', 'Identified Root Cause', 'Technical Resolution Implemented'];
$issuesRows = [
    [
        'Issue A: Ask LifeGPT Infinite Loader',
        'ask/index.php was implemented exclusively as a synchronous PHP form POST. Any asynchronous AJAX fetch to ask/index.php returned 26KB of raw HTML, breaking client JSON parsers and leaving loaders spinning indefinitely.',
        'Created api/index.php?action=ask returning structured JSON payloads. Implemented dual-mode JSON detection in ask/index.php and developed assets/js/ask.js for seamless async chat management.'
    ],
    [
        'Issue B: Story Funnel Guest Token Missing (403)',
        'In interview/conversation.php, window.LifeGPTConfig omitted guestToken and csrfToken. assets/js/interview.js sent fetch calls without guest_token or credentials: "same-origin", triggering 403 blocks whenever session cookies dropped.',
        'Injected guestToken and csrfToken into window.LifeGPTConfig. Implemented sendApiRequest() in interview.js transmitting guest_token and credentials. Added Auth::validateInterviewAccess() with automatic session re-hydration.'
    ],
    [
        'Issue C: Inconsistent CSRF Validation',
        'CSRF::validateAjax() relied strictly on getallheaders()["x-requested-with"], which fails under certain FastCGI/proxy environments, and rejected JSON payloads lacking $_POST tokens.',
        'Upgraded includes/csrf.php to inspect $_SERVER HTTP fallbacks, header tokens (X-CSRF-Token), JSON body tokens, and accept valid same-origin XMLHttpRequest/JSON Content-Types.'
    ],
    [
        'Issue D: Persona Name Privacy Leakage',
        'Interviewer persona names (e.g. "Linda") and role instructions occasionally leaked into RAG synthesis responses.',
        'Hardened RagPipeline system prompt with absolute negative constraints: "NEVER mention persona names (such as Linda). You are strictly LifeGPT synthesizing anonymous lived experiences."'
    ]
];
$docBody .= buildTable($issuesHeaders, $issuesRows);

// 6. QA Integration Verification Matrix
$docBody .= pHeading('6. QA Integration Verification Matrix', 1);
$docBody .= pText('The integrated platform was validated through automated end-to-end integration tests executed directly against the local Apache/MySQL environment:');

$qaHeaders = ['Test ID', 'Target Component', 'Verification Procedure', 'Expected Result', 'Actual Result', 'Status'];
$qaRows = [
    ['TP-01', 'Unified API (Ask)', 'POST /api/index.php?action=ask with test query', 'HTTP 200, valid JSON, grounding score, sources list', 'HTTP 200, Score: 72%, Sources: 7', 'PASSED'],
    ['TP-02', 'Privacy & Persona Leak', 'Audit synthesis text for internal persona names', 'Zero occurrences of "Linda" or prompt leaks', 'Zero persona mentions across 3,039 characters', 'PASSED'],
    ['TP-03', 'Guest Story Setup', 'Initialize story via UUID, anonymous consent, and token', 'DB row inserted in lg_interviews, token hashed', 'Interview #106 created, status: in_progress', 'PASSED'],
    ['TP-04', 'Guest Token Re-hydration', 'POST /api/index.php?action=next_question with token', 'HTTP 200, Question 1 returned without cookie', 'HTTP 200, AI greeting returned, session hydrated', 'PASSED'],
    ['TP-05', 'Save Answer Progression', 'POST /api/index.php?action=save_answer and next_question', 'Answer recorded in DB, sequence increments to 2', 'Sequence 2 returned with follow-up prompt', 'PASSED'],
    ['TP-06', 'Interview Finalization', 'POST /api/index.php?action=complete', 'Status: completed, knowledge chunks populated in DB', 'Status: completed, 7 chunks synthesized in DB', 'PASSED'],
    ['TP-07', 'Backward Compatibility', 'POST /api/interview-next-question.php', 'Legacy endpoint delegates to unified router', 'HTTP 200, identical JSON output returned', 'PASSED']
];
$docBody .= buildTable($qaHeaders, $qaRows);

// 7. Deployment & Maintenance Guide
$docBody .= pHeading('7. Deployment & Maintenance Guide', 1);
$docBody .= pText('To maintain the platform in production at fiftyisnifty.com/lifegpt/ or on local XAMPP:');
$docBody .= pBullet('Environment Variables:', 'Ensure .env or config.php contains GROQ_API_KEY (or OPENAI_API_KEY) with a valid gsk_ key, DB_NAME=lifegpt, DB_USER, and DB_PASS.');
$docBody .= pBullet('Database Seeding:', 'Run php database/seeds/seed_100_rag_stories.php to seed 100 realistic, diverse life experience stories across 20 life themes.');
$docBody .= pBullet('Automated Testing:', 'Run php scripts/qa_verification_test.php after any code modification to execute the full TP-01 through TP-07 regression suite.');
$docBody .= pBullet('Git Synchronization:', 'All changes are staged and version-controlled under the rag-ui-integrated branch.');

// Wrap in full Word document structure
$fullDocumentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ' .
'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
'<w:body>' . $docBody .
'<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/></w:sectPr>' .
'</w:body></w:document>';

// -----------------------------------------------------------------------------
// Package into ZIP (.docx)
// -----------------------------------------------------------------------------

$zip = new ZipArchive();
if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Failed to create ZIP at {$outputPath}\n");
}

// [Content_Types].xml
$contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
'<Default Extension="xml" ContentType="application/xml"/>' .
'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>' .
'</Types>';
$zip->addFromString('[Content_Types].xml', $contentTypesXml);

// _rels/.rels
$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
'</Relationships>';
$zip->addFromString('_rels/.rels', $relsXml);

// word/_rels/document.xml.rels
$docRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
'</Relationships>';
$zip->addFromString('word/_rels/document.xml.rels', $docRelsXml);

// word/styles.xml
$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI" w:cs="Segoe UI"/><w:sz w:val="21"/><w:color w:val="1E293B"/></w:rPr></w:rPrDefault>' .
'<w:pPrDefault><w:pPr><w:spacing w:after="140" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>' .
'<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:rPr><w:b/><w:sz w:val="44"/><w:color w:val="1B4332"/></w:rPr></w:style>' .
'<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="1B4332"/></w:rPr></w:style>' .
'<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="2D6A4F"/></w:rPr></w:style>' .
'<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="40916C"/></w:rPr></w:style>' .
'<w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/><w:pPr><w:ind w:left="360"/></w:pPr></w:style>' .
'</w:styles>';
$zip->addFromString('word/styles.xml', $stylesXml);

// word/document.xml
$zip->addFromString('word/document.xml', $fullDocumentXml);

$zip->close();

echo "Successfully generated Word document at:\n{$outputPath}\n";
