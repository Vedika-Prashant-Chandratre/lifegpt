/**
 * LifeGPT - Conversation / Interview Controller
 */

document.addEventListener('DOMContentLoaded', function() {
    // Config parameters
    const config = window.LifeGPTConfig || {};
    const appUrl = config.appUrl || '';
    const interviewUuid = config.interviewUuid || '';
    const inputMode = config.inputMode || 'mixed';
    const voiceSettings = config.voiceSettings || { rate: 1.0, pitch: 1.0, lang: 'en-US' };

    // DOM Elements
    const chatContainer = document.getElementById('chatContainer');
    const questionProgress = document.getElementById('questionProgress');
    const progressBar = document.getElementById('progressBar');
    
    const answerForm = document.getElementById('answerForm');
    const answerText = document.getElementById('answerText');
    const btnSubmit = document.getElementById('btnSubmit');
    const btnSkip = document.getElementById('btnSkip');
    const btnPause = document.getElementById('btnPause');
    const btnReplay = document.getElementById('btnReplay');
    const btnEndEarly = document.getElementById('btnEndEarly');
    
    // Recording controls
    const btnRecord = document.getElementById('btnRecord');
    const btnStopRecord = document.getElementById('btnStopRecord');
    const recordBtnText = document.getElementById('recordBtnText');
    const recordingIndicator = document.getElementById('recordingIndicator');
    
    // Transcript Review controls
    const transcriptReviewBox = document.getElementById('transcriptReviewBox');
    const transcriptReviewText = document.getElementById('transcriptReviewText');
    const btnAcceptTranscript = document.getElementById('btnAcceptTranscript');
    const btnEditTranscript = document.getElementById('btnEditTranscript');
    const btnRetryTranscript = document.getElementById('btnRetryTranscript');

    // State Variables
    let currentQuestion = '';
    let questionCount = 0;
    let targetQuestionLimit = 8; // Default standard length
    let finalSpeechTranscript = '';
    let isSynthPlaying = false;
    
    // CSRF token retrieval helper
    function getCsrfToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    // Initialize Modules
    let synthPlayer = null;
    let speechRecognizer = null;

    if (typeof LifeGPTSpeechSynthesis !== 'undefined') {
        synthPlayer = new LifeGPTSpeechSynthesis({
            rate: voiceSettings.rate,
            pitch: voiceSettings.pitch,
            lang: voiceSettings.lang,
            onStart: () => {
                isSynthPlaying = true;
                btnReplay.innerHTML = '🔊 Speaking...';
            },
            onEnd: () => {
                isSynthPlaying = false;
                btnReplay.innerHTML = '🔊 Replay Question';
            }
        });
    }

    if (typeof LifeGPTSpeechRecognition !== 'undefined' && inputMode !== 'typing') {
        speechRecognizer = new LifeGPTSpeechRecognition({
            onStart: () => {
                recordingIndicator.style.display = 'flex';
                if (btnRecord) {
                    btnRecord.style.display = 'none';
                    btnStopRecord.style.display = 'inline-flex';
                }
                finalSpeechTranscript = '';
            },
            onEnd: () => {
                recordingIndicator.style.display = 'none';
                if (btnRecord) {
                    btnRecord.style.display = 'inline-flex';
                    btnStopRecord.style.display = 'none';
                }
                
                // Show review box if we captured some text
                if (finalSpeechTranscript.trim().length > 0) {
                    showTranscriptReview(finalSpeechTranscript.trim());
                }
            },
            onResult: (final, interim) => {
                finalSpeechTranscript = final;
                // Pre-fill textarea with what is heard for live feedback
                answerText.value = final + (interim ? ' ' + interim : '');
            },
            onError: (err) => {
                recordingIndicator.style.display = 'none';
                alert('Microphone error: ' + err + '. Please try typing your response.');
            }
        });
        
        // Hide record buttons if browser doesn't support speech recognition
        if (!speechRecognizer.isSupported() && btnRecord) {
            btnRecord.style.display = 'none';
            console.warn('Speech Recognition unsupported. Hiding mic controls.');
        }
    }

    // --- Core Conversation Flow ---

    /**
     * Fetch next question from AI backend
     */
    function fetchNextQuestion() {
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Organizing thoughts...';
        
        const csrfToken = getCsrfToken();
        
        fetch(`${appUrl}/api/interview-next-question.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                interview_uuid: interviewUuid
            })
        })
        .then(response => response.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Save & Next Question';
            
            if (data.success) {
                if (data.interview_complete) {
                    // Redirect to final summary creation screen
                    window.location.href = `${appUrl}/interview/complete.php`;
                    return;
                }
                
                // Save state details
                currentQuestion = data.next_question;
                questionCount = data.question_sequence;
                targetQuestionLimit = data.target_limit;
                
                // Update Progress bar & labels
                updateProgressUI();
                
                // Render Question
                addChatBubble(currentQuestion, 'ai');
                
                // Speak out loud
                if (synthPlayer) {
                    synthPlayer.speak(currentQuestion);
                }
                
                // Clear answer textarea
                answerText.value = '';
                
            } else {
                alert('Error loading question: ' + data.error);
            }
        })
        .catch(error => {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Save & Next Question';
            console.error('Fetch Next Question Error:', error);
            alert('Failed to connect to the interview engine. Please refresh.');
        });
    }

    /**
     * Submit answer text to save progress
     */
    function saveAnswer(text, method = 'typing') {
        if (synthPlayer) {
            synthPlayer.cancel();
        }
        
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Saving answer...';
        
        const csrfToken = getCsrfToken();
        
        fetch(`${appUrl}/api/interview-save-answer.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                interview_uuid: interviewUuid,
                answer: text,
                input_method: method
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Render user answer bubble
                addChatBubble(text, 'user');
                
                // Fetch next question
                fetchNextQuestion();
            } else {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Save & Next Question';
                alert('Error saving answer: ' + data.error);
            }
        })
        .catch(error => {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Save & Next Question';
            console.error('Save Answer Error:', error);
            alert('Connection failure. Your answer could not be saved.');
        });
    }

    /**
     * Add bubble to chat panel
     */
    function addChatBubble(text, role) {
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble chat-bubble-${role}`;
        
        const meta = document.createElement('div');
        meta.className = 'chat-bubble-meta';
        meta.textContent = role === 'ai' ? 'LifeGPT Interviewer' : 'You';
        
        const body = document.createElement('div');
        body.textContent = text;
        
        bubble.appendChild(meta);
        bubble.appendChild(body);
        
        chatContainer.appendChild(bubble);
        
        // Auto scroll to bottom
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    /**
     * Update progress details
     */
    function updateProgressUI() {
        questionProgress.textContent = `Question ${questionCount} of approximately ${targetQuestionLimit}`;
        const pct = Math.min(100, Math.round(((questionCount - 1) / targetQuestionLimit) * 100));
        progressBar.style.width = `${pct}%`;
    }

    // --- Speech Recognition Transcripts Review Flow ---
    
    function showTranscriptReview(transcript) {
        transcriptReviewText.textContent = `"${transcript}"`;
        transcriptReviewBox.style.display = 'block';
        answerForm.style.opacity = '0.4';
        
        // Disable form inputs during review
        answerText.disabled = true;
        btnSubmit.disabled = true;
        if (btnRecord) btnRecord.disabled = true;
    }
    
    function hideTranscriptReview() {
        transcriptReviewBox.style.display = 'none';
        answerForm.style.opacity = '1';
        
        // Enable form inputs
        answerText.disabled = false;
        btnSubmit.disabled = false;
        if (btnRecord) btnRecord.disabled = false;
    }

    // --- Event Listeners Bindings ---

    // Submit Answer Form
    answerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = answerText.value.trim();
        if (text.length === 0) {
            alert('Please share your thoughts before clicking Next, or choose to Skip.');
            return;
        }
        
        saveAnswer(text, 'typing');
    });

    // Skip current question
    btnSkip.addEventListener('click', function() {
        if (confirm('Would you like to skip this question? You can also type a brief note instead.')) {
            saveAnswer('[Contributor chose to skip this question]', 'typing');
        }
    });

    // Replay speech voice
    btnReplay.addEventListener('click', function() {
        if (synthPlayer && currentQuestion) {
            if (isSynthPlaying) {
                synthPlayer.cancel();
            } else {
                synthPlayer.speak(currentQuestion);
            }
        }
    });

    // Pause interview
    btnPause.addEventListener('click', function() {
        if (synthPlayer) synthPlayer.cancel();
        if (confirm('Your progress is autosaved. Would you like to pause the interview and return to the dashboard?')) {
            window.location.href = `${appUrl}/dashboard/`;
        }
    });

    // End Early
    btnEndEarly.addEventListener('click', function() {
        if (synthPlayer) synthPlayer.cancel();
        
        if (questionCount < 4) {
            alert('To complete an interview, we suggest answering at least 4 questions. You can continue answering or pause and save for later.');
            return;
        }
        
        if (confirm('Would you like to end the interview now and prepare your final story summary?')) {
            btnEndEarly.disabled = true;
            btnEndEarly.textContent = 'Completing...';
            
            const csrfToken = getCsrfToken();
            
            fetch(`${appUrl}/api/interview-complete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    interview_uuid: interviewUuid
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = `${appUrl}/interview/complete.php`;
                } else {
                    btnEndEarly.disabled = false;
                    btnEndEarly.textContent = 'Finish & Summarize';
                    alert('Error completing interview: ' + data.error);
                }
            })
            .catch(err => {
                btnEndEarly.disabled = false;
                btnEndEarly.textContent = 'Finish & Summarize';
                console.error(err);
                alert('Connection error while completing interview.');
            });
        }
    });

    // Speech Recording buttons
    if (btnRecord) {
        btnRecord.addEventListener('click', function() {
            if (synthPlayer) synthPlayer.cancel();
            if (speechRecognizer) speechRecognizer.start();
        });
        
        btnStopRecord.addEventListener('click', function() {
            if (speechRecognizer) speechRecognizer.stop();
        });
    }

    // Transcript choices: Use
    btnAcceptTranscript.addEventListener('click', function() {
        answerText.value = finalSpeechTranscript;
        hideTranscriptReview();
        // Automatically save and proceed!
        saveAnswer(finalSpeechTranscript, 'voice');
    });

    // Transcript choices: Edit
    btnEditTranscript.addEventListener('click', function() {
        answerText.value = finalSpeechTranscript;
        hideTranscriptReview();
        answerText.focus();
    });

    // Transcript choices: Retry
    btnRetryTranscript.addEventListener('click', function() {
        hideTranscriptReview();
        answerText.value = '';
        if (speechRecognizer) {
            speechRecognizer.start();
        }
    });

    // START: Load first question
    fetchNextQuestion();
});
