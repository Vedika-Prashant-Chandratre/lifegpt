/**
 * LifeGPT - Asynchronous Ask Search Controller
 * Handles real-time question submissions, animated thinking indicator,
 * dynamic bubble injection, Grounding Score badges, and sources drawer.
 */

document.addEventListener('DOMContentLoaded', function() {
    const askForm = document.getElementById('askForm');
    const askQueryInput = document.getElementById('askQueryInput');
    const chatContainer = document.getElementById('askChatContainer');
    const submitBtn = askForm ? askForm.querySelector('.ask-submit-btn') : null;
    const config = window.LifeGPTConfig || {};
    const appUrl = config.appUrl || '';

    // --- Context-aware conversation state ---
    let activeConversationId = config.conversationId || sessionStorage.getItem('lifegpt_conversation_id') || '';
    let debugModeEnabled = false; // toggled via ?debug=1 in URL or dev button
    const showGroundingScore = config.showGroundingScore !== false; // default true; controlled by SHOW_GROUNDING_SCORE env

    // Check URL for debug mode
    if (new URLSearchParams(window.location.search).get('debug') === '1') {
        debugModeEnabled = true;
    }

    function getCsrfToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : (config.csrfToken || '');
    }

    function formatTimeNow() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = now.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const minsStr = minutes < 10 ? '0' + minutes : minutes;
        return `${hours}:${minsStr} ${ampm}`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function scrollToBottom() {
        if (chatContainer) {
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: 'smooth'
            });
        }
    }

    function removeEmptyStateIfPresent() {
        if (!chatContainer) return;
        const emptyState = chatContainer.querySelector('div[style*="max-width: 680px"]');
        if (emptyState) {
            emptyState.remove();
        }
    }

    function renderUserBubble(text, timeStr) {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble chat-bubble-user';
        bubble.style.alignSelf = 'flex-end';
        bubble.style.maxWidth = '75%';
        bubble.innerHTML = `
            <div class="chat-bubble-meta">You &bull; <span class="notranslate" translate="no">${escapeHtml(timeStr)}</span></div>
            <p style="font-size: 1.05rem; line-height: 1.5; margin: 0;">${escapeHtml(text)}</p>
        `;
        chatContainer.appendChild(bubble);
        scrollToBottom();
    }

    function renderLoaderBubble() {
        const loader = document.createElement('div');
        loader.id = 'activeAiLoader';
        loader.className = 'chat-bubble chat-bubble-ai';
        loader.style.alignSelf = 'flex-start';
        loader.style.maxWidth = '85%';
        loader.style.background = '#f8fafc';
        loader.style.border = '1px dashed var(--color-border)';

        loader.innerHTML = `
            <div class="chat-bubble-meta" style="display: flex; align-items: center; gap: 0.4rem;">
                <span class="notranslate" translate="no">&#129302;</span>
                <strong class="notranslate" translate="no">LifeGPT Host</strong> &bull;
                <span style="color: var(--color-primary); font-weight: 600;">Searching collective wisdom...</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0;">
                <div class="lifegpt-spinner" style="width: 20px; height: 20px; border: 3px solid #cbd5e1; border-top-color: var(--color-primary); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                <span style="font-size: 0.95rem; color: var(--color-text-muted); font-style: italic;">
                    Retrieving lived experiences, evaluating relevance, and synthesizing wisdom...
                </span>
            </div>
            <style>
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
        `;
        chatContainer.appendChild(loader);
        scrollToBottom();
        return loader;
    }

    function renderAiBubble(data, timeStr) {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble chat-bubble-ai';
        bubble.style.alignSelf = 'flex-start';
        bubble.style.maxWidth = '85%';

        const score = typeof data.grounding_score === 'number' ? data.grounding_score : 50;
        const confLabel = data.confidence_label || (score >= 80 ? 'High grounding' : (score >= 60 ? 'Moderate grounding' : (score >= 40 ? 'Limited grounding' : 'Insufficient grounding')));
        const confColor = data.confidence_color || (score >= 80 ? '#16a34a' : (score >= 60 ? '#d97706' : (score >= 40 ? '#ea580c' : '#dc2626')));
        const confBadge = data.confidence_badge || (score >= 80 ? '#dcfce7' : (score >= 60 ? '#fef3c7' : (score >= 40 ? '#ffedd5' : '#fee2e2')));
        const srcCount = parseInt(data.sources_count || (data.sources ? data.sources.length : 0), 10);
        const disclaimer = data.disclaimer || 'This score reflects how strongly the answer is supported by relevant LifeGPT experiences. It is not a guarantee of factual correctness.';
        const intentType = data.intent_type || 'STANDALONE';

        const formattedAnswer = escapeHtml(data.answer || '').replace(/\n/g, '<br>');

        // Context badge shown on follow-up responses
        const contextBadge = intentType === 'FOLLOW_UP'
            ? `<span style="font-size:0.72rem; font-weight:600; padding:0.1rem 0.45rem; border-radius:999px; background:#ede9fe; color:#7c3aed; margin-left:0.4rem;" title="LifeGPT understood this as a follow-up and used conversation context">
                &#128279; Context-aware
              </span>`
            : '';

        let sourcesHtml = '';
        if (data.sources && data.sources.length > 0) {
            let itemsHtml = '';
            data.sources.forEach(src => {
                const expNum = escapeHtml(src.experience_num || '1');
                const topic = escapeHtml(src.topic || 'Life Experience');
                const author = escapeHtml(src.author || 'Anonymous Contributor');
                const insight = escapeHtml(src.key_insight || src.text || '');

                itemsHtml += `
                    <div style="font-size: 0.78rem; background: #ffffff; border: 1px solid var(--color-border); border-left: 3px solid var(--color-primary); border-radius: 4px; padding: 0.4rem 0.6rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 600; color: var(--color-primary); margin-bottom: 0.2rem;">
                            <span>Experience #<span class="notranslate" translate="no">${expNum}</span> &mdash; ${topic}</span>
                            <span style="color: var(--color-text-muted); font-weight: 400;">${author}</span>
                        </div>
                        <div style="color: var(--color-text-main); font-style: italic; line-height: 1.4;">
                            &ldquo;${insight}&rdquo;
                        </div>
                    </div>
                `;
            });

            sourcesHtml = `
                <details style="margin-top: 0.65rem; border-top: 1px dashed var(--color-border); padding-top: 0.45rem;">
                    <summary style="font-size: 0.8rem; font-weight: 600; color: var(--color-primary); cursor: pointer; user-select: none;">
                        View supporting experiences (<span class="notranslate" translate="no">${srcCount}</span>) &darr;
                    </summary>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem;">
                        ${itemsHtml}
                    </div>
                </details>
            `;
        }

        // Developer debug panel (shown when debug=1 in URL)
        let debugHtml = '';
        if (debugModeEnabled && data.debug) {
            const d = data.debug;
            const metrics = d.retrieval_metrics || {};
            const concepts = (d.context_concepts || []).join(', ') || 'none';
            debugHtml = `
                <details style="margin-top:0.5rem; border-top:1px dashed #cbd5e1; padding-top:0.4rem;" open>
                    <summary style="font-size:0.75rem; font-weight:700; color:#64748b; cursor:pointer;">&#128295; Debug Info</summary>
                    <div style="font-size:0.73rem; font-family:monospace; color:#475569; margin-top:0.3rem; display:grid; grid-template-columns:auto 1fr; gap:0.15rem 0.6rem;">
                        <span style="font-weight:600;">Intent:</span><span>${escapeHtml(d.intent_type)} (${Math.round((d.intent_confidence||0)*100)}% confidence &mdash; ${escapeHtml(d.intent_reason||'')})</span>
                        <span style="font-weight:600;">Contextual Query:</span><span style="font-style:italic;">&ldquo;${escapeHtml(d.contextual_query||'same as original')}&rdquo;</span>
                        <span style="font-weight:600;">Context Concepts:</span><span>${escapeHtml(concepts)}</span>
                        <span style="font-weight:600;">Mode:</span><span>${escapeHtml(metrics.context_mode||'standalone')}</span>
                        <span style="font-weight:600;">Best Semantic:</span><span>${(metrics.best_semantic_score||0).toFixed(4)}</span>
                        <span style="font-weight:600;">Best Keyword:</span><span>${(metrics.best_keyword_score||0).toFixed(4)}</span>
                        <span style="font-weight:600;">Best Context:</span><span>${(metrics.best_context_score||0).toFixed(4)}</span>
                        <span style="font-weight:600;">Best Hybrid:</span><span>${(metrics.best_hybrid_score||0).toFixed(4)}</span>
                        <span style="font-weight:600;">Stories Selected:</span><span>${metrics.selected_stories||0} / ${metrics.total_candidates||0} candidates</span>
                    </div>
                </details>
            `;
        }

        bubble.innerHTML = `
            <div class="chat-bubble-meta" style="display: flex; align-items: center; gap: 0.4rem; flex-wrap:wrap;">
                <span><span class="notranslate" translate="no">&#129302;</span></span>
                <strong class="notranslate" translate="no">LifeGPT Host</strong> &bull;
                <span class="notranslate" translate="no">${escapeHtml(timeStr)}</span>
                ${contextBadge}
            </div>
            <p style="font-size: 1.05rem; line-height: 1.6; margin-bottom: 0.75rem;">${formattedAnswer}</p>

            ${showGroundingScore ? `
            <div style="margin-top: 0.85rem; padding: 0.85rem 1rem; background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                        <span class="notranslate" translate="no">&#127919;</span>
                        <strong style="font-size: 0.88rem; color: var(--color-primary);"><span class="notranslate" translate="no">LifeGPT</span> Grounding Score:</strong>
                        <span class="notranslate" translate="no" style="font-size: 0.82rem; font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px; background: ${escapeHtml(confBadge)}; color: ${escapeHtml(confColor)};">
                            ${score}% &bull; ${escapeHtml(confLabel)}
                        </span>
                    </div>
                    <span style="font-size: 0.8rem; color: var(--color-text-muted);">
                        ${srcCount > 0 ? 'Based on <span class="notranslate" translate="no">' + srcCount + '</span> relevant LifeGPT ' + (srcCount === 1 ? 'experience' : 'experiences') : 'Limited archive match'}
                    </span>
                </div>

                <div style="height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-bottom: 0.45rem;">
                    <div style="height: 100%; width: ${Math.min(100, Math.max(4, score))}%; background: ${escapeHtml(confColor)}; border-radius: 99px; transition: width 0.4s ease;"></div>
                </div>

                <p style="font-size: 0.76rem; color: var(--color-text-muted); margin: 0; line-height: 1.4;">
                    ${escapeHtml(disclaimer)}
                </p>

                ${sourcesHtml}
                ${debugHtml}
            </div>
            ` : ''}

            <div class="citation-tag notranslate" translate="no">
                <span class="notranslate" translate="no">&#128220;</span> AI-assisted search across contributed stories
            </div>
        `;

        chatContainer.appendChild(bubble);
        scrollToBottom();
    }

    async function executeAsk(queryText) {
        const query = (queryText || '').trim();
        if (!query) return;

        removeEmptyStateIfPresent();

        const timeStr = formatTimeNow();
        renderUserBubble(query, timeStr);

        if (askQueryInput) {
            askQueryInput.value = '';
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
        }

        const loader = renderLoaderBubble();
        const csrf = getCsrfToken();

        try {
            const requestBody = {
                query: query,
                csrf_token: csrf
            };

            // Attach active conversation_id for context continuity
            if (activeConversationId) {
                requestBody.conversation_id = activeConversationId;
            }

            // Attach debug flag if enabled
            if (debugModeEnabled) {
                requestBody.debug = 1;
            }

            const res = await fetch(`${appUrl}/api/index.php?action=ask`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify(requestBody)
            });

            if (loader && loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }

            if (!res.ok) {
                let errMessage = `Server returned status ${res.status}`;
                try {
                    const errData = await res.json();
                    if (errData.error) errMessage = errData.error;
                } catch (e) {}
                throw new Error(errMessage);
            }

            const data = await res.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to retrieve wisdom response.');
            }

            // Update active conversation_id from response
            if (data.conversation_id) {
                activeConversationId = data.conversation_id;
                sessionStorage.setItem('lifegpt_conversation_id', activeConversationId);
                // Update New Chat button to show active state
                updateNewChatButton(true);
            }

            renderAiBubble(data, formatTimeNow());

        } catch (err) {
            if (loader && loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }

        } catch (err) {
            if (loader && loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }

            console.error('Ask LifeGPT API Error:', err);

            const errBubble = document.createElement('div');
            errBubble.className = 'chat-bubble chat-bubble-ai';
            errBubble.style.alignSelf = 'flex-start';
            errBubble.style.maxWidth = '85%';
            errBubble.style.background = '#fff1f2';
            errBubble.style.borderColor = '#fecdd3';
            errBubble.innerHTML = `
                <div class="chat-bubble-meta" style="color: #be123c;">⚠️ Search Notice &bull; ${escapeHtml(formatTimeNow())}</div>
                <p style="color: #9f1239; margin-bottom: 0.5rem;">
                    Could not complete search: <strong>${escapeHtml(err.message)}</strong>
                </p>
                <button type="button" class="btn btn-outline" style="padding: 0.35rem 0.85rem; font-size: 0.85rem;" onclick="window.askQuestion('${escapeHtml(query)}')">
                    🔄 Retry Question
                </button>
            `;
            chatContainer.appendChild(errBubble);
            scrollToBottom();
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
            if (askQueryInput) {
                askQueryInput.focus();
            }
        }
    }

    // Attach form submit event
    if (askForm) {
        askForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const text = askQueryInput ? askQueryInput.value : '';
            executeAsk(text);
        });
    }

    // Expose global askQuestion helper (for suggestions, chips, cards)
    window.askQuestion = function(q) {
        if (!q) return;
        const queryText = String(q).trim();
        if (!queryText) return;
        if (askQueryInput) {
            askQueryInput.value = queryText;
        }
        executeAsk(queryText);
    };

    // Global click listener for any element with data-question (topics, common questions, suggestion cards)
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('[data-question]');
        if (trigger) {
            e.preventDefault();
            const question = trigger.getAttribute('data-question');
            if (question) {
                window.askQuestion(question);
            }
        }
    });

    // --- New Chat button ---
    const newChatBtn = document.getElementById('newChatBtn');
    if (newChatBtn) {
        newChatBtn.addEventListener('click', function(e) {
            sessionStorage.removeItem('lifegpt_conversation_id');
            // If the element is a link to ?action=new_chat, allow native navigation
            const href = newChatBtn.getAttribute('href');
            if (!href || href === '#' || href === 'javascript:void(0)') {
                e.preventDefault();
                window.location.href = `${appUrl}/ask/?action=new_chat`;
            }
        });
    }

    // Initialize conversation_id from sessionStorage if page reloaded mid-conversation
    if (!activeConversationId) {
        const stored = sessionStorage.getItem('lifegpt_conversation_id');
        if (stored) activeConversationId = stored;
    }
});
