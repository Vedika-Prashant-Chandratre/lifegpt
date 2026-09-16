/**
 * LifeGPT - Conversation / Story Controller
 * (A FiftyIsNifty research initiative)
 * Features dynamic question progress, profanity moderation, and member/guest completion routing.
 */

document.addEventListener('DOMContentLoaded', function() {
    const config = window.LifeGPTConfig || {};
    const appUrl = config.appUrl || '';
    const interviewUuid = config.interviewUuid || '';
    const inputMode = config.inputMode || 'mixed';
    const isLoggedIn = config.isLoggedIn || false;
    const voiceSettings = config.voiceSettings || { rate: 1.0, pitch: 1.0, lang: 'en-US' };

    // Clear any queued speech from previous loads immediately
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
        try { window.speechSynthesis.cancel(); } catch (e) {}
    }

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
    const profanityBanner = document.getElementById('profanityAlertBanner');
    
    // Recording controls
    const btnRecord = document.getElementById('btnRecord');
    const btnStopRecord = document.getElementById('btnStopRecord');
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
    let targetQuestionLimit = 5; // Default 5 questions per spec
    let finalSpeechTranscript = '';
    let isSynthPlaying = false;

    // List of inappropriate words for client-side content moderation
    const profanityList = [
        'fuck', 'shit', 'asshole', 'bitch', 'cunt', 'bastard', 'nigger', 'faggot',
        'motherfucker', 'cock', 'pussy', 'whore', 'slut', 'dick', 'piss'
    ];
    
    function getCsrfToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    // --- Content Moderation Logic ---
    function checkProfanity(text) {
        if (!text) return false;
        const lower = text.toLowerCase();
        for (let word of profanityList) {
            const regex = new RegExp('\\b' + word + '\\b', 'i');
            if (regex.test(lower)) {
                return true;
            }
        }
        return false;
    }

    function validateInputText() {
        const val = answerText.value.trim();
        const hasProfanity = checkProfanity(val);

        if (hasProfanity) {
            if (profanityBanner) profanityBanner.style.display = 'block';
            btnSubmit.disabled = true;
            btnSubmit.classList.add('disabled');
        } else {
            if (profanityBanner) profanityBanner.style.display = 'none';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('disabled');
        }
    }

    if (answerText) {
        answerText.addEventListener('input', validateInputText);
    }

    // Initialize Speech Modules
    let synthPlayer = null;
    let speechRecognizer = null;

    if (typeof LifeGPTSpeechSynthesis !== 'undefined') {
        synthPlayer = new LifeGPTSpeechSynthesis({
            rate: voiceSettings.rate,
            pitch: voiceSettings.pitch,
            lang: voiceSettings.lang,
            onStart: () => {
                isSynthPlaying = true;
                if (btnReplay) btnReplay.innerHTML = '⏹ Stop Speaking';
            },
            onEnd: () => {
                isSynthPlaying = false;
                if (btnReplay) btnReplay.innerHTML = '🔊 Read Question';
            },
            onError: () => {
                isSynthPlaying = false;
                if (btnReplay) btnReplay.innerHTML = '🔊 Read Question';
            }
        });
    }

    if (typeof LifeGPTSpeechRecognition !== 'undefined' && inputMode !== 'typing') {
        speechRecognizer = new LifeGPTSpeechRecognition({
            onStart: () => {
                if (recordingIndicator) recordingIndicator.style.display = 'flex';
                if (btnRecord) {
                    btnRecord.style.display = 'none';
                    if (btnStopRecord) btnStopRecord.style.display = 'inline-flex';
                }
                finalSpeechTranscript = '';
            },
            onEnd: () => {
                if (recordingIndicator) recordingIndicator.style.display = 'none';
                if (btnRecord) {
                    btnRecord.style.display = 'inline-flex';
                    if (btnStopRecord) btnStopRecord.style.display = 'none';
                }
                
                if (finalSpeechTranscript.trim().length > 0) {
                    showTranscriptReview(finalSpeechTranscript.trim());
                }
            },
            onResult: (final, interim) => {
                finalSpeechTranscript = final;
                answerText.value = final + (interim ? ' ' + interim : '');
                validateInputText();
            },
            onError: (err) => {
                if (recordingIndicator) recordingIndicator.style.display = 'none';
                alert('Microphone error: ' + err + '. Please try typing your response.');
            }
        });
        
        if (!speechRecognizer.isSupported() && btnRecord) {
            btnRecord.style.display = 'none';
        }
    }

    // --- Core Conversation Flow ---
    function handleSessionCompletion() {
        if (isLoggedIn) {
            // Logged-in member -> Redirect directly to Member Dashboard with completed status & UUID
            window.location.href = `${appUrl}/dashboard/?completed=1&uuid=${interviewUuid}`;
        } else {
            // Anonymous guest -> Redirect to Guest Completed Story Review Page
            window.location.href = `${appUrl}/interview/summary.php`;
        }
    }

    function fetchNextQuestion() {
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Organizing thoughts...';
        
        const csrfToken = getCsrfToken();
        
        fetch(`${appUrl}/api/interview-next-question.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ interview_uuid: interviewUuid })
        })
        .then(response => response.json())
        .then(data => {
            btnSubmit.disabled = false;
            
            if (data.success) {
                if (data.interview_complete) {
                    handleSessionCompletion();
                    return;
                }
                
                currentQuestion = data.next_question;
                questionCount = data.question_sequence;
                targetQuestionLimit = data.target_limit || 5;
                
                // Never speak automatically; ensure any prior speech is cancelled and label reset
                if (synthPlayer) {
                    synthPlayer.cancel();
                }
                isSynthPlaying = false;
                if (btnReplay) {
                    btnReplay.innerHTML = '🔊 Read Question';
                }
                
                updateProgressUI();
                addChatBubble(currentQuestion, 'ai');
                
                answerText.value = '';
                validateInputText();
                
            } else {
                alert('Error loading question: ' + data.error);
            }
        })
        .catch(error => {
            btnSubmit.disabled = false;
            console.error('Fetch Next Question Error:', error);
            alert('Failed to connect to the story engine. Please refresh.');
        });
    }

    function saveAnswer(text, method = 'typing') {
        if (checkProfanity(text)) {
            if (profanityBanner) profanityBanner.style.display = 'block';
            alert('⚠️ Inappropriate language detected. Please edit your response to proceed.');
            return;
        }

        if (synthPlayer) {
            synthPlayer.cancel();
        }
        isSynthPlaying = false;
        if (btnReplay) {
            btnReplay.innerHTML = '🔊 Read Question';
        }
        if (speechRecognizer) {
            speechRecognizer.stop();
        }
        
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Saving answer...';
        
        const csrfToken = getCsrfToken();
        
        fetch(`${appUrl}/api/interview-save-answer.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
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
                addChatBubble(text, 'user');
                fetchNextQuestion();
            } else {
                btnSubmit.disabled = false;
                alert('Error saving answer: ' + data.error);
            }
        })
        .catch(error => {
            btnSubmit.disabled = false;
            console.error('Save Answer Error:', error);
            alert('Connection failure. Your answer could not be saved.');
        });
    }

    function addChatBubble(text, role) {
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble chat-bubble-${role}`;
        
        const meta = document.createElement('div');
        meta.className = 'chat-bubble-meta';
        meta.textContent = role === 'ai' ? '🌱 LifeGPT Host' : 'You';
        
        const body = document.createElement('div');
        body.textContent = text;
        
        bubble.appendChild(meta);
        bubble.appendChild(body);
        
        chatContainer.appendChild(bubble);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function updateProgressUI() {
        questionProgress.textContent = `Question ${questionCount} of ${targetQuestionLimit}`;
        const pct = Math.min(100, Math.round(((questionCount - 1) / targetQuestionLimit) * 100));
        progressBar.style.width = `${pct}%`;

        // Question 5 of 5 Transformation
        if (questionCount >= targetQuestionLimit) {
            btnSubmit.innerHTML = '🏁 Finish Story & View Summary';
        } else {
            btnSubmit.innerHTML = 'Send Answer ➔';
        }
    }

    function showTranscriptReview(transcript) {
        transcriptReviewText.textContent = `"${transcript}"`;
        transcriptReviewBox.style.display = 'block';
        answerForm.style.opacity = '0.4';
        
        answerText.disabled = true;
        btnSubmit.disabled = true;
        if (btnRecord) btnRecord.disabled = true;
    }
    
    function hideTranscriptReview() {
        transcriptReviewBox.style.display = 'none';
        answerForm.style.opacity = '1';
        
        answerText.disabled = false;
        btnSubmit.disabled = false;
        if (btnRecord) btnRecord.disabled = false;
        validateInputText();
    }

    // --- Form Submit & Event Bindings ---
    answerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = answerText.value.trim();
        if (text.length === 0) {
            alert('Please share your thoughts before proceeding.');
            return;
        }
        
        saveAnswer(text, 'typing');
    });

    if (btnSkip) {
        btnSkip.addEventListener('click', function() {
            if (confirm('Would you like to skip this question?')) {
                if (synthPlayer) synthPlayer.cancel();
                if (speechRecognizer) speechRecognizer.stop();
                saveAnswer('[Contributor chose to skip this question]', 'typing');
            }
        });
    }

    if (btnReplay) {
        btnReplay.addEventListener('click', function() {
            if (!currentQuestion) return;
            if (isSynthPlaying) {
                if (synthPlayer) synthPlayer.cancel();
                isSynthPlaying = false;
                btnReplay.innerHTML = '🔊 Read Question';
            } else {
                if (synthPlayer) {
                    synthPlayer.speak(currentQuestion);
                }
            }
        });
    }

    if (btnPause) {
        btnPause.addEventListener('click', function() {
            if (synthPlayer) synthPlayer.cancel();
            if (speechRecognizer) speechRecognizer.stop();
            if (confirm('Your progress is autosaved. Would you like to pause and return to your dashboard?')) {
                window.location.href = `${appUrl}/dashboard/`;
            }
        });
    }

    if (btnEndEarly) {
        btnEndEarly.addEventListener('click', function() {
            if (synthPlayer) synthPlayer.cancel();
            if (speechRecognizer) speechRecognizer.stop();
            if (confirm('Would you like to finish your story now and view your summary?')) {
                btnEndEarly.disabled = true;
                btnEndEarly.textContent = 'Completing...';
                
                const csrfToken = getCsrfToken();
                
                fetch(`${appUrl}/api/interview-complete.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ interview_uuid: interviewUuid })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        handleSessionCompletion();
                    } else {
                        btnEndEarly.disabled = false;
                        btnEndEarly.textContent = 'Finish Story & View Summary';
                        alert('Error completing story: ' + data.error);
                    }
                });
            }
        });
    }

    if (btnRecord) {
        btnRecord.addEventListener('click', function() {
            if (synthPlayer) synthPlayer.cancel();
            if (speechRecognizer) speechRecognizer.start();
        });
        
        if (btnStopRecord) {
            btnStopRecord.addEventListener('click', function() {
                if (speechRecognizer) speechRecognizer.stop();
            });
        }
    }

    if (btnAcceptTranscript) {
        btnAcceptTranscript.addEventListener('click', function() {
            answerText.value = finalSpeechTranscript;
            hideTranscriptReview();
            saveAnswer(finalSpeechTranscript, 'voice');
        });
    }

    if (btnEditTranscript) {
        btnEditTranscript.addEventListener('click', function() {
            answerText.value = finalSpeechTranscript;
            hideTranscriptReview();
            answerText.focus();
        });
    }

    if (btnRetryTranscript) {
        btnRetryTranscript.addEventListener('click', function() {
            hideTranscriptReview();
            answerText.value = '';
            if (speechRecognizer) speechRecognizer.start();
        });
    }

    // Stop active speech playback or microphone when navigating away
    window.addEventListener('beforeunload', function() {
        if (synthPlayer) synthPlayer.cancel();
        if (speechRecognizer) speechRecognizer.stop();
    });
    window.addEventListener('pagehide', function() {
        if (synthPlayer) synthPlayer.cancel();
        if (speechRecognizer) speechRecognizer.stop();
    });

    // Load first question
    fetchNextQuestion();
});
