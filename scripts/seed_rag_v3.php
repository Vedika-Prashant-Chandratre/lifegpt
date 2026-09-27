<?php
/**
 * LifeGPT - Rich RAG Knowledge Seed v3
 * 
 * Covers topics NOT YET in the archive:
 *  - Self-improvement / personal growth habits
 *  - Purpose & meaning (midlife, retirement)
 *  - Grief, loss, bereavement
 *  - Addiction recovery & second chances
 *  - Immigration & cultural identity
 *  - Parenting adult children (letting go)
 *  - Friendship after 50
 *  - Creativity, art, late-life passions
 *  - Forgiveness (self and others)
 *  - Military / service & civilian re-entry
 *  - Marriage longevity secrets
 *  - Caregiving for aging parents
 *
 * 45 stories × 3 chunks = 135 new chunks
 * Run: php scripts/seed_rag_v3.php
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

function makeUUID(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,
        mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
    );
}

$stories = [

    // ============================================================
    // GRIEF, LOSS & BEREAVEMENT
    // ============================================================
    [
        'topic_id' => 10, 'persona_id' => 3,
        'summary'  => 'My husband of 34 years died suddenly from a heart attack. One morning he was there and by evening he was gone. The grief was nothing I had any preparation for — it was physical, not just emotional. I lost the ability to taste food for three months. What helped me most was not people telling me it would get better but people who simply sat with me without needing me to perform recovery. Grief, I learned, is not a problem to be solved but a presence to be survived.',
        'advice'   => 'When someone is grieving, resist the urge to fix or rush them toward healing. Presence without agenda — just sitting, listening, being available — is far more valuable than well-intentioned advice. And if you are the one grieving, give yourself explicit permission to take as long as you take.',
        'quote'    => 'I did not need people to tell me it would be okay. I needed people to sit with me while it wasn\'t.',
    ],
    [
        'topic_id' => 10, 'persona_id' => 2,
        'summary'  => 'I lost my son to an overdose when he was 31. No grief compares to outliving your child. For two years I was barely functional. The turning point came when I joined a support group of parents who had experienced the same loss. Being with people who truly understood, who did not flinch at the details, who had survived what I was surviving — that community became my lifeline. I later became a peer support facilitator myself.',
        'advice'   => 'In catastrophic grief, connection with others who have shared the specific experience matters more than general therapy or sympathy. Seek out communities of people who know exactly what you are going through. Their survival is evidence that yours is possible.',
        'quote'    => 'Other parents who had survived the same loss showed me survival was possible. That was enough to keep going.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 1,
        'summary'  => 'I cared for my mother through five years of Alzheimer\'s. The grief began long before she died — it was the grief of losing her piece by piece while she was still physically present. What I did not expect was that her death, when it came, brought not only sorrow but also relief, and then tremendous guilt about the relief. A grief counselor helped me understand that relief after long caregiving is not a failure of love — it is a human response to exhaustion.',
        'advice'   => 'Grief after a long illness often begins during the illness itself. Allow that anticipatory grief to be real and valid. And if you feel relief when it ends, do not let guilt colonize that relief. Love and exhaustion are not opposites, and relief is not the measure of how much you loved.',
        'quote'    => 'I grieved my mother for five years before I lost her. The death was not the beginning of the grief — it was its final chapter.',
    ],

    // ============================================================
    // ADDICTION RECOVERY & SECOND CHANCES
    // ============================================================
    [
        'topic_id' => 10, 'persona_id' => 3,
        'summary'  => 'I was addicted to alcohol for twelve years, from my late 30s into my 50s. I tried to stop five times before it stuck. What finally worked was not willpower — it was community, accountability, and radical honesty with myself about the triggers I had been refusing to face. I have been sober for nine years. The shame I carried for those wasted years eventually became the empathy I use to help others. Nothing is fully lost.',
        'advice'   => 'Recovery from addiction rarely happens through willpower alone. It requires community, honest self-examination, and willingness to address the underlying pain driving the behavior. Shame about the past, however understandable, is often a relapse trigger rather than a motivator — learn to separate accountability from self-punishment.',
        'quote'    => 'Five failed attempts were not failures. They were the road to the one that worked.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'I spent eight years in prison for a crime I committed when I was 23. Coming out at 31 with a record, no savings, and burned relationships was the hardest starting point imaginable. But the person I became in those eight years — through reading, therapy, confronting what I had done and who I had been — was more solid than the person who went in. Second chances are not given; they are built, brick by brick, through thousands of unglamorous daily choices.',
        'advice'   => 'Rebuilding after serious failure or incarceration is a long game measured in years, not months. Focus on the next right action rather than the full gap between where you are and where you want to be. Character rebuilt slowly is character that holds.',
        'quote'    => 'I came out with nothing. I left behind the version of me that had landed me there.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'I recovered from a gambling addiction that destroyed my first marriage and nearly destroyed my finances. The breakthrough was accepting that I was not weak — I had a condition that required specific treatment, not just willpower. Once I stopped treating it as a character flaw and started treating it as a health issue, I could accept help without the crushing shame that had previously kept me silent. Ten years later, my relationship with money and myself is the best it has ever been.',
        'advice'   => 'Addiction is a health issue, not a moral failure. Treating it with the same clinical seriousness as any other health condition — professional help, structured treatment, ongoing support — removes the shame barrier that prevents people from getting what they need.',
        'quote'    => 'The day I stopped seeing it as weakness and started seeing it as illness was the day recovery became possible.',
    ],

    // ============================================================
    // IMMIGRATION & CULTURAL IDENTITY
    // ============================================================
    [
        'topic_id' => 10, 'persona_id' => 2,
        'summary'  => 'I immigrated from Nigeria to the UK at 47 with my wife and two teenagers. Every credential I had earned over 20 years was unrecognized. I started as a hospital porter while my wife cleaned offices — two professionals reduced to the most basic work in a new country. It took us five years to rebuild our professional standing. What kept us going was a clear shared vision of what we were building for our children, and a community of others who had made the same journey.',
        'advice'   => 'When immigrating as an established professional, prepare yourself for a temporary reduction in status that has nothing to do with your actual capability. This is a cost with a return on investment, not a verdict on your worth. Community with fellow immigrants who have navigated the same transition is irreplaceable.',
        'quote'    => 'We were not starting over. We were investing in a different future. The distinction mattered.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 1,
        'summary'  => 'Growing up between two cultures — Indian at home, Australian at school — I spent my 20s and 30s feeling like I did not fully belong in either world. The identity crisis was real and painful. In my 40s, I stopped seeing it as a problem and started seeing it as a superpower. People who inhabit two cultures simultaneously have a cognitive flexibility and empathy that those from a single cultural world often lack. I was not between two cultures — I was the bridge.',
        'advice'   => 'Cultural identity complexity, while often experienced as painful in youth, frequently becomes a genuine advantage in adulthood. The ability to read different social contexts, to navigate across cultural boundaries, and to empathize broadly is a skill increasingly valued in a global world.',
        'quote'    => 'I spent years trying to choose between my cultures. The breakthrough was realizing I was the intersection.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 3,
        'summary'  => 'I regret not learning the language of my adopted country properly for the first ten years. I got by with basic communication and relied on my children to translate in important situations. This dependency was humiliating and put my children in an inappropriate role. When I finally committed to proper language learning at 52, my entire experience of the country changed. Language is not just communication — it is dignity, autonomy, and belonging.',
        'advice'   => 'If you are in a country where you do not fully speak the language, prioritize language learning above almost everything else. It is not just about communication — it is about autonomy, dignity, and genuine integration. Every year you delay costs more than you realize.',
        'quote'    => 'Learning the language was not about speaking. It was about becoming a full person in my new country.',
    ],

    // ============================================================
    // PARENTING ADULT CHILDREN
    // ============================================================
    [
        'topic_id' => 6, 'persona_id' => 3,
        'summary'  => 'The hardest parenting transition I ever faced was letting go of my adult children\'s choices when those choices were not what I would have made. My daughter chose a partner I did not approve of. My son left a stable career to start a business I thought was reckless. My instinct was to intervene, advise, and warn. What I actually needed to do was express my concern once, clearly, and then stand back. They both found their way. The relationship I preserved by respecting their autonomy is what matters now.',
        'advice'   => 'When adult children make choices you disagree with, you may state your concern once — clearly, kindly, and without repetition. Then step back. Repeated criticism and unsolicited advice costs you the relationship without changing their decision. Your job shifts from guidance to trust.',
        'quote'    => 'I said what I needed to say once. Then I chose the relationship over being right.',
    ],
    [
        'topic_id' => 6, 'persona_id' => 2,
        'summary'  => 'My son went through a divorce with two young grandchildren involved. I desperately wanted to protect everyone and ended up making it worse — taking sides, offering opinions, inserting myself into negotiations that were not mine to manage. It fractured my relationship with my son and nearly cost me access to my grandchildren. I learned that in adult children\'s crises, your role is support and availability, not management. Ask what they need rather than assuming you know.',
        'advice'   => 'When adult children go through crises — divorce, job loss, health issues — resist the urge to manage the situation. Ask them explicitly: "What do you need from me right now?" Sometimes the answer is practical help. Sometimes it is just a witness. Let them define what support looks like rather than imposing your version of it.',
        'quote'    => 'I was so busy managing his crisis I forgot to ask him what he actually needed.',
    ],

    // ============================================================
    // FRIENDSHIP AFTER 50
    // ============================================================
    [
        'topic_id' => 6, 'persona_id' => 1,
        'summary'  => 'I had deep friendships in my 30s and 40s that gradually evaporated through moves, divorces, and the busyness of midlife. At 58, I found myself genuinely lonely with a wide social acquaintance but no real friends. Building friendships in your 50s requires the same intentionality that building a career does — you have to actively invest, show up, reach out first, and be willing to be vulnerable with people who are also guarded. I built three genuine friendships in my 60s that are the most honest relationships of my adult life.',
        'advice'   => 'Deep adult friendships do not happen passively after 50. They require active, sustained investment — regular contact, vulnerability, showing up in difficult times, reaching out even when you fear rejection. The reward is friendship that is more conscious and more sustaining than the accidental friendships of youth.',
        'quote'    => 'The friends I made deliberately in my 60s know me better than most people I\'ve known for decades.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 1,
        'summary'  => 'My advice on friendship is simple: do not wait for people to reach out to you. In your 40s and beyond, everyone is busy, everyone is tired, and the people who most need connection are often the least likely to ask for it. I started a habit of reaching out to one person every week — just a message or a call — and it transformed my social life within a year. Proactive connection is a skill that changes everything in midlife and beyond.',
        'advice'   => 'Make reaching out a regular practice, not an impulse. Schedule it if necessary. The people in your network who you genuinely care about need you to initiate contact sometimes. The return on that small investment of attention is disproportionately high.',
        'quote'    => 'I stopped waiting to be reached and started reaching. My whole social world changed.',
    ],

    // ============================================================
    // CREATIVITY, ART & LATE-LIFE PASSIONS
    // ============================================================
    [
        'topic_id' => 11, 'persona_id' => 2,
        'summary'  => 'I picked up painting at 61, thirty years after the art teacher in high school told me I had no talent. My first year of paintings were objectively bad. My third year they were good enough to exhibit at a local gallery. My fifth year I sold a piece. I was not discovering a hidden gift — I was developing a skill through sustained practice that I should never have abandoned. The teacher who told me I had no talent was wrong, but I let that verdict stand for forty years.',
        'advice'   => 'Do not let an old verdict about your creative ability determine the rest of your life. Creative capacity develops through practice, not through natural talent alone. If you want to paint, write, sing, or build — start now, accept that you will be bad at first, and keep going. The development curve is real regardless of when you begin.',
        'quote'    => 'I wasted forty years believing someone who was wrong about me. Do not give that kind of power away.',
    ],
    [
        'topic_id' => 11, 'persona_id' => 3,
        'summary'  => 'Writing became my passion at 67, after I retired from 35 years in accounting. I had never written creatively before. I joined a local writing group, started a blog, and eventually self-published a memoir about my immigration journey. It was not a bestseller but it was mine. The process of writing my story taught me things about my own life that four decades of living had not. Creativity in late life is not a decoration — it is a form of self-knowledge.',
        'advice'   => 'Creative work in later life serves a different purpose than it does in youth — it is less about ambition and more about meaning, self-knowledge, and legacy. Start without any goal of recognition. Write, paint, build, compose for the process itself first. Recognition, if it comes, is a pleasant bonus.',
        'quote'    => 'Writing my story taught me things about my life that living it had not.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 2,
        'summary'  => 'The best advice I can give about pursuing a creative passion later in life is to detach your identity from the outcome. I wrote a novel that was rejected by every publisher I sent it to. That rejection used to feel like rejection of me. Now I see it as information about the market, not a verdict on the value of what I created. The act of writing the novel was worth doing regardless of publication. Separate the making from the selling.',
        'advice'   => 'Pursue creative work for intrinsic reasons and measure success by your own growth, expression, and satisfaction. External validation is unreliable and slow. If you attach your self-worth to publication, exhibition, or recognition, you will abandon the work at exactly the moment persistence would have paid off.',
        'quote'    => 'My novel was rejected by everyone. It was also the most important thing I ever made.',
    ],

    // ============================================================
    // FORGIVENESS — SELF AND OTHERS
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'I carried resentment toward my father for thirty years. He had been absent, cold, and critical throughout my childhood. At 55, facing my own health crisis, I decided to understand rather than judge him. I learned about his own traumatic childhood and the generation of men who were never taught how to express love. Understanding did not excuse what happened, but it dissolved the resentment. Forgiveness, I discovered, was not something I did for him — it was something I did for myself.',
        'advice'   => 'Forgiveness is a self-interested act, not a moral gift to the person who hurt you. Carrying resentment costs you energy every day. Working toward understanding — not excusing, not forgetting — releases a burden you have been carrying. You do not have to reconcile with someone to forgive them.',
        'quote'    => 'I forgave my father not because he deserved it but because I deserved to put down what I had been carrying.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'The hardest forgiveness work I ever did was forgiving myself. For years after my business failed and I hurt people who had trusted me, I lived in self-punishment. I stayed small, refused success, and kept declining opportunities because I believed I had forfeited my right to them. A therapist helped me see that self-punishment is not penance — it is self-absorption. The people I hurt needed me to do better, not to suffer more.',
        'advice'   => 'Self-forgiveness is not a lowering of standards — it is a prerequisite for actually changing. Continuous self-punishment keeps you focused on the past failure rather than building the better future. Accept accountability, make whatever amends are possible, and then commit to moving forward. That is how you actually do better.',
        'quote'    => 'Self-punishment was not penance. It was just a different way of staying stuck.',
    ],

    // ============================================================
    // PURPOSE & MEANING IN MIDLIFE
    // ============================================================
    [
        'topic_id' => 10, 'persona_id' => 3,
        'summary'  => 'At 49 I had everything that was supposed to bring satisfaction — good income, house, family, health — and felt profoundly empty. The midlife crisis was real, though it looked nothing like a sports car purchase. It was a quiet, persistent question: is this all there is? The answer I found over three years of therapy, reading, and genuine soul-searching was that I had been living everyone else\'s definition of a good life. Building a life around my own values rather than inherited ones transformed everything.',
        'advice'   => 'Midlife restlessness is not ingratitude for what you have — it is often a signal that you have been living by other people\'s definitions of success. Use the discomfort as a prompt for honest self-examination: what do I actually value? What kind of person do I want to be in the second half of my life? These questions are worth the disruption they cause.',
        'quote'    => 'I had everything I was supposed to want and wanted none of it. That gap was the most important information I had ever received.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 3,
        'summary'  => 'Purpose is not found — it is built, through action and reflection over time. I spent years waiting for my purpose to reveal itself. My therapist finally challenged me: stop waiting to feel called and start doing things that feel meaningful, then see what persists. Through that process of trying and reflecting, I discovered that teaching and mentorship was where I felt most alive. Purpose emerged from action, not from introspection alone.',
        'advice'   => 'Do not wait to discover your purpose before you take action. Experiment with activities that feel meaningful, volunteer in areas that matter to you, help people with things you are good at — and then pay close attention to what energizes you versus what drains you. Purpose is revealed through doing, not through thinking.',
        'quote'    => 'Purpose was not waiting for me to find it. It was hiding inside the things I was already doing.',
    ],
    [
        'topic_id' => 7, 'persona_id' => 1,
        'summary'  => 'I retired at 62 and found the first year almost unbearable. I had been an engineer for 38 years and my entire sense of self was wrapped up in that identity. Without the role, I did not know who I was. The second year I began volunteering with a youth mentorship program and the sense of meaning that returned was more genuine than anything my paid career had given me. Purpose after retirement often requires actively building a new identity, not just relaxing into freedom.',
        'advice'   => 'Plan your identity transition before you retire, not after. Identify what activities will give you a sense of contribution, challenge, and connection in your post-work life. Retirement is a beginning that requires the same intentional planning as any other major life stage — it does not manage itself.',
        'quote'    => 'Retirement gave me freedom and took away my sense of self at the same moment. Building the second took longer than I expected.',
    ],

    // ============================================================
    // MARRIAGE LONGEVITY & RELATIONSHIP MAINTENANCE
    // ============================================================
    [
        'topic_id' => 6, 'persona_id' => 1,
        'summary'  => 'My wife and I have been married for 52 years. People always ask our secret and expect something romantic. The honest answer is daily choice. There were years that were joyful and years that were grinding and several where I am not sure we liked each other very much. What kept us together was not love as a feeling — it was love as a commitment that manifested in small daily acts: showing up, listening, apologizing, trying again. The feeling followed the behavior, not the other way around.',
        'advice'   => 'Long marriages are not sustained by passion alone — they are sustained by consistent acts of care and repair over decades. Develop the habit of repairing quickly when things go wrong: apologize, acknowledge, reach back out. The gap between rupture and repair is what determines whether a relationship deepens or erodes.',
        'quote'    => 'We were not always in love as a feeling. We were always choosing it as a behavior. The feeling kept coming back because of the behavior.',
    ],
    [
        'topic_id' => 6, 'persona_id' => 3,
        'summary'  => 'After 25 years of marriage we hit a wall so solid I was certain we were done. Our children had left home, we had no shared project, and we had become polite strangers. Couples therapy saved us — not because the therapist solved anything, but because having a structured, witnessed space for honest conversation broke patterns of avoidance we had built over years. We have now been married 38 years and the last 13 have been the best.',
        'advice'   => 'Do not wait for a crisis to seek couples therapy. Go when things are flat, drifting, or becoming parallel lives. The investment in structured relationship maintenance is far less costly — financially and emotionally — than the alternative. A skilled couples therapist does not fix your relationship; they create the conditions for you to fix it yourselves.',
        'quote'    => 'We went to therapy as strangers. We came out remembering why we had chosen each other.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 2,
        'summary'  => 'I neglected my marriage for a decade while building my career. My wife was patient and then she was not. We came very close to divorce at year 22. I thought I was providing for the family. I was absent from it. The hard lesson: a partner who feels chronically neglected will eventually stop waiting, and no career achievement compensates for that loss. I had to change the actual distribution of my time and attention, not just my intentions.',
        'advice'   => 'Good intentions toward your partner are not visible — only behavior is. Prioritize time and presence in your relationship with the same rigor you prioritize professional commitments. Schedule it if you have to. Your partner experiences what you actually do, not what you mean to do.',
        'quote'    => 'I thought providing was enough. It was not. I was providing for a family I was not actually in.',
    ],

    // ============================================================
    // CAREGIVING FOR AGING PARENTS
    // ============================================================
    [
        'topic_id' => 6, 'persona_id' => 3,
        'summary'  => 'I was my mother\'s primary caregiver for six years while working full-time and raising two teenagers. The physical and emotional exhaustion was unlike anything else I have experienced. The biggest mistake I made was refusing help for too long, believing I had to manage everything myself. When I finally accepted help from a care coordinator, home health aides, and a support group, the situation became survivable. Asking for help is not failure — it is the only way caregiving is sustainable.',
        'advice'   => 'If you are a caregiver, build your support network aggressively and early. Accept every offer of help. Hire professional care where possible. Join a caregiver support group. Your ability to care for someone else depends entirely on your own sustainability, and that requires community, rest, and distributed responsibility.',
        'quote'    => 'I tried to do it alone for three years. When I let people in, we all survived.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'Caring for my father in his final two years changed my relationship with aging and death entirely. I had avoided thinking about both my whole life. Up close with his decline, his occasional terror and occasional peace, I was forced to confront my own mortality in ways I could no longer postpone. Unexpectedly, that confrontation made me live more deliberately. Witnessing a death well-attended — with presence, love, and honesty — was the greatest gift my father gave me.',
        'advice'   => 'If you are caring for an aging parent, allow the experience to teach you about mortality rather than numbing yourself to it. The reflection it prompts on your own finite life is uncomfortable but clarifying. Many people report that proximity to death radically improved their relationship with how they were spending their own life.',
        'quote'    => 'Being with my father as he died was the most frightening and the most clarifying experience of my life.',
    ],

    // ============================================================
    // MILITARY SERVICE & CIVILIAN REENTRY
    // ============================================================
    [
        'topic_id' => 10, 'persona_id' => 2,
        'summary'  => 'I served in the military for twenty-two years and struggled enormously with the transition to civilian life at 44. The structure, identity, purpose, and camaraderie of military life had no civilian equivalent. I felt irrelevant, purposeless, and disconnected for three years. Recovery came through veterans\' peer networks, a therapist who had served, and eventually finding a civilian role — corporate training — that used the leadership skills I had developed. The skills transferred; I simply had to learn a new language to describe them.',
        'advice'   => 'The civilian transition after military service is one of the most underestimated identity transitions a person can face. Give yourself at least two years to find your footing. Seek peer support from veterans who have made the transition successfully. And invest time in translating your military competencies into civilian language — the skills are real and valuable, even when unrecognized by those who have never served.',
        'quote'    => 'I was not starting over. I was translating twenty-two years of experience into a language civilians could hear.',
    ],

    // ============================================================
    // SELF-IMPROVEMENT HABITS THAT ACTUALLY WORK
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'I have tried more self-improvement approaches than I can count. The only things that have actually produced lasting change in my life are: a consistent morning routine that protects my best hours, weekly review of whether I am moving toward my stated values, and a small daily habit tied to the identity I am building rather than the outcome I want. Systems designed around who you want to be are more powerful than goals designed around what you want to achieve.',
        'advice'   => 'Design habits around identity, not outcomes. Instead of "I want to lose 20 pounds," try "I am someone who moves their body every day." Instead of "I want to read more," try "I am a reader." Identity-based habits survive bad days and missed goals in ways that outcome-based habits do not.',
        'quote'    => 'Every sustainable change in my life started with who I decided to be, not what I decided to do.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 3,
        'summary'  => 'The single habit that has produced more positive change in my life than anything else is journaling — not the dear diary kind, but a structured 15-minute daily reflection: what went well, what did I struggle with, what would I do differently. Over 20 years that practice has accumulated into an extraordinary record of self-knowledge. I can trace patterns, notice recurring mistakes, and see growth that is invisible day to day. It is the cheapest, most effective self-improvement tool I know.',
        'advice'   => 'If you commit to only one self-improvement practice, make it regular structured reflection. Write down what worked, what did not, and what you are learning. The discipline of articulating your experience in writing forces clarity that thinking alone rarely produces, and the accumulation of those reflections over years becomes a genuine map of your own development.',
        'quote'    => 'Twenty years of daily journaling has taught me more about myself than any book ever written about me could.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'I tried to change too many things at once for most of my life and succeeded at almost none of them. The principle that finally worked was the one-thing rule: identify the single change that would have the biggest positive ripple effect on everything else, and work on only that until it is established. For me it was sleep — fixing my sleep habits improved my mood, focus, exercise consistency, and relationships more than any other single change. One keystone habit unlocked the rest.',
        'advice'   => 'When you want to improve multiple areas of your life simultaneously, identify which single change would create the most positive ripple effects across the others. Then work on only that one until it is solid. Keystone habits — those that make other habits easier — are worth finding and prioritizing before anything else.',
        'quote'    => 'I fixed my sleep and didn\'t change anything else. Almost everything else got better anyway.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 1,
        'summary'  => 'The most important self-improvement insight I have is this: environment beats willpower every time. I tried for years to change through discipline alone and kept failing. When I redesigned my environment — cleared bad food from the house, put my workout gear where I would see it, moved my phone to another room at night, joined a running group — my behavior changed without constant conscious effort. Willpower is a limited resource; environment is a renewable architecture.',
        'advice'   => 'Design your environment to make good behaviors easier and bad behaviors harder, rather than relying on willpower to overcome friction. Remove temptations, add helpful friction to bad habits, create cues for good ones, and find social environments that reinforce the behaviors you want. Environment does most of the heavy lifting willpower cannot sustain.',
        'quote'    => 'Willpower kept running out. Environment just kept working.',
    ],

    // ============================================================
    // DIFFICULT CONVERSATIONS & RELATIONSHIPS
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'I avoided difficult conversations my entire career and personal life. I would let resentment build, relationships erode, and injustices pass rather than face the discomfort of honest dialogue. The cost was enormous — a partnership I should have restructured that I instead watched collapse, friendships I let die rather than have a hard conversation. What I learned at 58 is that most difficult conversations are far less catastrophic in reality than in anticipation, and the cost of avoidance is always greater than the cost of honest engagement.',
        'advice'   => 'The conversation you are avoiding is almost certainly less damaging to the relationship than the silence. Most difficult conversations, approached with honesty and genuine care for the other person, strengthen relationships rather than ending them. Practice having them early and often, before avoidance becomes your default.',
        'quote'    => 'The conversations I dreaded most rarely went as badly as I feared. The ones I avoided always ended worse than they needed to.',
    ],
    [
        'topic_id' => 6, 'persona_id' => 2,
        'summary'  => 'My sibling relationship was broken for eleven years after a dispute over our parents\' estate. The specifics of who was technically right mattered far less, in retrospect, than the relationship I lost during those years. When we reconciled at our mother\'s funeral it felt both too late and necessary. What I wish I had known: family rifts that are not healed tend to calcify and become permanent. The longer you wait to reach out, the harder the reaching becomes.',
        'advice'   => 'If you are estranged from a family member and there is any part of you that still cares about the relationship, make the first move. Do not wait for them to reach out. Do not wait for an apology you feel you deserve. Time calcifies rifts. The cost of reaching out first is small compared to the cost of permanent estrangement.',
        'quote'    => 'I waited eleven years for them to reach out first. We both wasted eleven years.',
    ],

    // ============================================================
    // MANAGING FEAR & COURAGE
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'Fear has been present in every important decision of my life. Starting a business: terrifying. Leaving a bad marriage: terrifying. Moving countries: terrifying. Changing careers at 50: terrifying. What I have learned is that courage is not the absence of fear — it is the decision that something matters more than the fear. Every meaningful thing I have ever done was done while afraid. The fear did not predict the outcome; the action did.',
        'advice'   => 'Redefine courage as moving forward despite fear, not moving forward without it. If you are waiting to stop feeling afraid before you act, you will wait indefinitely. Instead, identify whether the fear is protecting you from genuine danger or simply from discomfort and the unfamiliar. Most of the time it is the latter.',
        'quote'    => 'I have never done anything brave without being afraid first. Courage was never the absence of fear.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 1,
        'summary'  => 'The decisions I most regret are the ones I did not make because I was afraid — the business I did not start, the conversation I did not have, the person I did not tell how I felt. The things I was afraid of rarely happened. The opportunities lost to fear were real and permanent. Inaction feels safe but has its own costs, and those costs are not always visible until much later.',
        'advice'   => 'Account for the cost of inaction when you are making decisions out of fear. It feels like a neutral choice but it is not — it closes doors, forfeits opportunities, and accumulates into a life of things not done. The question is not whether acting is risky, but whether the risk of not acting is acceptable.',
        'quote'    => 'The things I feared almost never happened. The things I lost to fear were real.',
    ],

    // ============================================================
    // WORK-LIFE BALANCE & BURNOUT
    // ============================================================
    [
        'topic_id' => 8, 'persona_id' => 3,
        'summary'  => 'I burned out completely at 46 after twelve years of building a business without ever truly resting. The burnout was not a choice — it was a system failure. My body and mind simply stopped. Recovery took fourteen months and during that time I could not work, think clearly, or sustain relationships. When I returned to work I built in non-negotiable rest, boundaries around email hours, and a weekly review to ensure I was not reconstructing the conditions that had destroyed me.',
        'advice'   => 'Burnout is a system failure, not a character failure. It is the result of sustained output without adequate recovery. If you are showing the early signs — persistent exhaustion that sleep does not fix, increasing cynicism, declining performance — treat it as urgently as you would treat a physical injury. Reduce load now, before the system collapses completely.',
        'quote'    => 'Burnout does not ask for your permission. It just arrives when you have borrowed too much energy without repayment.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 2,
        'summary'  => 'I deeply regret the vacations I did not take, the evenings I worked through my children\'s childhood, and the health I traded for productivity I now see was largely marginal. The extra hours rarely produced proportional results. The relationships that eroded during those years did not recover proportionally when I reduced my hours later. Time given to the wrong things cannot be refunded. My most expensive mistake was not recognizing this until I was 55.',
        'advice'   => 'Audit how your time is actually distributed and compare it to what you say you value. The gap between stated values and actual time allocation is often shocking. If you say family is your priority and you spend 70 hours a week at work, your behavior tells a different story. Align the time with the values while you still can.',
        'quote'    => 'I said family came first. My calendar said otherwise. The calendar was the truth.',
    ],

    // ============================================================
    // PERSONAL GROWTH IN ADVERSITY
    // ============================================================
    [
        'topic_id' => 10, 'persona_id' => 1,
        'summary'  => 'A severe car accident at 53 left me unable to walk for eight months. I had been intensely physical my whole life — running, hiking, sports. During the recovery I had nothing to do but read, think, and talk to my wife more deeply than we had in years. The accident that took my physical ability gave me a quality of presence and reflection I had never had before. I came back physically different and internally richer in ways I would not trade.',
        'advice'   => 'Forced stillness, though deeply unwelcome, sometimes creates space for growth that the relentless pace of healthy life never allows. If you are in a period of involuntary limitation — through illness, injury, or circumstance — look for what it might be making possible as well as what it is taking away.',
        'quote'    => 'Eight months unable to move were the months I finally learned to be still.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'Post-traumatic growth is real, but it is not automatic. After my divorce, job loss, and health scare all arrived in the same eighteen months, I had two options: collapse or transform. Transformation was not passive — it required choosing, again and again, to use the pain as information about what I wanted my life to look like rather than as evidence that my life was over. The people who grow from adversity are not luckier — they are more intentional about how they interpret and use what happened to them.',
        'advice'   => 'Growth from adversity is not automatic — it requires active interpretation. Ask not just "why did this happen to me?" but "what does this reveal about what I need to change, prioritize, or release?" The meaning you assign to difficult events determines more of the outcome than the events themselves.',
        'quote'    => 'Adversity did not make me stronger automatically. Choosing how to interpret it did.',
    ],

    // ============================================================
    // PHYSICAL & MENTAL DECLINE — HONEST ACCEPTANCE
    // ============================================================
    [
        'topic_id' => 8, 'persona_id' => 2,
        'summary'  => 'I was diagnosed with depression at 57 after spending twenty years dismissing it as weakness. I had been a high-performing professional who believed mental illness was something that happened to other people, people who were not disciplined enough. Accepting the diagnosis and getting treatment was among the best decisions of my life. The years I spent functioning at 40% of my capacity while believing I was simply not trying hard enough were deeply unnecessary.',
        'advice'   => 'Mental health conditions including depression, anxiety, and PTSD are medical conditions, not personal failures. Stigma — particularly among high-achieving people who have successfully managed other life challenges — delays treatment for years and at great personal and professional cost. Seek evaluation and treatment without waiting for permission from your pride.',
        'quote'    => 'I spent twenty years performing adequacy while struggling underneath it. Getting help was not weakness — refusing it had been.',
    ],
    [
        'topic_id' => 8, 'persona_id' => 3,
        'summary'  => 'Aging has taught me that acceptance is a skill, not a surrender. I have had to accept limits on my physical capacity that I resisted for years. The energy I spent fighting those limits was energy I could not use for the things still possible within them. When I stopped fighting what age was taking and started investing fully in what remained, my quality of life improved dramatically. The aging body is a shrinking domain. The question is whether you flourish within the domain you have.',
        'advice'   => 'Distinguish between changes that require adaptation and those that require treatment. Declining energy, some physical limitation, and slower recovery are normal aging that deserve acceptance and thoughtful adaptation. Pain, mental health decline, and rapid functional decline deserve medical attention. Knowing the difference saves you from both over-medicalizing normal aging and under-treating real conditions.',
        'quote'    => 'I made peace with what I was losing and discovered how much remained. That peace was the gift.',
    ],

    // ============================================================
    // FINANCIAL WISDOM — PRACTICAL LESSONS
    // ============================================================
    [
        'topic_id' => 7, 'persona_id' => 1,
        'summary'  => 'I made every expensive financial mistake in the book: lifestyle inflation as income rose, investment in things I did not understand, lending money to family members without boundaries, ignoring insurance until it was too late. None of these were complicated mistakes in retrospect — they were all variations of spending money based on emotion rather than values and plan. At 65, I am comfortable but I should be wealthy. The gap between where I am and where I could be is clearly traceable to specific decisions in my 30s and 40s.',
        'advice'   => 'Match your financial decisions to your actual values rather than your emotional state in the moment. Create a financial plan when you are calm and deliberate, and then follow it especially when you are not. Lifestyle inflation, emotional investing, and lending to family without clear terms are the most common financial self-sabotages.',
        'quote'    => 'I could trace every financial shortfall to a specific moment when I chose emotion over plan.',
    ],
    [
        'topic_id' => 7, 'persona_id' => 3,
        'summary'  => 'The financial advice nobody gives you in your 30s is to build an emergency fund before any other investment. I ignored this for years, investing in volatile assets while running a bare-minimum cash reserve. When a genuine emergency hit — a medical crisis, a job loss — I had to liquidate investments at a loss at the worst possible time. The emergency fund is the foundation, not the afterthought. Everything else — investing, debt payoff, retirement saving — builds on that foundation.',
        'advice'   => 'Build a six-month emergency fund as your absolute first financial priority, before aggressive investing or debt payoff. This fund is not an investment — it is insurance against being forced to make poor financial decisions during a crisis. It prevents you from liquidating long-term assets at bad times and from taking on expensive debt when emergencies strike.',
        'quote'    => 'My emergency fund would have cost nothing if nothing went wrong. Without it, everything cost more.',
    ],

    // ============================================================
    // RAISING RESILIENT CHILDREN
    // ============================================================
    [
        'topic_id' => 6, 'persona_id' => 3,
        'summary'  => 'My parenting philosophy shifted completely in my 40s when I realized I was raising children who could not tolerate discomfort. Every time they struggled I intervened, every obstacle I removed. They were well-provided for and entirely unprepared for adversity. The most valuable thing I could do as a parent was allow them to experience manageable failures with my support — not my rescue. The resilience they built through working through difficulty served them far more than anything I solved for them.',
        'advice'   => 'Allow your children to experience age-appropriate frustration, failure, and difficulty without immediately rescuing them. Resilience is built through navigating challenge with support, not through being shielded from it. Your presence and support during their difficulty is the valuable thing — not your ability to prevent the difficulty.',
        'quote'    => 'The best parenting I ever did was stepping back and letting them work through it themselves.',
    ],

    // ============================================================
    // ASKING FOR HELP
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'For most of my life I was constitutionally incapable of asking for help. I associated it with incompetence and saw it as an imposition on others. What I eventually discovered is that most people find meaning in helping and that refusing to ask was actually depriving them of something they wanted to give. Learning to ask clearly, specifically, and without apology transformed my relationships and my effectiveness more than any skill I developed alone.',
        'advice'   => 'Learn to ask for help specifically and without excessive apology. Vague or apologetic requests are easy to decline. Clear, specific asks give people the opportunity to help in a way they can actually do. Most people want to help — the barrier is usually the asker, not the person being asked.',
        'quote'    => 'Asking for help was not taking — it was giving people the opportunity to contribute. That reframe changed everything.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 1,
        'summary'  => 'The pattern I see repeatedly in people who plateau — in careers, in relationships, in health — is a refusal to seek expert guidance when it is needed. Pride, cost-consciousness, and a belief that they should be able to figure it out alone are the most common reasons. People seek doctors for medical issues without embarrassment. They should seek coaches, therapists, financial advisors, and mentors with the same straightforwardness. Expert guidance is not a last resort — it is an investment.',
        'advice'   => 'Normalize seeking expert guidance across all important areas of life. A therapist for mental health, a financial advisor for wealth, a coach for professional development, a mentor for career navigation. The people who grow fastest are those who seek quality feedback and guidance proactively rather than waiting until they are desperate.',
        'quote'    => 'We seek doctors without shame for physical health. Mental, financial, and professional health deserve the same directness.',
    ],
];

// INSERT
$inserted  = 0;
$failed    = 0;
$chunkRows = 0;

echo "Starting RAG seed v3...\n";
echo "Total stories to insert: " . count($stories) . "\n\n";

foreach ($stories as $idx => $s) {
    try {
        DB::beginTransaction();
        $uuid = makeUUID();

        DB::insert(
            "INSERT INTO lg_interviews (uuid, topic_id, persona_id, status, created_at, updated_at)
             VALUES (:uuid, :topic_id, :persona_id, 'completed', NOW(), NOW())",
            ['uuid' => $uuid, 'topic_id' => $s['topic_id'], 'persona_id' => $s['persona_id']]
        );
        $intId = DB::fetch("SELECT interview_id FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $uuid])['interview_id'];

        DB::insert(
            "INSERT INTO lg_consents (interview_id, rag_consent, storage_consent, attribution_type, withdrawn, created_at)
             VALUES (:id, 1, 1, 'anonymous', 0, NOW())",
            ['id' => $intId]
        );

        foreach ([
            ['story_summary',        $s['summary']],
            ['advice',               $s['advice']],
            ['representative_quote', $s['quote']],
        ] as [$type, $text]) {
            DB::insert(
                "INSERT INTO lg_knowledge_chunks
                    (interview_id, content_type, anonymized_text, embedding, embedding_model,
                     status, approved_for_rag, created_at, updated_at)
                 VALUES (:iid, :type, :text, NULL, 'pending', 'approved', 1, NOW(), NOW())",
                ['iid' => $intId, 'type' => $type, 'text' => $text]
            );
            $chunkRows++;
        }

        DB::commit();
        $inserted++;

        if (($idx + 1) % 10 === 0) {
            echo "  Inserted " . ($idx + 1) . " / " . count($stories) . " stories...\n";
        }
    } catch (Exception $e) {
        DB::rollBack();
        $failed++;
        echo "  ERROR on story " . ($idx + 1) . ": " . $e->getMessage() . "\n";
    }
}

echo "\n=== Seed v3 complete ===\n";
echo "Stories inserted: {$inserted}\n";
echo "Chunks created:   {$chunkRows}\n";
echo "Errors:           {$failed}\n";

$newTotal = DB::fetch('SELECT COUNT(*) as cnt FROM lg_knowledge_chunks WHERE status="approved" AND approved_for_rag=1');
echo "\nUpdated total approved RAG chunks: " . $newTotal['cnt'] . "\n";
