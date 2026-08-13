<?php
/**
 * LifeGPT - System Administration Dashboard
 * (A FiftyIsNifty research initiative)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

Auth::requireAdmin();
$adminUser = Auth::getCurrentUser();

$activeTab = $_GET['tab'] ?? 'overview';
$message = '';
$error = '';

// Handle Moderation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    $action = $_POST['action'] ?? '';
    $chunkId = (int)($_POST['chunk_id'] ?? 0);

    if ($chunkId > 0) {
        if ($action === 'approve') {
            DB::query("UPDATE lg_knowledge_chunks SET status = 'approved', approved_for_rag = 1 WHERE chunk_id = :id", ['id' => $chunkId]);
            $message = "Knowledge chunk #{$chunkId} approved for RAG search library!";
        } elseif ($action === 'reject') {
            DB::query("UPDATE lg_knowledge_chunks SET status = 'rejected', approved_for_rag = 0 WHERE chunk_id = :id", ['id' => $chunkId]);
            $message = "Knowledge chunk #{$chunkId} rejected.";
        } elseif ($action === 'redact') {
            $redactedText = trim($_POST['redacted_text'] ?? '');
            if (!empty($redactedText)) {
                DB::query(
                    "UPDATE lg_knowledge_chunks SET anonymized_text = :text, status = 'approved', approved_for_rag = 1 WHERE chunk_id = :id",
                    ['text' => $redactedText, 'id' => $chunkId]
                );
                $message = "Knowledge chunk #{$chunkId} redacted & approved successfully!";
            }
        }
    }
}

// Fetch Metrics for Overview
$totalUsers = DB::fetch("SELECT COUNT(*) AS total FROM lg_users")['total'] ?? 0;
$totalStories = DB::fetch("SELECT COUNT(*) AS total FROM lg_interviews")['total'] ?? 0;
$totalChunks = DB::fetch("SELECT COUNT(*) AS total FROM lg_knowledge_chunks")['total'] ?? 0;
$pendingChunksCount = DB::fetch("SELECT COUNT(*) AS total FROM lg_knowledge_chunks WHERE status = 'pending'")['total'] ?? 0;

// Fetch Data for Tabs
$pendingChunks = DB::fetchAll(
    "SELECT kc.*, i.uuid AS interview_uuid, p.name AS persona_name 
     FROM lg_knowledge_chunks kc
     JOIN lg_interviews i ON kc.interview_id = i.interview_id
     JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     WHERE kc.status = 'pending'
     ORDER BY kc.created_at DESC"
);

$allChunks = DB::fetchAll(
    "SELECT kc.*, i.uuid AS interview_uuid, p.name AS persona_name 
     FROM lg_knowledge_chunks kc
     JOIN lg_interviews i ON kc.interview_id = i.interview_id
     JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     ORDER BY kc.created_at DESC LIMIT 50"
);

$personas = DB::fetchAll("SELECT * FROM lg_interviewer_personas ORDER BY persona_id ASC");
$topics = DB::fetchAll("SELECT * FROM lg_interview_topics ORDER BY topic_id ASC");

$pageTitle = "System Administration Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1240px; margin: 1rem auto 4rem auto;">
    
    <!-- Admin Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2.5rem; background: var(--color-primary); color: #ffffff; padding: 2.25rem; border-radius: 24px;">
        <div>
            <span style="display: inline-block; padding: 0.3rem 0.85rem; background: rgba(255,255,255,0.15); border-radius: 9999px; font-size: 0.8rem; font-weight: 700; color: #a7f3d0; margin-bottom: 0.5rem;">SYSTEM ADMINISTRATION</span>
            <h1 style="color: #ffffff; font-size: 2.35rem; margin-bottom: 0.25rem;">LifeGPT Administration Console</h1>
            <p style="color: #d1fae5; font-size: 0.95rem;">Logged in as: <strong><?php echo htmlspecialchars($adminUser['email']); ?></strong></p>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?php echo APP_URL; ?>/" class="btn btn-secondary" style="border-color: #ffffff; color: var(--color-primary);">← Back to Site</a>
            <a href="<?php echo APP_URL; ?>/account/logout.php" class="btn btn-danger">Sign Out</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <strong>Success:</strong> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Admin Navigation Sidebar/Tabs -->
    <div style="display: flex; gap: 0.75rem; margin-bottom: 2rem; border-bottom: 2px solid var(--color-border); padding-bottom: 0.5rem; flex-wrap: wrap;">
        <a href="<?php echo APP_URL; ?>/admin/?tab=overview" class="btn <?php echo ($activeTab === 'overview') ? 'btn-primary' : 'btn-outline'; ?>">Overview</a>
        <a href="<?php echo APP_URL; ?>/admin/?tab=review" class="btn <?php echo ($activeTab === 'review') ? 'btn-primary' : 'btn-outline'; ?>">
            Review Queue <?php if ($pendingChunksCount > 0): ?><span style="background: #be123c; color: #ffffff; border-radius: 9999px; padding: 0.1rem 0.5rem; font-size: 0.8rem; margin-left: 0.4rem;"><?php echo $pendingChunksCount; ?></span><?php endif; ?>
        </a>
        <a href="<?php echo APP_URL; ?>/admin/?tab=personas" class="btn <?php echo ($activeTab === 'personas') ? 'btn-primary' : 'btn-outline'; ?>">Personas</a>
        <a href="<?php echo APP_URL; ?>/admin/?tab=topics" class="btn <?php echo ($activeTab === 'topics') ? 'btn-primary' : 'btn-outline'; ?>">Topics</a>
        <a href="<?php echo APP_URL; ?>/admin/?tab=chunks" class="btn <?php echo ($activeTab === 'chunks') ? 'btn-primary' : 'btn-outline'; ?>">Knowledge Chunks</a>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <?php if ($activeTab === 'overview'): ?>
        <div class="dashboard-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo $totalUsers; ?></div>
                <div class="metric-label">Total Registered Members</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $totalStories; ?></div>
                <div class="metric-label">Total Stories Conducted</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" style="color: var(--color-primary);"><?php echo $totalChunks; ?></div>
                <div class="metric-label">Knowledge Chunks</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" style="color: var(--color-amber);"><?php echo $pendingChunksCount; ?></div>
                <div class="metric-label">Pending Moderation</div>
            </div>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <h2>Quick Moderation Actions</h2>
            <p class="text-sm" style="margin-bottom: 1.5rem;">Review pending knowledge chunks, inspect AI host personas, or update life topics.</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo APP_URL; ?>/admin/?tab=review" class="btn btn-primary">Open Review Queue (<?php echo $pendingChunksCount; ?>)</a>
                <a href="<?php echo APP_URL; ?>/admin/?tab=personas" class="btn btn-secondary">Inspect Personas</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 2: REVIEW QUEUE -->
    <?php if ($activeTab === 'review'): ?>
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">Moderation Queue Table</h2>
            
            <?php if (empty($pendingChunks)): ?>
                <div style="text-align: center; padding: 3rem 1.5rem; background: var(--color-bg-base); border-radius: var(--border-radius-md);">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">✔</div>
                    <h3>Queue Clean!</h3>
                    <p class="text-sm">No knowledge chunks are currently awaiting moderation.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php foreach ($pendingChunks as $chunk): ?>
                        <div style="border: 1px solid var(--color-border); border-radius: var(--border-radius-md); padding: 1.5rem; background: #ffffff;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div>
                                    <strong style="color: var(--color-primary); font-size: 1.1rem;">Chunk #<?php echo $chunk['chunk_id']; ?></strong> &bull; 
                                    <span class="text-sm">Persona: <?php echo htmlspecialchars($chunk['persona_name']); ?></span>
                                </div>
                                <span class="step-badge" style="background: #FEF3C7; color: #92400E;">PENDING REVIEW</span>
                            </div>

                            <div style="background: var(--color-bg-base); padding: 1.15rem; border-radius: 12px; font-size: 1rem; margin-bottom: 1.25rem;">
                                “<?php echo htmlspecialchars($chunk['anonymized_text']); ?>”
                            </div>

                            <!-- RAG Approval Controls: Approve to RAG, Reject, Redact -->
                            <form action="" method="POST" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                                <?php echo CSRF::getInput(); ?>
                                <input type="hidden" name="chunk_id" value="<?php echo $chunk['chunk_id']; ?>">
                                
                                <button type="submit" name="action" value="approve" class="btn btn-primary" style="min-height: 40px; padding: 0.4rem 1.25rem; font-size: 0.9rem;">
                                    [ Approve to RAG ]
                                </button>

                                <button type="submit" name="action" value="reject" class="btn btn-danger" style="min-height: 40px; padding: 0.4rem 1.25rem; font-size: 0.9rem;">
                                    [ Reject ]
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TAB 3: PERSONAS -->
    <?php if ($activeTab === 'personas'): ?>
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">Active AI Host Personas</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-border); background: var(--color-bg-base);">
                            <th style="padding: 0.75rem;">ID</th>
                            <th style="padding: 0.75rem;">Key</th>
                            <th style="padding: 0.75rem;">Name</th>
                            <th style="padding: 0.75rem;">Greeting Prompt</th>
                            <th style="padding: 0.75rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personas as $p): ?>
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: 0.75rem;">#<?php echo $p['persona_id']; ?></td>
                                <td style="padding: 0.75rem;"><code><?php echo htmlspecialchars($p['persona_key']); ?></code></td>
                                <td style="padding: 0.75rem;"><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                <td style="padding: 0.75rem;"><em>“<?php echo htmlspecialchars(mb_substr($p['greeting'], 0, 80)); ?>...”</em></td>
                                <td style="padding: 0.75rem;">
                                    <span class="step-badge" style="background: #F0FDF4; color: #166534;">Active</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 4: TOPICS -->
    <?php if ($activeTab === 'topics'): ?>
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">Active Life Topics</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-border); background: var(--color-bg-base);">
                            <th style="padding: 0.75rem;">ID</th>
                            <th style="padding: 0.75rem;">Key</th>
                            <th style="padding: 0.75rem;">Topic Name</th>
                            <th style="padding: 0.75rem;">Starter Prompt</th>
                            <th style="padding: 0.75rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topics as $t): ?>
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: 0.75rem;">#<?php echo $t['topic_id']; ?></td>
                                <td style="padding: 0.75rem;"><code><?php echo htmlspecialchars($t['topic_key']); ?></code></td>
                                <td style="padding: 0.75rem;"><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                                <td style="padding: 0.75rem;"><em>“<?php echo htmlspecialchars(mb_substr($t['starter_prompt'], 0, 80)); ?>...”</em></td>
                                <td style="padding: 0.75rem;">
                                    <span class="step-badge" style="background: #F0FDF4; color: #166534;">Active</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 5: KNOWLEDGE CHUNKS -->
    <?php if ($activeTab === 'chunks'): ?>
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">Knowledge Chunks Record</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($allChunks as $chunk): ?>
                    <div style="border: 1px solid var(--color-border); border-radius: var(--border-radius-md); padding: 1rem; background: #ffffff;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                            <span style="font-size: 0.95rem; font-weight: 600;">Chunk #<?php echo $chunk['chunk_id']; ?></span>
                            <span class="step-badge" style="background: <?php echo ($chunk['status'] === 'approved') ? '#F0FDF4' : '#FEF3C7'; ?>; color: <?php echo ($chunk['status'] === 'approved') ? '#166534' : '#92400E'; ?>;">
                                <?php echo strtoupper($chunk['status']); ?>
                            </span>
                        </div>
                        <p class="text-sm" style="color: var(--color-text-main);">“<?php echo htmlspecialchars($chunk['anonymized_text']); ?>”</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
