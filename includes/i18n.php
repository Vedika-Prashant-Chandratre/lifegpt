<?php
/**
 * LifeGPT - Simple Localization / String Helper
 * Clean English-first wisdom labels without emojis or broken characters.
 */
function getLangStrings(string $lang = 'en'): array {
    $strings = [
        'en' => [
            'nav_home' => 'Home', 'nav_how' => 'How It Works', 'nav_share' => 'Share a Story',
            'nav_ask' => 'Ask LifeGPT', 'nav_dashboard' => 'Dashboard', 'nav_signin' => 'Sign In',
            'hero_badge' => 'POWERED BY PEOPLE. ORGANIZED BY AI.',
            'hero_title' => 'Your life has answers someone else needs.',
            'hero_subtitle' => 'Talk with an AI host about something you learned, something you would do differently, or something that still makes you laugh. Participate without displaying your name, or sign in to save your stories.',
            'btn_share' => 'Share Your Story', 'btn_ask' => 'Ask LifeGPT',
            'ask_title' => 'Ask LifeGPT Search', 'ask_subtitle' => 'AI-assisted search across contributed stories',
            'ask_placeholder' => 'Ask anything (e.g. How to navigate a career change?)',
            'ask_send' => 'Send', 'ask_new_chat' => 'New Chat',
            'ask_topics' => 'Browse by Topic', 'ask_common_q' => 'Common Questions',
            'topic_career' => 'Career &amp; Work',
            'topic_family' => 'Family &amp; Relationships',
            'topic_health' => 'Health &amp; Aging',
            'topic_money' => 'Money &amp; Retirement',
            'topic_purpose' => 'Life Lessons &amp; Purpose',
            'accuracy_label' => 'Answer Accuracy', 'accuracy_high' => 'High Relevance',
            'accuracy_medium' => 'Moderate Relevance', 'accuracy_low' => 'Low Relevance',
            'sourced_from' => 'AI-assisted search across contributed stories',
            'ready_title' => 'Ready when you are.', 'guest_mode' => 'GUEST SEARCH MODE',
            'no_account' => 'No account or login required.', 'return_home' => '&larr; Return to Homepage',
            
            // Demo Questions (Derived from high-grounding RAG knowledge chunks)
            'q1' => 'How to change careers later in life?',
            'q2' => 'How to bounce back from major failure?',
            'q3' => 'What do people regret most about money?',
            'q4' => 'How to maintain healthy relationships & marriage?',
            'q5' => 'What is the best parenting and family advice?',
            'q6' => 'How do people cope with grief and loss?',
            'q7' => 'How to prepare mentally and financially for retirement?',
            'q8' => 'How do people find true purpose and meaning?',
            'q9' => 'What habits help people age gracefully & stay healthy?',
            
            // Full Queries sent to RAG pipeline on click
            'q1_full' => 'What practical guidance do people share about changing careers, switching industries, or starting anew after 40?',
            'q2_full' => 'What stories and practical lessons do people share about recovering from career failure, financial collapse, or setbacks?',
            'q3_full' => 'What are the biggest financial regrets people have, and what money habits do they recommend for long-term security?',
            'q4_full' => 'What advice do people share about maintaining healthy relationships, marriages, and resolving conflicts over the years?',
            'q5_full' => 'What wisdom and practical advice do experienced parents share about raising children and supporting family?',
            'q6_full' => 'What wisdom helps people navigate grief, bereavement, and finding strength after losing someone close?',
            'q7_full' => 'What advice do retirees give about transitioning into retirement, managing money, and staying active and fulfilled?',
            'q8_full' => 'How did experienced contributors find purpose, fulfillment, and peace of mind as they grew older?',
            'q9_full' => 'What daily habits, lifestyle choices, and mindset shifts do older adults recommend for physical wellness and healthy aging?',
        ],
    ];
    return $strings['en'];
}
