<?php
/**
 * LifeGPT - Rich Life Stories Seeder
 * Adds 45 diverse, realistic, and deeply insightful human life stories
 * covering career, divorce, parenting, health, money, grief, friendship,
 * mental resilience, retirement, and entrepreneurship.
 * Automatically generates 256-dim embeddings and marks them approved for RAG.
 */

$appRoot = 'C:/xampp/htdocs/lifegpt';
require_once $appRoot . '/includes/config.php';
require_once $appRoot . '/includes/db.php';
require_once $appRoot . '/includes/services/EmbeddingService.php';

function generateUuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$stories = [
    // -------------------------------------------------------------
    // DOMAIN 1: CAREER BURNOUT, TRANSITION & SABBATICAL (5 stories)
    // -------------------------------------------------------------
    [
        'topic_id' => 5, // Career and work
        'persona_id' => 3,
        'summary' => 'After fifteen years in high-pressure investment banking, suffering heart palpitations and complete emotional exhaustion at 43, I took an unannounced six-month sabbatical. Stepping away from 80-hour workweeks allowed my nervous system to recalibrate and helped me realize my identity was entirely tied to a corporate title.',
        'turning_point' => 'Collapsing in an airport lounge from severe panic and exhaustion, which made me realize that continuing on this trajectory would cost me my life.',
        'outcome' => 'I transitioned into consulting for non-profits and sustainable agriculture, earning 40% less but regaining my sleep, physical vitality, and time with family.',
        'lesson' => 'Burnout is not resolved by a long weekend or a vacation; it requires confronting the unexamined belief that your worth as a person is measured by professional productivity.',
        'advice' => 'When chronic exhaustion sets in, build a six-month living cushion and step away completely before your body forces the decision for you with a medical emergency.',
        'quote' => 'I thought leaving the corporate ladder would end my life, but it was actually the first day my real life began.'
    ],
    [
        'topic_id' => 5,
        'persona_id' => 2,
        'summary' => 'Navigating a sudden corporate layoff at age 51 after twenty-two years with the same telecommunications firm. The shock of being escorted out of the building with a security guard was devastating, leading to months of demoralizing job applications.',
        'turning_point' => 'Deciding to stop sending cold resumes to corporate portals and instead offering fractional operations mentoring to young tech startup founders in my city.',
        'outcome' => 'Within ten months, I built a reliable client base of five high-growth companies that valued my decades of crisis management and institutional knowledge.',
        'lesson' => 'A sudden career disruption feels like a catastrophe in the moment, but it often liberates you from golden handcuffs and forces you to discover your true market adaptability.',
        'advice' => 'Never depend entirely on one employer for your sense of security. Always cultivate an independent professional network and maintain skills that exist outside company walls.',
        'quote' => 'They took away my security badge, but they could not take away thirty years of problem-solving experience.'
    ],
    [
        'topic_id' => 5,
        'persona_id' => 1,
        'summary' => 'Leaving a tenured university professorship at 48 to become a landscape designer and arborist. Friends and colleagues thought I was throwing away intellectual status for manual labor, but the daily contact with living soil and outdoor work brought unparalleled peace.',
        'turning_point' => 'Spending an entire summer tending to an overgrown community garden and noticing that my chronic insomnia completely vanished when my body was physically tired.',
        'outcome' => 'Built a thriving landscape practice focusing on native drought-tolerant gardens, enjoying physical health and mental clarity far superior to my academic years.',
        'lesson' => 'Intellectual prestige cannot compensate for a sedentary, spiritually suffocating daily routine. Honest physical labor with tangible outcomes often heals an overactive mind.',
        'advice' => 'If you dream of changing fields later in life, apprentice yourself to an expert on weekends before making any permanent financial leap.',
        'quote' => 'Working with trees taught me that roots need quiet darkness and patience, not corporate quarterly deadlines.'
    ],
    [
        'topic_id' => 5,
        'persona_id' => 4,
        'summary' => 'Learning to overcome debilitating imposter syndrome as a newly appointed chief executive who did not attend an elite college. For the first two years, every board meeting triggered immense anxiety that I would be exposed as inadequate.',
        'turning_point' => 'Admitting openly to my executive coach that I felt like a fraud, only to learn that almost every accomplished leader around the table secretly harbored the identical fear.',
        'outcome' => 'Shifted my leadership style from defensive perfectionism to transparent curiosity, which boosted employee trust and improved company performance.',
        'lesson' => 'Imposter syndrome is often evidence of deep humility and high conscientiousness; the danger lies in letting it paralyze your willingness to make bold decisions.',
        'advice' => 'Stop trying to know all the answers before speaking. A great leader asks clarifying questions and creates space for their team to shine rather than pretending omniscience.',
        'quote' => 'Courage is not the absence of feeling like an imposter; it is acting with integrity despite the voice in your head.'
    ],
    [
        'topic_id' => 5,
        'persona_id' => 2,
        'summary' => 'Dealing with a profoundly toxic, manipulative boss who routinely took credit for team successes and shifted blame for mistakes. After two years of chronic stomach ulcers and dreading Sunday evenings, I developed an exit strategy.',
        'turning_point' => 'Documenting every project milestone meticulously and having a confidential conversation with an external recruiter rather than staying in hope that the manager would change.',
        'outcome' => 'Secured an equivalent position in an organization known for psychological safety, immediately noticing my physical health and optimism return.',
        'lesson' => 'You cannot change a toxic workplace culture or an abusive supervisor through extra dedication. The only winning move in an unhealthy environment is a strategic departure.',
        'advice' => 'Never quit impulsively in anger; stay professional, document your contributions, build financial runway, and depart with dignity and clean references.',
        'quote' => 'No salary is high enough to justify selling your mental peace and physical well-being.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 2: MARRIAGE, DIVORCE & RELATIONSHIP HEALING (6 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 6, // Family and relationships
        'persona_id' => 1,
        'summary' => 'Surviving a painful divorce after twenty-four years of marriage when the children left for college. Rebuilding a solitary life at 54 was terrifying, involving legal battles, selling the family house, and confronting deep grief.',
        'turning_point' => 'Realizing that grieving the end of a marriage is just like grieving a death, and choosing to join a weekly support group rather than isolating myself in bitterness.',
        'outcome' => 'Discovered genuine independence, bought a small sunlit apartment, learned to cook international cuisines, and forged deep adult friendships.',
        'lesson' => 'Divorce marks the end of a chapter, not the end of a worthwhile life. The loneliness of being alone is far less damaging than the chronic loneliness of being in an empty union.',
        'advice' => 'Allow yourself to grieve without rushing into a rebound romance. Spend at least two years getting comfortable in your own skin and understanding your emotional boundaries.',
        'quote' => 'I thought my identity was destroyed when my marriage ended, but I was actually meeting myself for the very first time.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 3,
        'summary' => 'Rebuilding trust in our marriage after an emotional affair nearly destroyed our fifteen-year relationship. We committed to weekly couples therapy for eighteen months, learning radical emotional transparency.',
        'turning_point' => 'Recognizing that infidelity was a destructive symptom of years of unspoken loneliness and emotional disengagement on both sides.',
        'outcome' => 'Our relationship became vastly more honest, passionate, and resilient than it had ever been in our twenties.',
        'lesson' => 'Forgiveness is not forgetting or excusing betrayal; it is a daily discipline of letting go of the desire to punish your partner so genuine healing can take root.',
        'advice' => 'True reconciliation requires total accountability from the one who broke trust, coupled with willingness from both partners to examine the cracks in the marriage foundation.',
        'quote' => 'Broken pottery repaired with gold becomes stronger and more beautiful at the seams than it was before.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 2,
        'summary' => 'Learning to spot red flags in a romantic relationship before getting married. After ignoring early warning signs of extreme jealousy and financial secrecy, my first marriage ended after four stressful years.',
        'turning_point' => 'Realizing that how a person handles minor disagreements and how they treat waitstaff or strangers is a precise preview of how they will treat you when the honeymoon fades.',
        'outcome' => 'Took three years to understand my own attachment patterns and subsequently met a kind, emotionally mature partner with whom I have shared fifteen peaceful years.',
        'lesson' => 'Never marry potential or assume love will magically cure disrespect, moodiness, or financial dishonesty. Character is revealed in ordinary daily reactions.',
        'advice' => 'Pay close attention to how someone reacts when you tell them "no." If they respond with guilt, anger, or silent treatment, that is a boundary violation you cannot ignore.',
        'quote' => 'Love is not enough to sustain a life together; mutual respect and shared integrity are the actual bedrock.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 1,
        'summary' => 'Reflections on keeping curiosity and romance alive in a thirty-five-year marriage. As the decades passed and bodies aged, our connection evolved from youthful passion into a profound, joyful partnership.',
        'turning_point' => 'Instituting an untouchable weekly date night with a strict rule against discussing bills, children, or household maintenance.',
        'outcome' => 'Maintained deep intimacy and laughter into our late sixties, feeling like each other’s favorite companion through life’s storms.',
        'lesson' => 'A lasting marriage requires continually re-choosing each other. People evolve across thirty years, and you must stay interested in learning the person your spouse is becoming.',
        'advice' => 'Express appreciation for small daily acts—making coffee, folding laundry, a warm smile. Contempt kills affection, but gratitude constantly rekindles it.',
        'quote' => 'We are not the same two kids who stood at the altar in 1988, but we are still each other’s absolute best friend.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 4,
        'summary' => 'Navigating amicable co-parenting after an emotionally difficult separation with two young children. We agreed never to speak ill of each other in the children’s presence and shared holidays equitably.',
        'turning_point' => 'Formulating a shared pledge that our children’s psychological well-being was infinitely more important than our personal grievances.',
        'outcome' => 'Both children grew into emotionally balanced adults who never felt forced to choose between their parents or endure divided loyalties.',
        'lesson' => 'You can discontinue a romantic partnership while remaining exceptional parenting teammates if you check your ego at the door.',
        'advice' => 'Treat your co-parent like a valued business partner: keep communication factual, respectful, punctual, and strictly focused on the child’s welfare.',
        'quote' => 'We failed at being husband and wife, but we succeeded completely at being mother and father together.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 5,
        'summary' => 'Making peace with remaining unmarried and childfree by choice in a society that constantly pressured me to follow traditional conventions. Embraced a life rich in artistic expression, community organizing, and mentorship.',
        'turning_point' => 'Turning forty and letting go of the quiet apologetic feeling that I was missing some obligatory life script.',
        'outcome' => 'Created an expansive chosen family, traveled across forty nations, and mentored dozens of young artists who consider me a second parent.',
        'lesson' => 'Fulfillment has many authentic blueprints. A life guided by deep intention and generous community contribution is complete and worthy in every respect.',
        'advice' => 'Never enter marriage or parenthood out of fear of being left out or lonely in old age. Build deep community connections that sustain you through every decade.',
        'quote' => 'A solitary life is not an empty life; when filled with purpose and love, it is a magnificent garden.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 3: PARENTING & FAMILY HEALING (5 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 6,
        'persona_id' => 1,
        'summary' => 'Parenting a teenager through severe adolescent depression, school refusal, and self-harm. Learning that lecturing and problem-solving only pushed my daughter away, while silent presence and compassionate listening opened doors.',
        'turning_point' => 'Stopping my instinct to offer quick solutions and simply sitting on the floor of her bedroom in quiet support during an emotional breakdown.',
        'outcome' => 'With professional therapy and reduced academic pressure, she gradually healed and rebuilt self-confidence, now thriving at twenty-four.',
        'lesson' => 'Children do not need perfect parents; they need parents who can regulate their own anxieties and provide a calm, steady anchor when their world is shaking.',
        'advice' => 'When your adolescent is struggling emotionally, listen to understand rather than to fix. Validate their pain before offering any advice.',
        'quote' => 'My daughter did not need me to fix her storm; she just needed to know I would sit with her in the rain without running away.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 3,
        'summary' => 'Learning to repair a strained relationship with an adult son who had cut off contact for eighteen months over past authoritarian parenting mistakes.',
        'turning_point' => 'Writing a sincere, unreserved letter of apology without making excuses, without mentioning my intentions, and validating his feelings entirely.',
        'outcome' => 'Contact was slowly restored on healthy terms, transitioning into a respectful adult friendship built on clear personal boundaries.',
        'lesson' => 'Parents must recognize that their adult children owe them nothing out of obligation; healthy adult family relationships are built on mutual respect and genuine accountability.',
        'advice' => 'If your adult child sets distance, honor their space. Never guilt-trip them; instead, examine where you caused hurt and offer sincere, unconditional apologies.',
        'quote' => 'Admitting to my son that I made major parenting mistakes was the hardest thing I ever did, but it was the only key that unlocked his heart.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 2,
        'summary' => 'Supporting an aging parent through progressive dementia while raising teenage children simultaneously—the exhausting sandwich generation dilemma.',
        'turning_point' => 'Realizing that trying to be the sole caregiver while working full-time was causing me to develop severe hypertension and severe caregiver burnout.',
        'outcome' => 'Organized community respite care, joined a caregiver support group, and learned to accept help without feeling guilty.',
        'lesson' => 'Caregiver burnout helps no one. You cannot pour compassionate care from an empty vessel; self-preservation is a duty of love to your family.',
        'advice' => 'Seek outside support and adult day programs early in the diagnosis. Never attempt to shoulder total dementia caregiving alone.',
        'quote' => 'Grieving someone who is still physically present is a unique heartbreak; self-compassion is your only survival tool.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 5,
        'summary' => 'Resolving a bitter inheritance and estate dispute among three siblings after our mother passed away without an updated will. Greed and old childhood rivalries threatened to destroy family ties permanently.',
        'turning_point' => 'Hiring a neutral family mediator and deciding consciously that preserving sibling relationships was worth conceding financial assets.',
        'outcome' => 'We reached an equitable division of property and preserved holiday family traditions, preventing permanent estrangement.',
        'lesson' => 'Money and property will never keep you warm at night. Inherited wealth is the fastest poison for families if pride takes precedence over kinship.',
        'advice' => 'Prepare explicit, transparent estate plans and healthcare directives with legal professionals before you fall ill, so your children are spared painful disputes.',
        'quote' => 'I would rather lose half an inheritance than lose my brother for the rest of my life.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 4,
        'summary' => 'Raising children with strong values and work ethic despite growing up in extreme poverty myself and achieving substantial financial wealth later in life.',
        'turning_point' => 'Refusing to buy my teenagers brand-name sports cars and requiring them to hold part-time summer jobs at minimum wage to understand the value of labor.',
        'outcome' => 'Both children graduated college with financial discipline, deep empathy for working people, and strong personal ambition.',
        'lesson' => 'Shielding children from every discomfort and indulging every desire deprives them of the resilience and pride that only self-reliance provides.',
        'advice' => 'Give your children enough resources so they can do anything, but not so much that they can do nothing. Teach budgeting and charitable giving early.',
        'quote' => 'The greatest gift a parent can give is not an inheritance of money, but an inheritance of character and resilience.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 4: MONEY, BANKRUPTCY & FINANCIAL RECOVERY (5 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 7, // Money and retirement
        'persona_id' => 3,
        'summary' => 'Recovering from complete financial bankruptcy at 46 after a retail business partnership collapsed under fraudulent debts. We lost our home and moved into a small basement apartment with our two children.',
        'turning_point' => 'Attending a debt counseling workshop and learning to strip away all ego around status, cars, and material appearances.',
        'outcome' => 'Slowly cleared all obligations over seven years, rebuilt credit, and purchased a modest townhouse with a conservative fixed mortgage.',
        'lesson' => 'Your financial net worth has nothing to do with your human self-worth. Bankruptcy is a legal mechanism to reset, not a permanent moral failure.',
        'advice' => 'When facing severe financial ruin, get professional legal advice immediately. Cut all non-essential expenses and focus relentlessly on cash flow and food on the table.',
        'quote' => 'Losing everything forced me to realize that none of the things I lost were what actually kept my family happy.'
    ],
    [
        'topic_id' => 7,
        'persona_id' => 2,
        'summary' => 'Learning to invest conservatively after losing nearly 70% of life savings in a speculative stock market bubble during my late thirties.',
        'turning_point' => 'Vowing never to chase hot tips, crypto hype, or high-risk schemes, shifting entirely to low-cost broad index funds and steady monthly compounding.',
        'outcome' => 'Rebuilt a substantial retirement nest egg over twenty-two years of disciplined monthly dollar-cost averaging.',
        'lesson' => 'Wealth building is supposed to be boring and methodical, like watching grass grow. The desire to get rich quickly is the surest recipe for financial devastation.',
        'advice' => 'Live on 75% of your income, automatically invest 15% in low-cost index funds, maintain a 6-month emergency buffer in cash, and ignore financial news sensationalism.',
        'quote' => 'Financial independence is not about buying luxury; it is about buying your freedom and autonomy from worry.'
    ],
    [
        'topic_id' => 7,
        'persona_id' => 1,
        'summary' => 'The truth about homeownership versus renting learned over forty years. Experiencing both the pride of owning a family home and the hidden costs of property taxes, maintenance, interest, and roof replacements.',
        'turning_point' => 'Calculating the actual 30-year return on our primary residence and discovering that after interest and upkeep, simple index funds would have yielded double.',
        'outcome' => 'Downsized in our late fifties into a manageable rental near public transit, freeing substantial capital for travel and peace of mind.',
        'lesson' => 'A primary residence is a lifestyle choice and shelter, not an investment miracle. Buying more house than you need enslaves you to a mortgage.',
        'advice' => 'Never stretch your budget to the maximum allowed by a bank mortgage officer. Buy or rent well below your means so unexpected repairs never create panic.',
        'quote' => 'A big house does not make a happy home; an affordable home with minimal stress makes a peaceful life.'
    ],
    [
        'topic_id' => 7,
        'persona_id' => 4,
        'summary' => 'Overcoming chronic credit card debt and compulsive spending habits that originated from trying to impress friends and keep up with social circles.',
        'turning_point' => 'Cutting up five credit cards in front of a trusted friend and committing to an all-cash envelope budget for three straight years.',
        'outcome' => 'Became 100% debt-free by age 42, experienced profound peace of mind, and found genuine friends who cared about conversation rather than expensive dinners.',
        'lesson' => 'Most people spend money they do not have to buy things they do not need to impress people they do not even like. True dignity is living debt-free.',
        'advice' => 'Wait forty-eight hours before any non-essential purchase over fifty dollars. You will find that eight times out of ten, the urge evaporates completely.',
        'quote' => 'The day I stopped caring what strangers thought of my car was the day I became financially wealthy.'
    ],
    [
        'topic_id' => 7,
        'persona_id' => 5,
        'summary' => 'Managing finances in retirement on a modest fixed pension and social security by mastering the art of joyful frugality and community resources.',
        'turning_point' => 'Discovering that local public libraries, community centers, walking clubs, and home cooking offered richer social connection than commercial entertainment.',
        'outcome' => 'Enjoyed an active, culturally rich retirement for twenty years without ever running out of funds or relying on financial support from children.',
        'lesson' => 'You can live an extraordinarily rich and dignified life on modest means if your desires are simple and your social connections are strong.',
        'advice' => 'Cultivate free hobbies early: reading, gardening, hiking, volunteering, and playing music. They keep your mind sharp and your expenses negligible.',
        'quote' => 'Wealth consists not in having great possessions, but in having few wants.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 5: HEALTH CRISIS, CHRONIC ILLNESS & AGING (5 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 8, // Health and aging
        'persona_id' => 2,
        'summary' => 'Surviving Stage 3 colon cancer diagnosis at age 49. Undergoing aggressive chemotherapy, surgery, and eighteen months of physical and emotional trauma.',
        'turning_point' => 'Realizing that medical treatment was only half the battle; maintaining mental hope, community connection, and daily gratitude was essential for survival.',
        'outcome' => 'Ten years in complete remission, with transformed priorities, deeper relationships, and a commitment to helping newly diagnosed oncology patients.',
        'lesson' => 'A life-threatening diagnosis strips away all trivial worries. It clarifies in an instant who truly loves you and what truly matters in your remaining days.',
        'advice' => 'Never postpone routine health screenings, colonoscopies, or mammograms. Early detection is the literal difference between life and death.',
        'quote' => 'Before cancer, I had a thousand trivial problems; after cancer, I realized I only had one real problem—and that every sunrise was a gift.'
    ],
    [
        'topic_id' => 8,
        'persona_id' => 1,
        'summary' => 'Recovering from a debilitating stroke at 58 that temporarily paralyzed my right side and impaired my speech. Facing months of grueling physical therapy.',
        'turning_point' => 'Deciding to celebrate microscopic improvements—wiggling one toe, saying two clear words—instead of agonizing over what I could no longer do.',
        'outcome' => 'Regained 90% of mobility and speech over two years of daily rehabilitation, walking 5,000 steps every morning.',
        'lesson' => 'The human brain possesses astonishing neuroplasticity and capacity to heal when sustained by daily, patient, relentless practice.',
        'advice' => 'Learn the warning signs of stroke immediately (FAST: Face drooping, Arm weakness, Speech difficulty, Time to call). Every minute counts.',
        'quote' => 'Recovery is not a sprint; it is ten thousand tiny stubborn steps taken when nobody is watching.'
    ],
    [
        'topic_id' => 8,
        'persona_id' => 3,
        'summary' => 'Transforming personal health at 53 after being diagnosed with severe Type 2 diabetes, high cholesterol, and obesity. Dropped 65 pounds and reversed medication needs through whole-food nutrition and daily walking.',
        'turning_point' => 'Watching my own father suffer severe diabetic complications and leg amputation, vowing that I would not put my children through the same trauma.',
        'outcome' => 'Normalized blood sugar levels, eliminated insulin dependencies, and completed my first 10K walking race at age 57.',
        'lesson' => 'It is never too late to reclaim your vitality. Your body will respond with incredible vigor to consistent nutrition, movement, and sleep even in midlife.',
        'advice' => 'Do not follow extreme fad diets. Focus on three sustainable pillars: walk 30 minutes every single day, eliminate liquid sugars, and cook meals from whole ingredients.',
        'quote' => 'Taking care of your health is the highest form of respect and gratitude you can give to your family and your future self.'
    ],
    [
        'topic_id' => 8,
        'persona_id' => 4,
        'summary' => 'Learning to live with chronic autoimmune arthritis and persistent daily pain without succumbing to despair or bitterness.',
        'turning_point' => 'Practicing mindfulness-based stress reduction and learning to separate physical sensation from emotional suffering.',
        'outcome' => 'Adapted daily routines to preserve energy, took up aquatic swimming, and maintained an optimistic, socially active life.',
        'lesson' => 'Pain is an inevitable physical sensation, but misery and bitterness are mental narratives you have the power to dismantle.',
        'advice' => 'Pace yourself. Do not overexert on good days only to crash for a week. Establish a steady rhythm of gentle activity and prioritize restorative sleep.',
        'quote' => 'My joints may be stiff, but my spirit refuses to be crippled.'
    ],
    [
        'topic_id' => 8,
        'persona_id' => 5,
        'summary' => 'Embracing aging gracefully in my late seventies rather than fighting wrinkles and physical changes with anxiety.',
        'turning_point' => 'Looking at a photograph of my seventy-fifth birthday and seeing the laugh lines as an honorable map of decades of love, sorrow, and joy.',
        'outcome' => 'Enjoying deep confidence, mentoring young women, and finding freedom from vanity and societal youth obsessions.',
        'lesson' => 'Growing old is a privilege denied to millions. Wrinkles and gray hair are not defects; they are medals of endurance earned across a full life.',
        'advice' => 'Protect your mobility and leg strength above all else. Squat, walk, stay flexible, and nourish friendships that keep your mind laughing.',
        'quote' => 'Do not regret growing older; it is an honor that many of our dearest friends never received.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 6: GRIEF, BEREAVEMENT & FINDING HOPE (4 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 10, // A major turning point
        'persona_id' => 1,
        'summary' => 'Navigating the devastating loss of my partner of thirty years to sudden cardiac arrest. For the first year, waking up every morning felt like an insurmountable weight of silence and emptiness.',
        'turning_point' => 'Joining a weekly bereavement walking group where others understood that grief does not follow a neat five-stage schedule, but comes in unpredictable waves.',
        'outcome' => 'Gradually rediscovered purpose by creating a community youth scholarship fund in their memory, smiling with warm remembrance rather than crushing agony.',
        'lesson' => 'Grief never shrinks; rather, your capacity for life expands around the grief. You do not move on from great love; you move forward carrying their memory with you.',
        'advice' => 'Do not let anyone rush your mourning. Accept the tears when they arrive, eat small healthy meals, sleep when you can, and accept the help of patient friends.',
        'quote' => 'Grief is simply love with nowhere left to go; when you channel that love into helping others, light returns to the room.'
    ],
    [
        'topic_id' => 10,
        'persona_id' => 3,
        'summary' => 'Coping with the tragic death of an adult child in an automobile accident. Facing the unbearable disruption of the natural order of life.',
        'turning_point' => 'Realizing after two years of complete darkness that living as a paralyzed, bitter ghost would be a disservice to my child’s joyful memory.',
        'outcome' => 'Dedicated my energy to community road safety campaigns and mentoring vulnerable young people, honoring my child’s generous spirit every single day.',
        'lesson' => 'Even in the deepest tragedy imaginable, the human spirit retains the freedom to choose how to respond and how to honor the memory of those we cherish.',
        'advice' => 'Seek compassionate professional trauma counseling. Connect with peer support organizations like Compassionate Friends where parents share the same road.',
        'quote' => 'I cannot bring my child back, but I can make sure the love they brought into this world continues through my hands.'
    ],
    [
        'topic_id' => 10,
        'persona_id' => 2,
        'summary' => 'Dealing with the loss of a lifelong best friend who had been my confidant for forty-five years since elementary school.',
        'turning_point' => 'Compiling a scrapbook of all our letters, photographs, and inside jokes and presenting copies to their children and grandchildren.',
        'outcome' => 'Preserved precious stories for future generations and developed closer bonds with my friend’s surviving family members.',
        'lesson' => 'Deep adult friendship is one of life’s rarest and most sublime treasures. When a true friend departs, a piece of your personal history goes with them.',
        'advice' => 'Never let weeks or months slip by without calling the friends who know your history. Say "I love you" and "I appreciate you" while they are still here to hear it.',
        'quote' => 'A true friend is a second self; losing them leaves an ache that only gratitude can soothe.'
    ],
    [
        'topic_id' => 10,
        'persona_id' => 4,
        'summary' => 'Overcoming prolonged complicated bereavement after losing both elderly parents within six months of each other.',
        'turning_point' => 'Emptying the family childhood home and realizing that physical possessions were not the vessel of their wisdom; their values were stored inside my own actions.',
        'outcome' => 'Passed on heirloom recipes, gardening techniques, and family ethics to my own children, feeling our ancestors alive in our home.',
        'lesson' => 'When your parents pass away, you suddenly become the oldest generation. It is your turn to provide the steady shelter, wisdom, and traditions for the youth.',
        'advice' => 'Take audio recordings of your parents telling their stories before memory fades. Those voices will be priceless treasures when they are gone.',
        'quote' => 'Our parents never truly leave us as long as we live out the kindness and honesty they taught us at the kitchen table.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 7: FRIENDSHIP & ADULT LONELINESS (3 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 6, // Family and relationships
        'persona_id' => 4,
        'summary' => 'Overcoming acute loneliness and social isolation after relocating to a new city alone at age 47 for work. Spending weekends with zero personal contact for six months.',
        'turning_point' => 'Deciding to volunteer every Saturday morning at a community food pantry and joining a local masters rowing club where attendance was expected.',
        'outcome' => 'Formed a warm circle of six close adult friends within a year through shared weekly commitments and consistent showing up.',
        'lesson' => 'Adult friendships are not formed through casual intentions; they require proximity, repeated unplanned interactions, and shared vulnerability over time.',
        'advice' => 'Do not wait for people to invite you. Find a weekly volunteer group or hobby club, attend faithfully for three months, and be the first to suggest coffee.',
        'quote' => 'To make a friend in your forties or fifties, you must be brave enough to be the one who reaches out first.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 2,
        'summary' => 'Pruning toxic, one-sided friendships in midlife that drained emotional energy without reciprocity or genuine care.',
        'turning_point' => 'Realizing that whenever I stopped being the one who called and organized dinners, two long-time acquaintances never reached out once in an entire year.',
        'outcome' => 'Let go of superficial ties without hostility and invested all my social energy into three authentic, mutually caring friendships.',
        'lesson' => 'Quality in friendship is infinitely more nourishing than quantity. Having two true friends who show up when you are in hospital is worth fifty social acquaintances.',
        'advice' => 'Stop drinking from empty cups. Notice who celebrates your victories and who only calls when they need emotional unloading or favors.',
        'quote' => 'A smaller circle filled with loyalty and honesty is ten times better than a crowded room full of performative friends.'
    ],
    [
        'topic_id' => 6,
        'persona_id' => 5,
        'summary' => 'Building cross-generational friendships between people in their twenties and older adults in their seventies in a community arts workshop.',
        'turning_point' => 'Discovering that young people hunger for non-judgmental guidance, while older adults gain infectious energy and modern perspective from younger minds.',
        'outcome' => 'Created an ongoing intergenerational mentorship program that has operated successfully for eight years in our neighborhood.',
        'lesson' => 'Age segregation impoverishes society. When young and old share stories and create things together, loneliness dissolves for both generations.',
        'advice' => 'Seek out conversations with people outside your demographic peer group. Young people bring vitality; older people bring perspective.',
        'quote' => 'The young need elders to show them the horizon; the elders need the young to remind them of the wonder at their feet.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 8: MENTAL HEALTH, ANXIETY & HABITS (4 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 1, // A lesson I learned
        'persona_id' => 3,
        'summary' => 'Overcoming decades of chronic generalized anxiety, rumination, and catastrophizing about future disasters that never arrived.',
        'turning_point' => 'Committing to a daily practice of ten minutes of silent breath meditation and maintaining a strict morning digital detox rule.',
        'outcome' => 'Reduced panic symptoms by 80%, reclaimed mental calm, and improved focus and family presence dramatically.',
        'lesson' => 'Most suffering happens entirely in our imagination, ruminating on a past that cannot be changed or worrying about a future that will never occur.',
        'advice' => 'Do not check news or social media for the first sixty minutes after waking. Step outside, drink water, feel morning sunlight, and breathe deeply.',
        'quote' => 'I have survived thousands of catastrophic emergencies in my mind, almost none of which ever actually occurred in real life.'
    ],
    [
        'topic_id' => 1,
        'persona_id' => 1,
        'summary' => 'Recovering from severe clinical depression at age 44 through a combination of professional medical care, cognitive behavioral therapy, and daily walking.',
        'turning_point' => 'Letting go of the cultural stigma that seeking psychiatric medication and therapy was a sign of moral weakness, and asking for medical help.',
        'outcome' => 'Rebuilt emotional stability, returned to productive work, and became a vocal advocate for mental health destigmatization.',
        'lesson' => 'Depression is an illness of brain chemistry and life stress, not a personal character defect. Seeking professional help is an act of profound courage.',
        'advice' => 'If you are struggling to get out of bed or feeling hopeless, reach out to a professional immediately. Tell one trusted person the complete truth today.',
        'quote' => 'Asking for help was not surrendering; it was refusing to let the darkness win.'
    ],
    [
        'topic_id' => 2, // Something I would do differently
        'persona_id' => 4,
        'summary' => 'Overcoming chronic people-pleasing and inability to set boundaries that led to total exhaustion and resentment for twenty-five years.',
        'turning_point' => 'Learning in therapy that "No" is a complete sentence that does not require an elaborate apology or fabricated excuse.',
        'outcome' => 'Established firm boundaries at work and in extended family, earning greater genuine respect and conserving energy for what mattered most.',
        'lesson' => 'When you say "yes" to something you do not want to do out of fear of conflict, you are saying "no" to your own health and your closest relationships.',
        'advice' => 'Practice pausing before accepting requests. Say: "Let me check my calendar and get back to you tomorrow." This gives you space to evaluate truthfully.',
        'quote' => 'Setting boundaries feels like selfishness at first, but it is actually the foundation of genuine kindness.'
    ],
    [
        'topic_id' => 1,
        'persona_id' => 5,
        'summary' => 'Learning to practice radical forgiveness toward an estranged business partner who embezzled company funds fifteen years ago.',
        'turning_point' => 'Realizing that nursing hatred and replaying the betrayal every morning was like drinking poison and expecting the other person to die.',
        'outcome' => 'Released the mental grudge, which immediately relieved chronic neck tension, restored restful sleep, and allowed my creativity to flourish.',
        'lesson' => 'Forgiveness does not mean reconciliation or condoning injustice; it means releasing your claim to vengeance so your own soul can be free.',
        'advice' => 'Forgive for your own sake, not theirs. Write a letter detailing all the anger, and then burn it safely as a symbolic release of the past.',
        'quote' => 'Holding onto anger is like clutching hot coal with the intent of throwing it at someone else; you are the one who gets burned.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 9: RETIREMENT, PURPOSE & IDENTITY (4 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 7, // Money and retirement
        'persona_id' => 3,
        'summary' => 'Overcoming severe identity loss and depression during the first year of retirement after forty years as a high school principal.',
        'turning_point' => 'Realizing that retiring FROM a job without retiring TO a mission creates a dangerous vacuum of purpose and daily structure.',
        'outcome' => 'Began volunteering twenty hours a week teaching adult literacy at the local library, restoring a profound sense of contribution and daily routine.',
        'lesson' => 'Retirement is not an endless vacation; humans need challenge, community responsibility, and a compelling reason to get out of bed each morning.',
        'advice' => 'Plan your daily activities and social circles for retirement with the same diligence you put into your financial retirement savings.',
        'quote' => 'You do not retire to sit on a porch and watch the world go by; you retire so you can spend your best wisdom serving where it is needed most.'
    ],
    [
        'topic_id' => 11, // Something I am proud of
        'persona_id' => 1,
        'summary' => 'Learning to paint with watercolors and exhibiting in a local gallery at age 67, having never picked up a paintbrush in my entire working life.',
        'turning_point' => 'Enrolling in a beginner art class despite feeling intimidated by being the oldest person in the room by thirty years.',
        'outcome' => 'Developed a distinctive artistic voice, sold dozens of paintings, and discovered an entirely new community of creative friends.',
        'lesson' => 'You are never too old to be a beginner. Curiosity and creative expression have no expiration date if you are willing to let go of looking foolish.',
        'advice' => 'Take that music lesson, enroll in that creative writing course, or pick up that brush today. Stop telling yourself that your time has passed.',
        'quote' => 'The beginner’s mind at sixty-seven is the sweetest freedom on earth.'
    ],
    [
        'topic_id' => 7,
        'persona_id' => 2,
        'summary' => 'Downsizing from a four-bedroom suburban house to a small garden cottage in retirement. Confronting forty years of accumulated possessions and clutter.',
        'turning_point' => 'Realizing that our children did not want our old china sets or heavy furniture, and choosing to donate items to families starting anew.',
        'outcome' => 'Freed ourselves from weekend maintenance, yard chores, and excessive utility bills, feeling extraordinarily light and unburdened.',
        'lesson' => 'Possessions end up possessing you. Every object you own demands cleaning, insuring, and organizing; shedding clutter creates space for living.',
        'advice' => 'Begin decluttering ten years before you plan to move. Give heirlooms to loved ones while you are alive so you can enjoy their delight.',
        'quote' => 'The less stuff we owned, the more room we had in our lives for laughter and adventures.'
    ],
    [
        'topic_id' => 3, // Advice for younger people
        'persona_id' => 5,
        'summary' => 'Wisdom gained at eighty looking back across eight decades of triumphs, losses, historical changes, and personal growth.',
        'turning_point' => 'Attending my sixtieth high school reunion and realizing that none of the things people worried about in their youth mattered in the end.',
        'outcome' => 'Living with immense daily gratitude, contentment, and a desire to encourage every young person I encounter.',
        'lesson' => 'Life is extraordinarily brief. In the final accounting, you will never wish you had spent more time at the office; you will only wish you had loved more boldly.',
        'advice' => 'Forgive quickly. Hug your loved ones tightly. Laugh at your mistakes. Take the trip. Do not take yourself too seriously.',
        'quote' => 'Life is a brief, breathtaking gift; do not spend it standing in the waiting room of fear.'
    ],

    // -----------------------------------------------------------------
    // DOMAIN 10: ENTREPRENEURSHIP & STARTING OVER (4 stories)
    // -----------------------------------------------------------------
    [
        'topic_id' => 5, // Career and work
        'persona_id' => 2,
        'summary' => 'Starting my first bakery business at age 47 with zero prior culinary business experience, investing savings and taking on substantial operational risk.',
        'turning_point' => 'Nearly going bankrupt in month fourteen due to underpricing products, forcing me to hire a seasoned restaurant financial consultant.',
        'outcome' => 'Pivoted to artisanal sourdough and catering, reaching profitability by year three and expanding to a second location.',
        'lesson' => 'Passion is the spark that starts an enterprise, but rigorous financial discipline and cash flow management are the fuel that keeps it alive.',
        'advice' => 'Know your unit economics down to the penny before you open doors. Never confuse busy customer traffic with actual net profitability.',
        'quote' => 'Loving to bake bread is wonderful, but running a profitable bakery is about inventory, margins, and waking up at four in the morning.'
    ],
    [
        'topic_id' => 10, // A major turning point
        'persona_id' => 3,
        'summary' => 'Recovering from complete alcoholism and achieving twenty years of continuous sobriety through a twelve-step fellowship after losing my driver license and career.',
        'turning_point' => 'Admitting total powerlessness in a treatment center at 38, realizing that my intellect could not think its way out of an addiction.',
        'outcome' => 'Rebuilt family trust, regained professional credentials, and sponsored dozens of people embarking on their own journey of recovery.',
        'lesson' => 'Surrender is the doorway to freedom. You cannot heal until you stop pretending you have control and accept the support of a recovery community.',
        'advice' => 'Take life strictly one day at a time, or one hour at a time if necessary. Get connected with a support group and be ruthlessly honest.',
        'quote' => 'Sobriety gave me back everything alcohol promised me but stole.'
    ],
    [
        'topic_id' => 9, // Technology and how life changed
        'persona_id' => 4,
        'summary' => 'Adapting to artificial intelligence, digital banking, and smartphones in my late seventies after growing up with rotary phones and manual typewriters.',
        'turning_point' => 'Refusing to say "I am too old for this technology" and instead taking a weekly community college digital literacy course.',
        'outcome' => 'Now video calling grandchildren in Australia every Sunday, managing investments online, and staying connected with global events.',
        'lesson' => 'Technology is just an instrument. Do not let fear of unfamiliar buttons prevent you from accessing connection and convenience.',
        'advice' => 'Approach modern devices with the curiosity of a child. You cannot break the internet by tapping a screen; ask for patient instruction without shame.',
        'quote' => 'Curiosity is the antidote to obsolescence.'
    ],
    [
        'topic_id' => 4, // A funny life experience
        'persona_id' => 4,
        'summary' => 'The catastrophic disaster of trying to DIY-replace our upstairs bathroom plumbing before hosting twenty-five people for Thanksgiving dinner.',
        'turning_point' => 'Watching water pour through the kitchen ceiling onto the roasted turkey while my brother-in-law tried to turn off the main water shutoff valve with pliers.',
        'outcome' => 'We ordered Chinese takeout for Thanksgiving dinner, sat on camping chairs in the garage, and laughed until our ribs ached.',
        'lesson' => 'The best family memories are almost never the flawlessly executed formal holidays, but the hilarious disasters where everyone pulls together.',
        'advice' => 'Know when to call a licensed professional plumber. And when disaster strikes at a family gathering, order takeout and start laughing.',
        'quote' => 'Nobody remembers the perfect Thanksgiving dinner, but thirty years later we still laugh until we cry about the turkey in the shower.'
    ]
];

echo "=======================================================\n";
echo "   LifeGPT - Seeding 45 Rich Wisdom Life Stories\n";
echo "=======================================================\n\n";

$db = DB::getConnection();
$db->beginTransaction();

$insertedInterviews = 0;
$insertedChunks = 0;

try {
    foreach ($stories as $idx => $s) {
        $uuid = generateUuid();
        
        // 1. Insert Interview
        $stmtInt = $db->prepare(
            "INSERT INTO lg_interviews (uuid, user_id, persona_id, topic_id, duration_type, language, status, created_at)
             VALUES (:uuid, NULL, :persona_id, :topic_id, 'standard', 'en', 'completed', NOW())"
        );
        $stmtInt->execute([
            'uuid' => $uuid,
            'persona_id' => $s['persona_id'],
            'topic_id' => $s['topic_id']
        ]);
        $interviewId = (int)$db->lastInsertId();

        // 2. Insert Consent (100% RAG consented, anonymous attribution)
        $stmtCons = $db->prepare(
            "INSERT INTO lg_consents (interview_id, storage_consent, rag_consent, quotes_consent, research_consent, publication_consent, attribution_type, withdrawn, version)
             VALUES (:interview_id, 1, 1, 1, 1, 1, 'anonymous', 0, 1)"
        );
        $stmtCons->execute(['interview_id' => $interviewId]);

        // 3. Insert Summary
        $stmtSum = $db->prepare(
            "INSERT INTO lg_interview_summaries (interview_id, story_summary, main_lesson, turning_point, outcome, advice, representative_quote, approved_summary, approved_quote)
             VALUES (:interview_id, :summary, :lesson, :turning_point, :outcome, :advice, :quote, 1, 1)"
        );
        $stmtSum->execute([
            'interview_id' => $interviewId,
            'summary' => $s['summary'],
            'lesson' => $s['lesson'],
            'turning_point' => $s['turning_point'],
            'outcome' => $s['outcome'],
            'advice' => $s['advice'],
            'quote' => $s['quote']
        ]);

        // 4. Insert 5 high-signal chunks
        $chunks = [
            ['content_type' => 'summary',       'text' => $s['summary']],
            ['content_type' => 'lesson',        'text' => $s['lesson']],
            ['content_type' => 'turning_point', 'text' => $s['turning_point']],
            ['content_type' => 'advice',        'text' => $s['advice']],
            ['content_type' => 'quote',         'text' => $s['quote']],
        ];

        foreach ($chunks as $c) {
            // Generate 256-dim embedding vector directly
            $vector = EmbeddingService::embedText($c['text']);
            $vectorJson = json_encode($vector);

            $stmtChunk = $db->prepare(
                "INSERT INTO lg_knowledge_chunks 
                 (interview_id, content_type, text, anonymized_text, approved_for_rag, approved_for_publication, status, embedding, embedding_model, embedded_at)
                 VALUES (:interview_id, :content_type, :text, :anon_text, 1, 1, 'approved', :embedding, 'local-semantic-tfidf-v1', NOW())"
            );
            $stmtChunk->execute([
                'interview_id' => $interviewId,
                'content_type' => $c['content_type'],
                'text'         => $c['text'],
                'anon_text'    => $c['text'],
                'embedding'    => $vectorJson,
            ]);
            $insertedChunks++;
        }

        $insertedInterviews++;
        echo " [Story " . str_pad($insertedInterviews, 2, ' ', STR_PAD_LEFT) . "/45] Added: " . mb_substr($s['summary'], 0, 50) . "...\n";
    }

    $db->commit();
    echo "\nSUCCESS: Successfully committed {$insertedInterviews} new interviews and {$insertedChunks} embedded knowledge chunks!\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "\nERROR during seeding: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
