<?php
/**
 * LifeGPT - How It Works
 */
$pageTitle = "How It Works";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 1.5rem; text-align: center;">How LifeGPT Works</h1>
    <p class="text-lg" style="color: var(--color-text-muted); text-align: center; margin-bottom: 3rem;">
        LifeGPT is designed to collect the collective wisdom of older adults and organize it for future generations, while giving contributors clear choices over how their stories are saved and shared.
    </p>

    <div class="card" style="margin-bottom: 2.5rem;">
        <h2>1. The Interview Process</h2>
        <p style="margin-bottom: 1.25rem;">
            When you start an interview, you act as the storyteller. The system will guide you through a set of structured follow-up questions focused on capturing:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1.5rem;">
            <li><strong>What happened:</strong> The context and turning point.</li>
            <li><strong>Decisions made:</strong> The factors that influenced you and the choices you made.</li>
            <li><strong>Lessons & Outcomes:</strong> What you learned and what advice you would give to others facing similar situations.</li>
        </ul>
        <p>
            You can choose to answer by <strong>typing</strong> or by <strong>speaking</strong> using your web browser's voice recognition. The interview takes between 3 to 15 minutes, depending on the duration you select.
        </p>
        <div style="background: var(--color-mint-bg); border-left: 4px solid var(--color-primary); padding: 1rem 1.25rem; border-radius: var(--radius-sm); margin-top: 1.25rem;">
            <strong style="color: var(--color-primary); display: block; font-size: 0.95rem; margin-bottom: 0.35rem;">Real-World Example:</strong>
            <p style="font-size: 0.95rem; line-height: 1.55; color: var(--color-text-main); margin-bottom: 0;">
                “Maria shared how she changed careers at 52. LifeGPT asked five follow-up questions, created a summary, and extracted three lessons for others considering a career change.”
            </p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2.5rem;">
        <h2>2. Our Interviewer Personas</h2>
        <p style="margin-bottom: 1.5rem;">
            Different stories require different tones. You can select one of our five specialized AI interviewers:
        </p>
        
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div style="border-left: 4px solid var(--color-primary-light); padding-left: 1.25rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 0.25rem;">Curious Grandchild</h3>
                <p class="text-sm" style="margin-bottom: 0.25rem;">Warm personal reflection. Style: Gentle, curious, encouraging.</p>
                <em style="font-size: 0.95rem; color: var(--color-text-muted);">"What is something life taught you that you wish you had known earlier?"</em>
            </div>
            
            <div style="border-left: 4px solid var(--color-primary-light); padding-left: 1.25rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 0.25rem;">Journalist</h3>
                <p class="text-sm" style="margin-bottom: 0.25rem;">Facts, decisions, and outcomes. Style: Clear, structured, neutral.</p>
                <em style="font-size: 0.95rem; color: var(--color-text-muted);">"Tell me about a decision that changed the direction of your life."</em>
            </div>
            
            <div style="border-left: 4px solid var(--color-primary-light); padding-left: 1.25rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 0.25rem;">Life Coach</h3>
                <p class="text-sm" style="margin-bottom: 0.25rem;">Lessons and practical advice. Style: Supportive and action-oriented.</p>
                <em style="font-size: 0.95rem; color: var(--color-text-muted);">"What challenge taught you the most, and how did it change you?"</em>
            </div>
            
            <div style="border-left: 4px solid var(--color-primary-light); padding-left: 1.25rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 0.25rem;">Comedian</h3>
                <p class="text-sm" style="margin-bottom: 0.25rem;">Humor and memorable mishaps. Style: Playful but respectful.</p>
                <em style="font-size: 0.95rem; color: var(--color-text-muted);">"What is something about getting older that you can finally laugh about?"</em>
            </div>
            
            <div style="border-left: 4px solid var(--color-primary-light); padding-left: 1.25rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 0.25rem;">Historian</h3>
                <p class="text-sm" style="margin-bottom: 0.25rem;">How life and society changed. Style: Reflective and contextual.</p>
                <em style="font-size: 0.95rem; color: var(--color-text-muted);">"What everyday part of life today would have seemed impossible when you were younger?"</em>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2.5rem;">
        <h2>3. Organizing & Sharing the Wisdom</h2>
        <p style="margin-bottom: 1rem;">
            Once you finish, the AI summarizes the story, highlights main lessons, and suggests a representative quote. You can edit all this text.
        </p>
        <p style="margin-bottom: 1rem;">
            After editing, you provide granular permissions to specify whether:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1.5rem;">
            <li>Your story can be saved in your private dashboard.</li>
            <li>Your contributed answers can be searched by visitors using <strong>Ask LifeGPT</strong> (AI-assisted search across contributed stories).</li>
            <li>Your quotes can be featured in public feeds.</li>
        </ul>
        <p>
            An administrator reviews the story, checks consent settings, redacts any accidentally shared identifying information (such as addresses or account details), and approves the knowledge chunks.
        </p>
    </div>

    <div style="text-align: center; margin-top: 3rem;">
        <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary text-lg">Start Sharing Your Wisdom</a>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
