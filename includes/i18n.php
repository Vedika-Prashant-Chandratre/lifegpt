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
            'topic_career' => 'Career &amp; Work', 'topic_family' => 'Family &amp; Relationships',
            'topic_turning' => 'Turning Points', 'topic_health' => 'Health &amp; Aging',
            'topic_money' => 'Money &amp; Retirement', 'topic_humor' => 'Humor &amp; Mishaps',
            'accuracy_label' => 'Answer Accuracy', 'accuracy_high' => 'High Relevance',
            'accuracy_medium' => 'Moderate Relevance', 'accuracy_low' => 'Low Relevance',
            'sourced_from' => 'AI-assisted search across contributed stories',
            'ready_title' => 'Ready when you are.', 'guest_mode' => 'GUEST SEARCH MODE',
            'no_account' => 'No account or login required.', 'return_home' => '&larr; Return to Homepage',
            'q1' => 'What lesson do people wish they had learned earlier?',
            'q2' => 'How to change careers later in life?',
            'q3' => 'How to bounce back from failure?',
            'q4' => 'What do people regret about money?',
            'q5' => 'How to maintain healthy relationships?',
            'q6' => 'Advice on parenting and raising children?',
            'q7' => 'How to deal with grief and loss?',
            'q8' => 'Starting a business - what should I know?',
            'q9' => 'How to find purpose in life?',
            'q10' => 'Advice for someone who is 25 years old?',
            'q1_full' => 'What is the most important lesson older people wish they had learned earlier in life?',
            'q2_full' => 'How do people successfully change careers later in life?',
            'q3_full' => 'What advice do people give about handling failure and bouncing back stronger?',
            'q4_full' => 'What do people regret most about money and savings from their younger years?',
            'q5_full' => 'How do people maintain healthy relationships and marriages over long periods?',
            'q6_full' => 'What wisdom do experienced people share about raising children and parenting?',
            'q7_full' => 'How do people cope with grief and the loss of a loved one?',
            'q8_full' => 'What advice do people give about starting a business or becoming an entrepreneur?',
            'q9_full' => 'How did people find purpose and meaning later in their life?',
            'q10_full' => 'What life advice would experienced people give to a 25-year-old today?',
        ],
    ];
    return $strings['en'];
}
