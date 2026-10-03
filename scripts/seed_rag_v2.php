<?php
/**
 * LifeGPT - Rich RAG Knowledge Seed (v2)
 * Adds 50 high-quality interviews with 3 chunks each = 150 new approved chunks.
 * 
 * Focus areas determined by audit gaps:
 *  - Career & work (skill prioritization, growth, proficiency, measuring progress, stagnation)
 *  - Health & aging (midlife health, fitness habits, mental health, energy management)
 *  - Lesson I learned (resilience, failure, pivots, financial mistakes)
 *  - Advice for younger people (career, relationships, money, purpose)
 *  - Something I would do differently (career, money, health, relationships)
 *  - Major turning point (career shift, health crisis, financial reset)
 * 
 * Run via: php scripts/seed_rag_v2.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

function makeUUID(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// topic_id mapping from audit:
// 1=A lesson I learned, 2=Something I would do differently, 3=Advice for younger people
// 4=A funny life experience, 5=Career and work, 6=Family and relationships
// 7=Money and retirement, 8=Health and aging, 9=Technology and how life changed
// 10=A major turning point, 11=Something I am proud of, 12=Surprise me
// persona_ids: 1=Curious Grandchild, 2=Journalist, 3=Life Coach, 4=Comedian, 5=Historian

$stories = [

    // ============================================================
    // CAREER & WORK — SKILL PRIORITIZATION, GROWTH, PROFICIENCY
    // ============================================================
    [
        'topic_id' => 5, 'persona_id' => 3,
        'summary'  => 'I spent years trying to be good at everything and ended up mediocre at all of it. At 52, after losing a senior marketing role, I realized my real advantage was deep expertise in data analytics. I spent six months doing nothing but sharpening that one skill, took on two freelance projects to test it, and within a year I was earning more than my old corporate salary. Depth beats breadth when the market gets competitive.',
        'advice'   => 'Pick the one skill that genuinely differentiates you and go deep on it before adding anything else. Trying to develop five skills simultaneously means none of them reach the level that actually commands attention. Master one, use that momentum to learn the next.',
        'quote'    => 'I was a jack of all trades and it nearly ended my career. Depth saved me.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 2,
        'summary'  => 'After being passed over for promotion three times, I finally asked my manager what was actually holding me back. The answer surprised me: it was not my technical skills but my inability to communicate upward. I could do the work brilliantly but nobody above me understood what I was doing or why it mattered. I spent eighteen months practicing executive communication, writing shorter emails, presenting data as stories. I got the promotion on my next review.',
        'advice'   => 'The skill that gets you promoted past your first few levels is rarely the same skill that got you hired. Communication, influence, and the ability to make your work visible are often the real gatekeepers to advancement.',
        'quote'    => 'I was producing great work in a room nobody was watching. Learning to open the door changed everything.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 3,
        'summary'  => 'I am a 58-year-old software engineer who never stopped learning. The key thing I tell younger developers is that the half-life of any technical skill is now about three years. I have reinvented myself six times in my career. Each time, I gave myself 90 days of focused learning, built one real project with the new skill, and then used that project as proof of competence. That cycle has kept me relevant and well-paid longer than almost anyone I started with.',
        'advice'   => 'Give yourself a structured 90-day sprint to learn any new skill: 30 days studying, 30 days building a small project, 30 days getting feedback and iterating. Without a real output to show, learning stays theoretical and never sticks.',
        'quote'    => 'Every three years I basically restart. That habit is the reason I still have a career at 58.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 1,
        'summary'  => 'I started my career as a teacher and transitioned into corporate training at 44. The skill that made the transition possible was not any technical knowledge — it was my ability to explain complex things simply. I had developed that teaching ability over 20 years without realizing it was a transferable, high-value business skill. When I started packaging it as facilitation and instructional design, doors opened immediately.',
        'advice'   => 'Look back at what you already do naturally and well. Often your most marketable skill is something you have been practicing for years without recognizing its value. The ability to simplify complexity, to persuade, to listen deeply — these are rare and in demand.',
        'quote'    => 'I thought I had to start over. It turned out I had already built the most important skill I needed.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 3,
        'summary'  => 'Measuring skill progress was the thing I got wrong for most of my career. I would read books, take courses, feel like I was learning, and then realize nothing had actually changed about how I worked. The shift happened when I started tracking outcomes rather than inputs. Not how many courses I took, but how much faster I completed projects, how many fewer errors I made, how confidently I handled problems I used to avoid.',
        'advice'   => 'Stop measuring learning by time spent or content consumed. Measure it by behavioral change: what can you now do that you could not do three months ago? What problems do you handle with less anxiety? What results have improved? Those are the real indicators.',
        'quote'    => 'I used to count courses. Now I count capabilities. Completely different picture.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 2,
        'summary'  => 'I plateaued in my career for four years and could not figure out why. I was working hard, learning new tools, putting in long hours. A mentor finally told me: you are optimizing the wrong things. I was improving skills I was already good at. The real gap was in areas I was actively avoiding — conflict resolution, saying no to scope creep, managing up. I had been running from my weaknesses disguised as a learner.',
        'advice'   => 'When you feel stuck, honestly audit where your real gaps are. Most people keep improving their strengths because it feels good. The breakthrough usually comes from addressing the one thing you have been avoiding.',
        'quote'    => 'Four years of spinning my wheels. One honest conversation changed everything.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 3,
        'summary'  => 'I became truly proficient at negotiation after my 50th birthday, even though I had been in sales my whole career. The difference was that I finally accepted that proficiency is not about technique — it is about internal confidence that comes only from doing hard reps in real situations. I negotiated hundreds of deals badly before I negotiated them well. There is no shortcut to the hours of uncomfortable practice.',
        'advice'   => 'Real proficiency in any interpersonal skill requires genuine discomfort along the way. You will do it badly in front of real people before you do it well. The only way to shorten that timeline is to put yourself in more real situations, not more practice simulations.',
        'quote'    => 'A hundred bad negotiations built the confidence for good ones. No simulation could have done that.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 2,
        'summary'  => 'I spent fifteen years waiting to feel ready before taking on leadership. When I finally stepped into a management role at 47 I realized readiness is a myth — you grow into it by doing. The first year was hard. I made mistakes I learned from faster than any course could have taught me. By year two I was genuinely good at it. The waiting had been the real waste.',
        'advice'   => 'Stop waiting to feel ready for the next level. Readiness follows action, it does not precede it. The discomfort of doing something you are not yet good at is the mechanism through which you become good at it.',
        'quote'    => 'I waited fifteen years to feel ready. I would have saved fifteen years by just starting.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 3,
        'summary'  => 'My biggest career mistake was not asking for feedback consistently. I would wait for annual reviews and then be surprised. When I started asking monthly — specifically asking what one thing I could do better — everything accelerated. People gave me honest answers I could act on. My improvement rate roughly tripled once I replaced annual feedback with continuous feedback.',
        'advice'   => 'Make feedback a monthly habit rather than an annual event. Ask one specific question: what is one thing I could do better? The specificity and frequency of feedback is more important than its formality.',
        'quote'    => 'Annual reviews told me where I had been. Monthly check-ins showed me where to go.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 1,
        'summary'  => 'I spent the first half of my career chasing salary and the second half chasing skills. The second half was far more satisfying and ultimately more lucrative. When you focus on skills, the right opportunities find you. When you focus only on salary, you often end up in roles that pay well but stall your development, and then the market corrects.',
        'advice'   => 'In your 30s and early 40s, optimize for skill acquisition over salary. Accept slightly lower pay if the role teaches you something valuable. The compound interest on skills is greater than the compound interest on a modest salary bump.',
        'quote'    => 'Skills compound. Salary comparisons just make you resentful.',
    ],

    // ============================================================
    // HOW LONG DOES IT TAKE / PROFICIENCY TIMELINE
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'People always ask me how long it takes to master something. My honest answer after 40 years of learning new things: it takes about 20 hours to be functional, 200 hours to be confident, and 2000 hours to be genuinely excellent. But the most important number is the first 20 — most people quit before they cross that threshold. Everything difficult feels worst at hour three.',
        'advice'   => 'Break the learning journey into phases. Accept that the first 20 hours will feel terrible. Power through to functional competence, then reassess. Many people abandon skills in hour three that they would have loved in hour twenty-one.',
        'quote'    => 'Most people quit at the worst possible time — right before it starts getting easier.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'I learned to play guitar at 55. Everyone told me I was too old. It took me two years of daily practice to play songs I was proud of. The timeline was longer than it would have been at 25, but the satisfaction was greater because I understood the investment I was making. Age does not eliminate the ability to learn — it just changes the timeline and deepens the meaning.',
        'advice'   => 'Do not let anyone else\'s timeline discourage yours. Some people learn in six months what takes others three years. What matters is consistent practice, not speed. The comparison that matters is you today versus you six months ago.',
        'quote'    => 'I did not learn guitar fast. I learned it well. Big difference.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 3,
        'summary'  => 'The timeline question is the wrong question. Asking how long it takes to become proficient is like asking how long it takes to get fit — it depends entirely on where you start, how often you train, and how well you recover. The people who get good fast are not more talented, they are more consistent and more deliberate in their practice. Consistency over a year beats intensity over a month every time.',
        'advice'   => 'Replace the question "how long will this take?" with "how do I make this sustainable?" Build a daily practice that is small enough to do even on your worst days. Ten minutes every day beats two-hour sessions twice a week.',
        'quote'    => 'Consistency is boring. It also works while everything else doesn\'t.',
    ],

    // ============================================================
    // MEASURING PROGRESS / NOT SEEING IMPROVEMENT
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'For ten years I tried to get healthier and felt like I was failing. I was tracking weight on a scale that went up and down daily and treating each bad day as evidence of overall failure. A nutritionist finally suggested I measure monthly averages, track energy levels, and note what I could do physically that I could not do before. When I switched from lagging indicators to leading ones, I saw real progress that had been invisible to me.',
        'advice'   => 'Choose the right metrics. Scales measure daily water fluctuations, not fat loss. Test scores measure one day, not your trajectory. Design measures that capture direction and trend, not just today\'s snapshot.',
        'quote'    => 'I was measuring the wrong thing and concluding I was failing at the right thing.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'I went six months into learning a new language and felt no improvement. My tutor showed me recordings of our early sessions alongside recent ones. The contrast was stark — my pronunciation, vocabulary, and fluency had all improved dramatically. I simply could not see it because improvement had been gradual. Now I record myself at milestones and compare backward, not forward.',
        'advice'   => 'Document your starting point before you begin. Take video of yourself doing the skill on day one, and again at day 30, day 90, day 180. Progress is almost impossible to notice in real time but obvious when you compare baseline to current.',
        'quote'    => 'I could not see the progress because I was standing inside it.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 3,
        'summary'  => 'When I stopped seeing improvement in my business, my instinct was to push harder. Work more hours, try more strategies. What actually helped was stopping and doing a proper diagnosis. I identified that my sales skills were fine but my client retention was terrible. I had been trying to fill a leaky bucket. Fixing the leak — improving how I served existing clients — was worth more than all the new marketing I was doing.',
        'advice'   => 'When progress stalls, diagnose before you add more effort. The bottleneck is rarely where you think it is. Map your process, find where things break down, and address that specifically. More effort through the wrong door just exhausts you.',
        'quote'    => 'Working harder on the wrong thing is the most expensive mistake in business.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'I did not see improvement for eight months in my leadership skills. Then I realized I was only measuring myself by the opinions of the same three people I had always asked. When I expanded my feedback circle — asking people at different levels, from different departments, even from outside the company — I discovered I had been improving significantly in ways that mattered to most people, just not to the three whose opinions I was over-weighting.',
        'advice'   => 'Seek feedback from multiple sources and perspectives, not just the one or two people whose opinion you value most. Sometimes the absence of progress is a measurement problem, not a performance problem.',
        'quote'    => 'I was improving everywhere except in the narrow mirror I was using to judge myself.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'In my 40s I went back to graduate school while working full time. For the first semester I felt no smarter, no more capable. I almost quit. What kept me going was a journal my professor suggested: write down one thing you understood today that you did not understand yesterday. Reading those entries at the end of the semester showed me hundreds of small gains I had completely ignored because I was only looking for a dramatic breakthrough.',
        'advice'   => 'Keep a daily progress log, even just one line. "Today I understood X" or "Today I did Y better than last time." These micro-entries accumulate into compelling evidence of growth that is otherwise invisible in the day-to-day.',
        'quote'    => 'Growth is quiet. Most people only notice the dramatic moments and miss the compound.',
    ],
    [
        'topic_id' => 10, 'persona_id' => 3,
        'summary'  => 'I was a mid-level manager who could not break through to the director level for six years. I kept doing what had made me successful before. The turning point came when a coach helped me see that the skills that got me to manager were actively working against me as a potential director. I had to let go of doing and embrace leading. Unlearning was harder than learning.',
        'advice'   => 'At certain career transitions, the skills that got you here become the ceiling, not the ladder. Be willing to examine what you need to stop doing, not just what you need to start. Letting go of identity is hard but often necessary.',
        'quote'    => 'I had to unlearn being good at my old job before I could be good at the new one.',
    ],

    // ============================================================
    // WHEN IMPROVEMENT IS NOT VISIBLE — WHAT TO DO
    // ============================================================
    [
        'topic_id' => 3, 'persona_id' => 3,
        'summary'  => 'After two years of trying to improve my public speaking, I was ready to give up. My coach suggested I change one thing: stop trying to perform better and start focusing entirely on the audience. Instead of thinking about how I was coming across, I focused on whether the person in the front row was following along, whether the person in the back seemed engaged. This shift from self-focus to audience-focus transformed my presence within six months.',
        'advice'   => 'If you are plateauing in a people-facing skill, shift your focus from your own performance to the person you are serving. Self-consciousness is the enemy of presence. The antidote is genuine curiosity about the other person.',
        'quote'    => 'When I stopped watching myself, other people started watching me.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 3,
        'summary'  => 'I tried to learn investing for three years by reading books and watching videos. My portfolio performance was poor. The change happened when I got a mentor — someone who had actually invested through market cycles and could help me interpret what I was seeing in real time. The gap between theoretical knowledge and practical wisdom was enormous. Knowledge without context is almost useless.',
        'advice'   => 'If you are stuck despite consuming information, find someone who has done it, not just someone who teaches about it. A mentor with real scars teaches you what no course can — the emotional and contextual layer that determines real-world performance.',
        'quote'    => 'Books taught me theory. A mentor taught me what to do when the theory stops working.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'I ran a small restaurant for twelve years and went through a five-year period where nothing seemed to improve. Revenue was flat, staff kept quitting, and I was exhausted. The breakthrough came when I finally stopped trying to fix everything myself and hired a business consultant for three months. She saw things in a week that I had been blind to for years. Sometimes the best investment when you are stuck is paying for a fresh pair of eyes.',
        'advice'   => 'When you have been stuck for more than six months despite genuine effort, consider that the problem may not be your skill level but your perspective. An outside view — a coach, consultant, or trusted critic — can identify your blind spots faster than more practice will.',
        'quote'    => 'I had been staring at my own problem for so long I could no longer see it.',
    ],

    // ============================================================
    // CAREER PRIORITIES / WHAT TO FOCUS ON FIRST
    // ============================================================
    [
        'topic_id' => 3, 'persona_id' => 3,
        'summary'  => 'The single most valuable piece of career advice I received at 35 was this: your reputation is more important than your resume. Your resume is a document. Your reputation is what people say when your name comes up in a room you are not in. I spent years polishing the document and neglecting the reputation. Reversing that priority changed my career within three years.',
        'advice'   => 'Prioritize reputation over credentials. Every decision you make at work is either building or eroding what people believe about you. Be the person who delivers without being asked, who helps without keeping score, who tells the truth when it is uncomfortable. That reputation travels faster and further than any certificate.',
        'quote'    => 'Credentials get you the interview. Reputation gets you the job.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 2,
        'summary'  => 'I wasted my 30s trying to be an expert generalist. I read widely, took courses in everything, kept myself broadly informed. At 42, I realized the market pays specialists, not generalists. I picked one area — healthcare operations — and committed to becoming genuinely known for it. Within two years I had more opportunities than I had ever had as a generalist.',
        'advice'   => 'Specialization is uncomfortable because it feels like closing doors. It actually opens a different, better set of doors. Become the person who is known for something specific, and let that specificity attract the right opportunities to you.',
        'quote'    => 'Being known for everything meant I was hired for nothing. Specializing changed that.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 3,
        'summary'  => 'When I coach people who feel overwhelmed by too many skill gaps, I always start with the same question: what is the one bottleneck that, if removed, would make everything else easier? For most people early in their career it is communication. For mid-career professionals it is influence without authority. For executives it is delegation. Identify your bottleneck, not your full list of weaknesses.',
        'advice'   => 'Do not try to improve ten things at once. Identify the one skill that is currently limiting everything else and fix that first. Once you remove the bottleneck, you will often find that other supposed weaknesses resolve themselves or matter less than you thought.',
        'quote'    => 'There is always one thing holding the rest back. Find it before you fix anything else.',
    ],
    [
        'topic_id' => 5, 'persona_id' => 1,
        'summary'  => 'I managed a team of 25 people for a decade. The skill I wish I had prioritized first was emotional intelligence. I was technically brilliant and could solve any business problem put in front of me. But I had no patience for people\'s emotional states, no ability to read the room, no sensitivity to what my team needed from me. I lost several great people before I understood what was happening. No technical skill substitutes for human understanding when you lead people.',
        'advice'   => 'If you manage people or plan to, make emotional intelligence your top priority before any technical upgrade. Your ability to understand, motivate, and retain talented people will determine your ceiling more than any hard skill.',
        'quote'    => 'I was technically excellent and people-blind. It cost me my best team members one by one.',
    ],

    // ============================================================
    // HEALTH & AGING — HABITS, ENERGY, FITNESS
    // ============================================================
    [
        'topic_id' => 8, 'persona_id' => 3,
        'summary'  => 'I ignored my physical health for twenty years, telling myself I would focus on it when things slowed down. At 54 I had a cardiac event that hospitalized me for a week. It did not kill me, but the recovery took eighteen months and changed my priorities completely. The body sends signals long before it sends alarms. I had been ignoring the signals for years.',
        'advice'   => 'Your physical health is the foundation everything else is built on. No career achievement, financial success, or relationship milestone matters if your body gives out. Build daily movement, adequate sleep, and regular medical checkups into your life now, not later.',
        'quote'    => 'I kept saying I would take care of my body when things slowed down. My body did not wait.',
    ],
    [
        'topic_id' => 8, 'persona_id' => 1,
        'summary'  => 'After turning 50 I struggled with consistent energy levels. I was sleeping enough but still exhausted by mid-afternoon. A simple change — cutting off caffeine at noon and eating a protein-focused lunch instead of carbs — transformed my afternoons within three weeks. The smallest habits often have the most disproportionate effects on energy, and yet we overlook them while looking for dramatic interventions.',
        'advice'   => 'Before you search for complex solutions to low energy, audit your sleep quality, caffeine timing, nutrition, and hydration. Many energy problems in midlife are solved by small consistent adjustments, not radical overhauls.',
        'quote'    => 'My energy problem had a boring solution. I was looking for a dramatic one.',
    ],
    [
        'topic_id' => 8, 'persona_id' => 3,
        'summary'  => 'I spent most of my 40s managing chronic back pain. Physical therapy, medications, adjustments — nothing stuck because I never addressed the root cause: sitting at a desk for 10 hours a day with zero movement breaks. A physiotherapist finally told me the truth bluntly: you cannot exercise your way out of a sedentary job. I restructured my day to include a five-minute movement break every hour and the pain largely resolved over six months.',
        'advice'   => 'Movement frequency matters more than exercise duration for people with desk-based work. Short movement breaks throughout the day are more effective than one gym session at the end of a ten-hour sitting marathon.',
        'quote'    => 'I was exercising daily and destroying my health hourly. Frequency was the fix.',
    ],
    [
        'topic_id' => 8, 'persona_id' => 2,
        'summary'  => 'I have always been physically active but I completely neglected mental health until my early 50s. Work stress, a difficult marriage, and financial pressure built up over years with no outlet. The crisis came suddenly but the causes had been accumulating for a decade. Starting therapy was the bravest and most impactful health decision I made in my life. The physical health I tended so carefully would have been irrelevant without mental resilience.',
        'advice'   => 'Mental health deserves the same level of proactive attention you give physical health. Do not wait for a crisis. Find a therapist or counselor during calmer periods, build a practice of self-reflection, and create social support structures before you desperately need them.',
        'quote'    => 'I maintained my body and neglected my mind for thirty years. The bill eventually came due.',
    ],
    [
        'topic_id' => 8, 'persona_id' => 3,
        'summary'  => 'Retirement hit me harder than I expected physically. I had been physically active through work — walking to meetings, taking stairs, the general activity of an office environment. When that stopped, I gained fifteen pounds in the first year without changing my diet. I had to deliberately engineer movement back into my day in ways I had never needed to before. Retirement is a health risk disguised as a reward.',
        'advice'   => 'Plan your physical activity structure before you retire, not after. The incidental movement of working life disappears immediately in retirement. Have a routine in place on day one or the decline begins faster than you expect.',
        'quote'    => 'I retired into relaxation and accidentally retired into declining health.',
    ],

    // ============================================================
    // SOMETHING I WOULD DO DIFFERENTLY — CAREER & MONEY
    // ============================================================
    [
        'topic_id' => 2, 'persona_id' => 2,
        'summary'  => 'If I could change one thing about my career, I would have networked genuinely and consistently throughout, not frantically when I needed something. I had years where I was too busy to maintain relationships, and then a restructuring wiped out my role and I found my network had completely atrophied. Rebuilding took three painful years. Relationships maintained in good times are worth ten times relationships requested in bad times.',
        'advice'   => 'Invest in your professional network during calm, comfortable periods. Reach out when you have nothing to ask for. Share things that are useful. Remember details about people\'s lives. The network that exists before you need it is the only network that actually helps you.',
        'quote'    => 'I called people when I needed them. By then it was too late to call them friends.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 3,
        'summary'  => 'The financial decision I most regret is waiting until 48 to start saving seriously. I had a decade of high earnings in my 30s where I spent freely and saved almost nothing, assuming I would catch up later. The mathematics of compound interest do not forgive late starts. A financial advisor showed me that a dollar invested at 30 is worth roughly five times the same dollar invested at 45. That simulation haunts me.',
        'advice'   => 'Start saving and investing as early as possible, even if the amounts feel insignificant. Time in the market is the single most powerful variable in wealth building, and it is the only one you cannot buy back. Starting at 25 with small amounts beats starting at 40 with large amounts.',
        'quote'    => 'I spent my 30s as if I had infinite time. Compound interest does not work that way.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 1,
        'summary'  => 'I would have asked for help much earlier. I spent years trying to solve business problems on my own because asking felt like admitting weakness. By the time I hired a mentor, joined a peer group, and started seeking advice regularly, I had wasted years reinventing wheels that others had already built. Pride is the most expensive career luxury there is.',
        'advice'   => 'Normalize asking for help and guidance. The most successful people I know are not the ones with all the answers — they are the ones who consistently build systems for getting the right answers from the right people quickly.',
        'quote'    => 'I wasted years solving problems alone that others had already solved. Pride cost me years.',
    ],
    [
        'topic_id' => 2, 'persona_id' => 2,
        'summary'  => 'I stayed in a job I disliked for seven years because the salary was good and change felt scary. I kept calculating what I would lose rather than what I might gain. When I finally left, I took a role that paid fifteen percent less and within two years had surpassed my old salary, found my work energizing, and stopped dreading Monday mornings. The cost of staying too long somewhere that dims you is enormous and invisible.',
        'advice'   => 'If you have genuinely tried to make a situation work for more than two years and it still drains you consistently, the cost of staying is higher than the cost of leaving. Do not let golden handcuffs or fear of change substitute for honest assessment.',
        'quote'    => 'Seven years of security felt safe. It was actually seven years of invisible damage.',
    ],

    // ============================================================
    // ADVICE FOR YOUNGER PEOPLE
    // ============================================================
    [
        'topic_id' => 3, 'persona_id' => 1,
        'summary'  => 'The advice I would give my younger self is this: your 30s are for building, not performing. I spent my 30s trying to look successful rather than actually becoming it. I drove the right car, lived in the right neighborhood, and had almost no savings or skills to show for it. The people who built substance in their 30s instead of appearances are the ones I watched thrive in their 50s.',
        'advice'   => 'Prioritize genuine capability, savings, and relationships in your 30s over external signals of success. The person who looks successful at 35 and the person who is actually building something often make opposite choices. Choose building.',
        'quote'    => 'I performed success in my 30s. I actually built it in my 40s. I wish I had started the building earlier.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 3,
        'summary'  => 'If I had one career insight to pass on it would be: find out what people will actually pay for, not just what you are good at. I was an excellent illustrator and spent years producing beautiful work that nobody valued commercially. When I applied my visual skills to UX design — a market that desperately needed people who could think visually — everything changed. Skill plus market need is the combination. Skill alone is a hobby.',
        'advice'   => 'Validate your skills against real market demand regularly. Talk to people who hire, look at what problems organizations are paying to solve, and find the intersection between your genuine capabilities and genuine market need. That intersection is where a real career lives.',
        'quote'    => 'I was good at something nobody was paying for. Finding what the market needed saved my livelihood.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 2,
        'summary'  => 'The relationship advice I give every young person is: be as intentional about choosing your partner as you are about choosing your career. I was extremely deliberate in my professional decisions and completely impulsive in my relationship choices. My marriage ended painfully in my 40s in a way that affected my children, my finances, and my sense of self for years. The most important decision you make is not your job but your life partner.',
        'advice'   => 'Apply careful, values-based thinking to relationship choices. Attraction and excitement are real but they are not sufficient criteria. Look for shared values, compatible life goals, and evidence of character over time — especially under stress.',
        'quote'    => 'I hired employees more carefully than I chose my partner. Do not make that mistake.',
    ],
    [
        'topic_id' => 3, 'persona_id' => 1,
        'summary'  => 'Young people underestimate how much of life is just showing up consistently. I was talented and unreliable in my 20s. I was outperformed by people half as talented who showed up every day without drama. Reliability, follow-through, and consistency are underrated superpowers that compound aggressively over time.',
        'advice'   => 'Develop the habit of doing what you said you would do, when you said you would do it, without requiring reminders. This single behavior distinguishes you from the majority of talented people and builds a reputation that takes years to earn and minutes to destroy.',
        'quote'    => 'I was talented and unreliable. Talent without reliability is just potential no one can count on.',
    ],

    // ============================================================
    // MAJOR TURNING POINTS
    // ============================================================
    [
        'topic_id' => 10, 'persona_id' => 2,
        'summary'  => 'Being laid off at 49 was the best and worst thing that happened to my career. In the moment it felt like the floor collapsing. In retrospect, it was the door I had been too comfortable to open myself. Six months of unemployment forced me to be honest about what I actually wanted. I started a consultancy, earned more in the first year than in any previous year, and have not had a traditional job since.',
        'advice'   => 'Forced transitions, though painful, often remove the inertia that was keeping you in a situation you had outgrown. When the floor collapses, look at what became visible underneath. The disruption often reveals an opportunity that comfort was concealing.',
        'quote'    => 'Losing my job was terrifying. It was also the first time I had to truly choose my path.',
    ],
    [
        'topic_id' => 10, 'persona_id' => 3,
        'summary'  => 'My turning point came when my doctor told me my lifestyle would kill me within ten years if nothing changed. I was 51, overweight, hypertensive, and sleeping four hours a night. I had built a successful business by treating my body as a machine. The diagnosis was a hard reset. I sold a non-essential division of the business to create space, hired a health coach, and rebuilt my physical foundations from zero. Four years later my numbers are all normal and I do better work in less time.',
        'advice'   => 'A health crisis is brutal information delivered at the worst time in the hardest way. Take it seriously. The things that got you to a position of outward success rarely include the habits that sustain health. Treating health as a strategy, not a luxury, often improves performance in every other area simultaneously.',
        'quote'    => 'Success had made me comfortable with habits that were slowly ending me.',
    ],
    [
        'topic_id' => 10, 'persona_id' => 1,
        'summary'  => 'I emigrated at 45 with my family to a country where my professional credentials meant almost nothing. Starting over was humbling and hard. What I discovered was that the habits and character I had built over decades transferred perfectly, even when the credentials did not. Within three years I had rebuilt my professional standing, and the experience gave me a resilience and confidence I could never have developed any other way.',
        'advice'   => 'When circumstances strip away credentials and status, what remains is character, adaptability, and genuine skill. Those things rebuild faster than you expect when paired with persistence. Your identity is not your title or your country.',
        'quote'    => 'I lost my credentials and had to rebuild. What I discovered is that I was more than my credentials.',
    ],
    [
        'topic_id' => 10, 'persona_id' => 2,
        'summary'  => 'My marriage ended in my early 50s after 22 years. It devastated me and my children. The years that followed were the loneliest and most clarifying of my life. I discovered that I had subordinated my own identity to the marriage for decades — my interests, my friendships, my sense of what I wanted. Rebuilding meant rediscovering who I was without the role of husband. It was painful and it made me more whole than I had ever been.',
        'advice'   => 'Even the hardest endings carry the possibility of authentic beginnings. If you are rebuilding after a major loss, give yourself permission to rediscover what you actually want — not just what you are supposed to want. The identity that emerges from genuine loss is often more honest and more sustainable than the one that preceded it.',
        'quote'    => 'I thought I was half of a marriage. I had forgotten I was a whole person.',
    ],
    [
        'topic_id' => 10, 'persona_id' => 3,
        'summary'  => 'At 56 I was diagnosed with a condition that ended my ability to do the physical work I had been doing for thirty years. Construction was not just my job, it was my identity. The first year after the diagnosis I was in denial and then depression. A therapist helped me see that the skills underlying my career — problem-solving, coordination, people management, quality control — were completely transferable. I pivoted to project management consulting and have been doing meaningful work ever since.',
        'advice'   => 'When a specific role or physical capability is taken from you, invest time in identifying the underlying skills and thinking patterns that made you effective. Those transfer even when the specific function cannot. Identity attached to a role is fragile. Identity attached to how you think and work is portable.',
        'quote'    => 'I thought my career was over. It turned out my career was just changing shape.',
    ],

    // ============================================================
    // SOMETHING I AM PROUD OF
    // ============================================================
    [
        'topic_id' => 11, 'persona_id' => 1,
        'summary'  => 'I am most proud of going back to university at 52 and completing a degree while working full-time and raising teenagers. Everyone thought I was crazy. I thought I was crazy. But I needed to prove to myself — and quietly to my children — that learning does not have an expiry date, and that hard things are worth attempting even when the timing is not ideal. I graduated at 55 and my children gave me a standing ovation.',
        'advice'   => 'Do not delay the meaningful thing you want to do because the timing is not right. The timing will rarely be right. The people watching you struggle and succeed teach your children and your community more about what is possible than any speech ever could.',
        'quote'    => 'The best thing I ever did for my children was let them watch me struggle and then succeed.',
    ],
    [
        'topic_id' => 11, 'persona_id' => 2,
        'summary'  => 'I am proud that I chose a career I loved over a career that paid more. I took a 40% pay cut in my late 30s to move from corporate law to nonprofit work. For years people questioned my sanity. But I built a retirement fund anyway — more slowly, more carefully — and I did work that made me feel alive instead of drained. The sacrifice was real. So was the satisfaction.',
        'advice'   => 'Financial security and meaningful work are not mutually exclusive, but they do require tradeoffs and careful planning. If you choose meaning over money, do so with open eyes, a realistic financial plan, and the conviction that the exchange is worth it to you.',
        'quote'    => 'I took less money and got more life. I have never regretted that trade.',
    ],

    // ============================================================
    // FAMILY, RELATIONSHIPS, WORK-LIFE BALANCE
    // ============================================================
    [
        'topic_id' => 6, 'persona_id' => 3,
        'summary'  => 'I prioritized career over family for two decades and told myself it was for them. My children needed presence, not provision. I was providing and absent. By the time I woke up to what I had missed, my kids were teenagers who had learned to live without me emotionally. Rebuilding those relationships as adults took years of consistent effort and genuine humility. The career success I was so proud of looks different when I weigh what it cost.',
        'advice'   => 'When your children are young, your presence matters in ways you cannot fully appreciate until it is behind you. No promotion compensates for the relationship you do not build with them in those years. Set non-negotiable time boundaries around family and treat them with the same seriousness you treat professional commitments.',
        'quote'    => 'I built a career and missed a childhood. I did not understand the trade until it was too late.',
    ],
    [
        'topic_id' => 6, 'persona_id' => 1,
        'summary'  => 'My parents kept secrets from each other to keep the peace. I learned that pattern and brought it into my own marriage — hiding financial stress, career anxieties, health worries. When my wife discovered the extent of what I had been shielding her from, the breach of trust damaged us for years. Radical honesty, delivered kindly, is the only foundation for genuine intimacy. Protective secrets are not protection.',
        'advice'   => 'Do not protect your partner from difficult truths. Share financial stress, health concerns, and career anxieties. Your partner signed up for a real partner, not a curated performance. The vulnerability required for full honesty is also what creates genuine closeness.',
        'quote'    => 'I thought I was protecting her by keeping secrets. I was just making us strangers.',
    ],
    [
        'topic_id' => 6, 'persona_id' => 3,
        'summary'  => 'The most important parenting insight I have is this: children listen to what you do, not what you say. I told my children to be kind, to be brave, to read widely. I modeled impatience, risk-aversion, and screen addiction. The gap between my words and my behavior was the actual curriculum they received. By the time I recognized this, I had to change my own behavior if I wanted to influence theirs.',
        'advice'   => 'If you want to raise children with certain values, model those values consistently and visibly. Children absorb behavior with extraordinary fidelity. The conversation you have with your spouse in the car when you think they are not listening is the lesson they remember.',
        'quote'    => 'My children did not need a lecture. They needed a demonstration.',
    ],

    // ============================================================
    // MONEY & RETIREMENT
    // ============================================================
    [
        'topic_id' => 7, 'persona_id' => 3,
        'summary'  => 'I retired at 61 and was financially prepared and emotionally completely unprepared. The first year I was lost. I had defined myself entirely through work for forty years and suddenly had no structure, no identity, no sense of purpose. The money was fine. The meaning was gone. I spent two years rebuilding a life that had purpose without employment. Now I volunteer, mentor, travel, and write. But I wish someone had told me to build that life before I retired, not after.',
        'advice'   => 'Retirement planning must include emotional and identity planning, not just financial planning. Ask yourself: who am I without work? What gives me energy and purpose outside of employment? Build activities, relationships, and roles that answer those questions before you retire, not during the existential crisis of your first year.',
        'quote'    => 'I had a perfect retirement account and a completely empty retirement life.',
    ],
    [
        'topic_id' => 7, 'persona_id' => 2,
        'summary'  => 'I went bankrupt at 52 after a business failure and spent three years rebuilding from near zero. What I learned is that financial recovery is primarily psychological, not mathematical. I knew how to earn money — I had done it before. The real battle was overcoming shame, rebuilding confidence, and making decisions from a state of security rather than desperation. The mathematics of recovery took three years. The psychology took six.',
        'advice'   => 'When rebuilding after financial setback, prioritize the psychological recovery alongside the financial one. Shame and desperation lead to poor decisions that extend the recovery. Find a mentor, join a support community, and treat the emotional dimension of financial stress with the same seriousness as the numbers.',
        'quote'    => 'Bankruptcy reset my finances. Rebuilding my confidence was the harder reset.',
    ],
    [
        'topic_id' => 7, 'persona_id' => 1,
        'summary'  => 'I watched my parents live their whole retirement in financial anxiety even though they had enough money. They had grown up in scarcity and never lost the scarcity mindset. They saved adequately but could not enjoy what they had saved. Watching them made me prioritize not just accumulating enough money but developing a healthy relationship with it — practicing generosity, allowing enjoyment, and separating financial security from financial anxiety.',
        'advice'   => 'Work on your relationship with money alongside your savings rate. Financial anxiety that persists regardless of the balance is a psychological issue, not a mathematical one. Develop the mindset of enough before you retire, or the money will not give you what you hoped it would.',
        'quote'    => 'My parents had enough money. They never felt they had enough. I refused to live that way.',
    ],

    // ============================================================
    // TECHNOLOGY & CAREER CHANGE
    // ============================================================
    [
        'topic_id' => 9, 'persona_id' => 2,
        'summary'  => 'Automation eliminated my role at 53. I had spent twenty years in logistics coordination and a new software system replaced most of what I did in six months. I had three choices: fight it, wait for another company to hire me for the same role, or pivot. I chose to pivot — spending eighteen months learning the implementation and management side of the same technology that replaced me. That pivot made me more valuable in the same industry than I had been before.',
        'advice'   => 'When technology threatens your role, the most resilient response is to learn the technology rather than compete against it. The implementation, management, and improvement of automation systems requires human judgment that automation cannot easily replicate. Position yourself as the expert who manages the machines, not as a victim of them.',
        'quote'    => 'Technology replaced my job. I learned that technology and it gave me a better one.',
    ],
    [
        'topic_id' => 9, 'persona_id' => 1,
        'summary'  => 'I am 67 and I have watched technology transform every aspect of how I communicate, work, and learn. The people I know who adapted well to technological change were not the ones who loved technology — they were the ones who stayed curious about why new tools existed and what problems they solved. Curiosity, not aptitude, is the key differentiator.',
        'advice'   => 'Approach every new technology with the question: what problem is this solving and for whom? When you understand the why, learning the how becomes purposeful rather than overwhelming. Curiosity and purpose make technological adaptation manageable at any age.',
        'quote'    => 'I never loved technology. But I always stayed curious about what it was trying to solve.',
    ],

    // ============================================================
    // LESSON I LEARNED — RESILIENCE & FAILURE
    // ============================================================
    [
        'topic_id' => 1, 'persona_id' => 2,
        'summary'  => 'I failed at my first business entirely and publicly. It was humiliating — people who knew me had invested money, and I lost it all. I spent two years convinced I was not an entrepreneur. Then a mentor reframed the failure for me: I had not failed at business, I had failed at that specific business with that specific model at that specific time. The failure was data, not a verdict. I went on to build a successful company ten years later, informed by everything that first failure taught me.',
        'advice'   => 'Treat failures as data points with specific causes, not as verdicts on your character or capability. Ask: what specifically went wrong, what would I do differently, and what did I learn that I could not have learned any other way? Failure that produces learning is expensive education, not a sign you should stop.',
        'quote'    => 'My first business failed completely. It also taught me everything I needed for my second one.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'The hardest lesson I learned was that I am responsible for how I respond to circumstances even when I am not responsible for causing them. A company I worked for went bankrupt through no fault of my own, taking my retirement savings with it. I spent years in bitterness and resentment. The bitterness did not rebuild my savings. Accepting responsibility for my response — not my circumstances — was the day I actually began to recover.',
        'advice'   => 'You cannot control circumstances, only your response to them. When you have been genuinely wronged or genuinely unlucky, the bitterness you hold costs you far more than the event that caused it. Channeling energy from resentment into action is the only practical path forward.',
        'quote'    => 'I was not responsible for what happened. I was responsible for what I did next.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 1,
        'summary'  => 'The most important thing I learned in sixty years is to never make a major decision from a place of fear or desperation. I took the wrong job, stayed in the wrong relationship, and made the wrong financial moves almost exclusively when I was afraid. The decisions I am most proud of were all made from a position of relative calm and clear values. Fear is useful for emergencies, not for strategy.',
        'advice'   => 'Develop the discipline to postpone major decisions when you are afraid, exhausted, or desperate. Make them instead when you are rested, have sought advice, and can connect the decision to your actual values. The quality of your major decisions determines the quality of your life.',
        'quote'    => 'Almost every decision I regret was made from fear. Almost every one I am proud of was made from clarity.',
    ],
    [
        'topic_id' => 1, 'persona_id' => 3,
        'summary'  => 'I spent years seeking validation from people who were not capable of giving it — a father who never approved of my choices, a manager who consistently undermined me. I kept performing for audiences who were not watching and waiting for applause that was never going to come. The day I stopped needing their approval was the day I started building something I was actually proud of.',
        'advice'   => 'Identify whose approval you are seeking and ask honestly whether they are capable of giving you what you need. Some people in your life are not equipped to validate you, and continuing to seek their approval costs you years of misdirected energy. Build your sense of achievement on your own standards first.',
        'quote'    => 'I performed for decades for an audience that was never clapping. I should have performed for myself.',
    ],
];

// ----------------------------------------------------------------
// INSERT ALL STORIES
// ----------------------------------------------------------------
$inserted  = 0;
$failed    = 0;
$chunkRows = 0;

echo "Starting RAG seed v2...\n";
echo "Total stories to insert: " . count($stories) . "\n\n";

foreach ($stories as $idx => $s) {
    try {
        DB::beginTransaction();

        $uuid = makeUUID();

        // Insert interview
        DB::insert(
            "INSERT INTO lg_interviews (uuid, topic_id, persona_id, status, created_at, updated_at)
             VALUES (:uuid, :topic_id, :persona_id, 'completed', NOW(), NOW())",
            ['uuid' => $uuid, 'topic_id' => $s['topic_id'], 'persona_id' => $s['persona_id']]
        );

        $intId = DB::fetch("SELECT interview_id FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $uuid])['interview_id'];

        // Insert consent
        DB::insert(
            "INSERT INTO lg_consents (interview_id, rag_consent, storage_consent, attribution_type, withdrawn, created_at)
             VALUES (:id, 1, 1, 'anonymous', 0, NOW())",
            ['id' => $intId]
        );

        // Build the 3 knowledge chunks
        $chunks = [
            ['type' => 'story_summary',         'text' => $s['summary']],
            ['type' => 'advice',                'text' => $s['advice']],
            ['type' => 'representative_quote',  'text' => $s['quote']],
        ];

        foreach ($chunks as $chunk) {
            DB::insert(
                "INSERT INTO lg_knowledge_chunks
                    (interview_id, content_type, anonymized_text, embedding, embedding_model,
                     status, approved_for_rag, created_at, updated_at)
                 VALUES
                    (:interview_id, :content_type, :text, NULL, 'pending',
                     'approved', 1, NOW(), NOW())",
                [
                    'interview_id' => $intId,
                    'content_type' => $chunk['type'],
                    'text'         => $chunk['text'],
                ]
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

echo "\n=== Seed complete ===\n";
echo "Stories inserted: {$inserted}\n";
echo "Chunks created:   {$chunkRows}\n";
echo "Errors:           {$failed}\n";

// Show updated totals
$newTotal = DB::fetch('SELECT COUNT(*) as cnt FROM lg_knowledge_chunks WHERE status="approved" AND approved_for_rag=1');
echo "\nUpdated total approved RAG chunks: " . $newTotal['cnt'] . "\n";
echo "\nNOTE: Run the embedding generation script to embed the new chunks.\n";
echo "      Until embeddings are generated, semantic search scores will be 0 for new chunks.\n";
echo "      Keyword and theme matching will still work immediately.\n";
