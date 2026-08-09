<?php
/**
 * LifeGPT - Select Interviewer Persona
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

// Fetch active personas from DB
$personas = DB::fetchAll("SELECT * FROM lg_interviewer_personas WHERE active = 1");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $selectedPersonaId = $_POST['persona_id'] ?? '';
    
    if (empty($selectedPersonaId)) {
        $error = 'Please select an interviewer persona to continue.';
    } else {
        // Validate against fetched active list
        $isValid = false;
        foreach ($personas as $p) {
            if ((int)$p['persona_id'] === (int)$selectedPersonaId) {
                $isValid = true;
                $_SESSION['setup_persona_id'] = (int)$p['persona_id'];
                $_SESSION['setup_persona_name'] = $p['name'];
                $_SESSION['setup_persona_key'] = $p['persona_key'];
                break;
            }
        }
        
        if ($isValid) {
            header("Location: " . APP_URL . "/interview/choose-topic.php");
            exit;
        } else {
            $error = 'Invalid persona selection. Please try again.';
        }
    }
}

// Current selection from session
$currentPersonaId = $_SESSION['setup_persona_id'] ?? '';

$pageTitle = "Choose Your AI Interviewer";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="hero-subtitle">Step 1 of 4</span>
        <h1 style="font-size: 2.25rem; margin-top: 0.5rem;">Who would you like to talk to?</h1>
        <p class="text-sm" style="font-size: 1.1rem; color: var(--color-text-muted);">Each interviewer has a different personality, voice, and focus. Choose the one that suits you.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" id="personaForm">
        <?php echo CSRF::getInput(); ?>
        <input type="hidden" name="persona_id" id="selectedPersonaId" value="<?php echo htmlspecialchars($currentPersonaId); ?>">
        
        <div class="persona-grid">
            <?php foreach ($personas as $p): 
                $emoji = '🧒';
                $styleDesc = 'Gentle, curious, encouraging';
                switch($p['persona_key']) {
                    case 'grandchild': $emoji = '🧒'; $styleDesc = 'Gentle, curious, encouraging'; break;
                    case 'journalist': $emoji = '🎤'; $styleDesc = 'Clear, structured, neutral'; break;
                    case 'coach': $emoji = '🏃'; $styleDesc = 'Supportive, growth-oriented'; break;
                    case 'comedian': $emoji = '🎭'; $styleDesc = 'Playful, witty, respectful'; break;
                    case 'historian': $emoji = '📜'; $styleDesc = 'Reflective, contextual'; break;
                }
                
                $isSelected = ((int)$p['persona_id'] === (int)$currentPersonaId);
            ?>
                <div class="card persona-card <?php echo $isSelected ? 'selected' : ''; ?>" 
                     data-id="<?php echo $p['persona_id']; ?>" 
                     tabindex="0" 
                     role="radio" 
                     aria-checked="<?php echo $isSelected ? 'true' : 'false'; ?>"
                     aria-label="<?php echo htmlspecialchars($p['name']); ?> - <?php echo $styleDesc; ?>">
                    
                    <div class="persona-avatar-wrapper">
                        <?php echo $emoji; ?>
                    </div>
                    
                    <h2 style="font-size: 1.4rem; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($p['name']); ?></h2>
                    <span class="persona-style-tag"><?php echo $styleDesc; ?></span>
                    
                    <p class="text-sm" style="margin-top: 1.25rem; font-style: italic; min-height: 72px;">
                        "<?php echo htmlspecialchars($p['greeting']); ?>"
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 3rem;">
            <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-outline">Back</a>
            <button type="submit" class="btn btn-primary text-lg" style="padding: 0.75rem 2.5rem;">Continue to Topics</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.persona-card');
    const input = document.getElementById('selectedPersonaId');
    
    function selectCard(card) {
        // Deselect all
        cards.forEach(c => {
            c.classList.remove('selected');
            c.setAttribute('aria-checked', 'false');
        });
        
        // Select this one
        card.classList.add('selected');
        card.setAttribute('aria-checked', 'true');
        
        // Set input value
        const id = card.getAttribute('data-id');
        input.value = id;
    }
    
    cards.forEach(card => {
        card.addEventListener('click', function() {
            selectCard(this);
        });
        
        card.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                selectCard(this);
            }
        });
    });
});
</script>
