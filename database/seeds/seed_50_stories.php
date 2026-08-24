<?php
/**
 * LifeGPT Seed Script - 50 Sample Experienced Storytellers
 * Generates 50 mock users, interviews, consents, summaries, and knowledge chunks
 * to populate the RAG database with high-quality wisdom content.
 */

// Define absolute paths
$baseDir = dirname(dirname(__DIR__));
require_once $baseDir . '/includes/config.php';
require_once $baseDir . '/includes/db.php';
require_once $baseDir . '/includes/auth.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=========================================\n";
echo "    Seeding 50 Storytellers to RAG DB\n";
echo "=========================================\n\n";

// Predefined high-quality wisdom templates (categories: career, relationship, money, turning_point, health)
$wisdomTemplates = [
    [
        'topic_key' => 'career',
        'topic_id' => 5, // Career and work
        'summary' => 'Reflections on transitioning from a corporate engineering job at age 52 to open a local woodworking shop. It explores the financial fear and the emotional satisfaction of manual craft.',
        'lesson' => 'True career satisfaction comes from doing work where you can see the tangible results of your labor, rather than climbing a corporate ladder that feels empty.',
        'turning_point' => 'Deciding to leave a secure senior director role at a tech firm after realizing the stress was affecting my relationship with my children.',
        'outcome' => 'The business took two years to break even, but the reduction in stress, physical activity, and closer family relationships made it the best decision of my life.',
        'advice' => 'Do not stay in a toxic or soul-crushing job just because the paycheck is secure. Your health and your family relationships are assets you can never buy back.',
        'quote' => 'My hands are full of splinters now, but my heart is no longer empty.',
        'profession' => 'Retired Software Engineer turned Woodworker',
        'tags' => ['Career Change', 'Stress', 'Craftsmanship']
    ],
    [
        'topic_key' => 'relationships',
        'topic_id' => 6, // Family and relationships
        'summary' => 'A story of navigating a 40-year marriage. It outlines how active listening and giving each other space to grow individually saved their marriage during mid-life transitions.',
        'lesson' => 'Marriage is not about finding someone who never changes, but rather about learning to love the different versions of the person they become over decades.',
        'turning_point' => 'Attending marriage counseling in our late 30s instead of filing for divorce when we felt we had drifted completely apart.',
        'outcome' => 'We learned communication skills that helped us grow closer, survive empty-nest syndrome, and enjoy our retirement years as best friends.',
        'advice' => 'Never let an argument end without acknowledging that you value the relationship more than being right. Learn to say "I value us more than this argument."',
        'quote' => 'A long marriage is just two good forgivers who refused to give up at the same time.',
        'profession' => 'Retired Family Counselor',
        'tags' => ['Marriage', 'Forgiveness', 'Communication']
    ],
    [
        'topic_key' => 'money',
        'topic_id' => 7, // Money and retirement
        'summary' => 'Lessons learned from recovering from a severe bankruptcy in the early 1990s due to speculative real estate investments. Focuses on learning to live minimally.',
        'lesson' => 'Wealth is not defined by how much you spend, but by how much freedom you have. Financial security is built by avoiding debt and living below your means.',
        'turning_point' => 'Losing my home and business in the 1991 market crash, forcing my family to move into a rented two-bedroom apartment.',
        'outcome' => 'It taught us to find joy in free activities like local library events, hiking, and cooking together, rebuilding a secure savings account slowly.',
        'advice' => 'Start saving 10% of everything you earn from your very first paycheck. Avoid credit card debt at all costs; if you cannot pay cash, you cannot afford it.',
        'quote' => 'Losing all my money was the hardest way to find out what actually made me wealthy.',
        'profession' => 'Retired High School Math Teacher',
        'tags' => ['Financial Recovery', 'Savings', 'Frugality']
    ],
    [
        'topic_key' => 'turning_point',
        'topic_id' => 10, // A major turning point
        'summary' => 'A reflection on surviving a aggressive cancer diagnosis at age 45. It highlights how the scare reprioritized my time toward community service.',
        'lesson' => 'Life is fragile and temporary. Waiting for "someday" to do what you love or help others is a gamble you will eventually lose.',
        'turning_point' => 'Receiving the pathology report that I was in remission, prompting me to leave corporate marketing to run a local food bank.',
        'outcome' => 'I have earned less money over the last 15 years, but I wake up every morning knowing my effort directly feeds hungry families.',
        'advice' => 'Regularly audit how you spend your time. If you are spending 60 hours a week on things you do not care about, make a plan to change it today.',
        'quote' => 'Cancer broke my body, but it finally fixed my perspective on what matters.',
        'profession' => 'Food Bank Coordinator',
        'tags' => ['Health Scare', 'Perspective', 'Altruism']
    ],
    [
        'topic_key' => 'advice',
        'topic_id' => 3, // Advice for younger people
        'summary' => 'Reflections on the importance of maintaining physical fitness, flexibility, and strength as you enter middle age to enjoy a long, active retirement.',
        'lesson' => 'Your physical body is the only home you are guaranteed to live in. If you do not invest in its maintenance early, retirement will be painful.',
        'turning_point' => 'Suffering a severe back injury at age 40 due to sedentary lifestyle, which motivated me to take up swimming and yoga.',
        'outcome' => 'At age 68, I am able to hike mountains with my grandchildren, travel, and remain fully independent without chronic pain.',
        'advice' => 'Move your body for at least 30 minutes every day. Do not wait for a health crisis to start walking, stretching, and eating real food.',
        'quote' => 'If you do not make time for your wellness, you will eventually be forced to make time for your illness.',
        'profession' => 'Retired Physical Therapist',
        'tags' => ['Health', 'Exercise', 'Aging']
    ],
    [
        'topic_key' => 'technology',
        'topic_id' => 9, // Technology and how life changed
        'summary' => 'Reflections on growing up in a world without smartphones and how the constant digital connection has eroded deep, undistracted conversation.',
        'lesson' => 'True relationships are built in quiet, shared moments without the ping of a notification. Convenience has replaced connection.',
        'turning_point' => 'Instituting a "no-phone Sunday" policy in my household ten years ago to force face-to-face interaction.',
        'outcome' => 'Our family conversations became deeper, we read more books, and our relationships grew significantly stronger than our peers.',
        'advice' => 'Turn off all non-essential notifications on your phone. Dedicate at least one hour every day to talk to your loved ones without any screens nearby.',
        'quote' => 'Technology makes it easy to speak to someone across the world, but harder to talk to the person across the table.',
        'profession' => 'Retired Librarian',
        'tags' => ['Technology', 'Connection', 'Digital Detox']
    ],
    [
        'topic_key' => 'funny',
        'topic_id' => 4, // A funny life experience
        'summary' => 'A lighthearted story about a major mishap during a wedding ceremony where the ring bearer (a golden retriever) chased a squirrel into a lake.',
        'lesson' => 'Perfect moments are overrated. The mishaps, mistakes, and unexpected disasters are the things that make life memorable and funny.',
        'turning_point' => 'Choosing to laugh and jump in the lake to get the dog instead of crying or canceling the reception.',
        'outcome' => 'It became a legendary family story, wedding pictures of us wet and muddy are our favorites, and it taught us to roll with life\'s punches.',
        'advice' => 'When things go wrong, ask yourself: "Will this be funny in ten years?" If the answer is yes, save yourself the stress and start laughing now.',
        'quote' => 'Life is a comedy written by an amateur. Don\'t take the script too seriously.',
        'profession' => 'Retired Event Coordinator',
        'tags' => ['Humor', 'Wedding', 'Resilience']
    ]
];

$firstNames = ['Helen', 'John', 'Robert', 'Mary', 'William', 'Dorothy', 'James', 'Patricia', 'Charles', 'Linda', 'Thomas', 'Barbara', 'Richard', 'Elizabeth', 'Joseph', 'Jennifer', 'David', 'Maria', 'George', 'Susan'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Garcia', 'Rodriguez', 'Wilson', 'Martinez', 'Anderson', 'Taylor', 'Thomas', 'Hernandez', 'Moore', 'Martin', 'Jackson', 'Thompson', 'White'];
$countries = ['United States', 'Canada', 'United Kingdom', 'Australia', 'New Zealand', 'Ireland', 'South Africa'];
$genders = ['male', 'female', 'non_binary'];

$personaIds = DB::fetchAll("SELECT persona_id FROM lg_interviewer_personas");
$personaList = array_column($personaIds, 'persona_id');

if (empty($personaList)) {
    die("ERROR: No personas found in the database. Please run setup_db.php first!\n");
}

try {
    DB::beginTransaction();
    
    $seededCount = 0;
    
    for ($i = 1; $i <= 50; $i++) {
        // 1. Generate User Details
        $gender = $genders[array_rand($genders)];
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $displayName = $firstName . ' ' . $lastName;
        $username = strtolower($firstName . '_' . $lastName . '_' . rand(10, 99));
        $email = $username . '@example.com';
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $age = rand(50, 88);
        $country = $countries[array_rand($countries)];
        
        // Pick a template
        $tpl = $wisdomTemplates[array_rand($wisdomTemplates)];
        $profession = $tpl['profession'];
        $aboutMe = "Reflecting on " . $tpl['topic_key'] . " lessons. Believer in passing wisdom down to future generations.";
        
        $uuid = Auth::generateUUID();
        
        // Insert User
        $userId = DB::insert(
            "INSERT INTO lg_users (uuid, email, password_hash, username, display_name, age, country, profession, gender, about_me, role, status) 
             VALUES (:uuid, :email, :password_hash, :username, :display_name, :age, :country, :profession, :gender, :about_me, 'member', 'active')",
            [
                'uuid' => $uuid,
                'email' => $email,
                'password_hash' => $passwordHash,
                'username' => $username,
                'display_name' => $displayName,
                'age' => $age,
                'country' => $country,
                'profession' => $profession,
                'gender' => $gender,
                'about_me' => $aboutMe
            ]
        );
        
        // 2. Insert Interview
        $interviewUuid = Auth::generateUUID();
        $personaId = $personaList[array_rand($personaList)];
        $topicId = $tpl['topic_id'];
        
        $interviewId = DB::insert(
            "INSERT INTO lg_interviews (uuid, user_id, persona_id, topic_id, status, language, input_mode, duration_type) 
             VALUES (:uuid, :user_id, :persona_id, :topic_id, 'completed', 'en', 'typing', 'standard')",
            [
                'uuid' => $interviewUuid,
                'user_id' => $userId,
                'persona_id' => $personaId,
                'topic_id' => $topicId
            ]
        );
        
        // 3. Insert Consent
        DB::insert(
            "INSERT INTO lg_consents (interview_id, storage_consent, rag_consent, quotes_consent, research_consent, publication_consent, attribution_type, attribution_value, withdrawn, version) 
             VALUES (:interview_id, 1, 1, 1, 1, 1, 'first_name', :attr_val, 0, 1)",
            [
                'interview_id' => $interviewId,
                'attr_val' => $firstName
            ]
        );
        
        // 4. Insert Messages (Mock conversation of 4 lines)
        DB::insert(
            "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
             VALUES (:interview_id, 1, 'interviewer', 'Welcome. What is a key life experience you want to share?', 'starter')",
            ['interview_id' => $interviewId]
        );
        
        DB::insert(
            "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
             VALUES (:interview_id, 2, 'contributor', :text, NULL)",
            [
                'interview_id' => $interviewId,
                'text' => "I wanted to share my perspective regarding " . strtolower($tpl['summary'])
            ]
        );
        
        DB::insert(
            "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
             VALUES (:interview_id, 3, 'interviewer', 'Thank you. What decision mattered most during this time?', 'follow_up')",
            ['interview_id' => $interviewId]
        );
        
        DB::insert(
            "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
             VALUES (:interview_id, 4, 'contributor', :text, NULL)",
            [
                'interview_id' => $interviewId,
                'text' => $tpl['turning_point'] . " " . $tpl['outcome']
            ]
        );
        
        // 5. Insert Summary
        DB::insert(
            "INSERT INTO lg_interview_summaries (interview_id, story_summary, main_lesson, turning_point, outcome, advice, funny_moment, representative_quote, approved_summary, approved_quote) 
             VALUES (:interview_id, :summary, :lesson, :turning_point, :outcome, :advice, NULL, :quote, 1, 1)",
            [
                'interview_id' => $interviewId,
                'summary' => $tpl['summary'],
                'lesson' => $tpl['lesson'],
                'turning_point' => $tpl['turning_point'],
                'outcome' => $tpl['outcome'],
                'advice' => $tpl['advice'],
                'quote' => $tpl['quote']
            ]
        );
        
        // 6. Insert Knowledge Chunks as APPROVED (so RAG retrieves them immediately)
        $chunks = [
            ['type' => 'summary', 'text' => $tpl['summary']],
            ['type' => 'lesson', 'text' => $tpl['lesson']],
            ['type' => 'turning_point', 'text' => $tpl['turning_point']],
            ['type' => 'outcome', 'text' => $tpl['outcome']],
            ['type' => 'advice', 'text' => $tpl['advice']],
            ['type' => 'quote', 'text' => $tpl['quote']]
        ];
        
        foreach ($chunks as $c) {
            DB::insert(
                "INSERT INTO lg_knowledge_chunks (interview_id, content_type, text, anonymized_text, approved_for_rag, approved_for_publication, status) 
                 VALUES (:interview_id, :type, :text, :anon, 1, 1, 'approved')",
                [
                    'interview_id' => $interviewId,
                    'type' => $c['type'],
                    'text' => $c['text'],
                    'anon' => $c['text']
                ]
            );
        }
        
        $seededCount++;
    }
    
    DB::commit();
    echo "SUCCESS: Successfully seeded $seededCount mock experienced storytellers and populated the RAG wisdom database!\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR during seeding: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
