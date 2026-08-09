<?php
/**
 * LifeGPT - Privacy Policy
 */
$pageTitle = "Privacy Policy";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 1.5rem; text-align: center;">Privacy Policy & Consent Framework</h1>
    <p class="text-lg" style="color: var(--color-text-muted); text-align: center; margin-bottom: 3rem;">
        “Your life has answers someone else needs.” But those answers belong entirely to you. Here is how we protect your information.
    </p>

    <div class="card" style="margin-bottom: 2.5rem;">
        <h2>1. Granular Consent</h2>
        <p style="margin-bottom: 1rem;">
            We do not use a single "agree to all" checkmark. Instead, you grant distinct permissions for different uses of your story:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>Storage Consent:</strong> Allows us to save your interview history so you can review it or resume it.</li>
            <li><strong>RAG (Search) Consent:</strong> Allows your anonymized stories to help answer questions asked by visitors on "Ask LifeGPT".</li>
            <li><strong>Quotes Consent:</strong> Allows us to display your representative, approved quotes on our homepage or community feeds.</li>
            <li><strong>Research Consent:</strong> Allows verified researchers to review anonymized stories to study demographic and social trends.</li>
        </ul>
        <p>If you only allow storage, your stories remain strictly private and visible only to you.</p>
    </div>

    <div class="card" style="margin-bottom: 2.5rem;">
        <h2>2. Data Deletion & Withdrawal</h2>
        <p style="margin-bottom: 1rem;">
            Your consent is fully withdrawable. You can change your permissions or delete your stories at any time:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li>Registered members can withdraw consent or delete interviews directly from their dashboard.</li>
            <li>Guest users are given a secure access link upon completing their interview, allowing them to return and withdraw consent or delete the conversation later.</li>
            <li>When consent is withdrawn, the corresponding information is instantly flagged as private and removed from all future RAG queries and public pages.</li>
        </ul>
    </div>

    <div class="card" style="margin-bottom: 2.5rem;">
        <h2>3. Administrative Review & Redaction</h2>
        <p style="margin-bottom: 1rem;">
            Before any story is approved for retrieval in "Ask LifeGPT", an administrator manually moderates it to ensure:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li>No full names, physical addresses, or precise locations are present.</li>
            <li>No confidential company details or financial account numbers are exposed.</li>
            <li>No sensitive health conditions or medical record numbers are detailed.</li>
        </ul>
        <p>Any identifying information found during moderation is redacted before the story is marked as an approved knowledge chunk.</p>
    </div>

    <div class="card" style="margin-bottom: 2.5rem;">
        <h2>4. No Audio Storage</h2>
        <p>
            To maximize privacy, we do not record or save your raw voice files. All browser speech recognition takes place on the client side, converting spoken words directly into text. We only store the text transcripts that you explicitly review and approve.
        </p>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
