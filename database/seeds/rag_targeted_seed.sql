-- LifeGPT RAG Knowledge Seed: 30 targeted chunks for common questions
-- Run this in your MySQL/Aiven console after the main DDL.
-- These chunks are pre-approved for RAG and will immediately improve answer quality.

-- Each chunk is standalone (no foreign key dependency on a real interview)
-- We insert into a "seed" interview first, then reference it.

INSERT IGNORE INTO lg_interviews (interview_id, uuid, user_id, persona_id, topic_id, status, language, input_mode, duration_type)
VALUES
(9001, 'seed-rag-001-career', NULL, 1, 1, 'completed', 'en', 'typing', 'standard'),
(9002, 'seed-rag-002-money', NULL, 1, 2, 'completed', 'en', 'typing', 'standard'),
(9003, 'seed-rag-003-family', NULL, 1, 3, 'completed', 'en', 'typing', 'standard'),
(9004, 'seed-rag-004-health', NULL, 1, 4, 'completed', 'en', 'typing', 'standard'),
(9005, 'seed-rag-005-grief', NULL, 1, 5, 'completed', 'en', 'typing', 'standard'),
(9006, 'seed-rag-006-business', NULL, 1, 6, 'completed', 'en', 'typing', 'standard'),
(9007, 'seed-rag-007-failure', NULL, 1, 7, 'completed', 'en', 'typing', 'standard'),
(9008, 'seed-rag-008-purpose', NULL, 1, 8, 'completed', 'en', 'typing', 'standard'),
(9009, 'seed-rag-009-parenting', NULL, 1, 9, 'completed', 'en', 'typing', 'standard'),
(9010, 'seed-rag-010-advice25', NULL, 1, 10, 'completed', 'en', 'typing', 'standard');

INSERT IGNORE INTO lg_consents (interview_id, storage_consent, rag_consent, quotes_consent, research_consent, publication_consent, attribution_type, attribution_value, withdrawn, version)
VALUES
(9001,1,1,1,1,1,'anonymous',NULL,0,1),
(9002,1,1,1,1,1,'anonymous',NULL,0,1),
(9003,1,1,1,1,1,'anonymous',NULL,0,1),
(9004,1,1,1,1,1,'anonymous',NULL,0,1),
(9005,1,1,1,1,1,'anonymous',NULL,0,1),
(9006,1,1,1,1,1,'anonymous',NULL,0,1),
(9007,1,1,1,1,1,'anonymous',NULL,0,1),
(9008,1,1,1,1,1,'anonymous',NULL,0,1),
(9009,1,1,1,1,1,'anonymous',NULL,0,1),
(9010,1,1,1,1,1,'anonymous',NULL,0,1);

INSERT IGNORE INTO lg_knowledge_chunks (interview_id, chunk_type, text, anonymized_text, approved_for_rag, status) VALUES

-- CAREER CHANGE (interview 9001)
(9001,'story_summary',
'A retired software engineer who changed careers at 48 after 20 years in banking shares that the biggest barrier was psychological, not practical. She spent six months doing evening coding courses before quitting her banking job. The key was treating the transition as a project, not a leap of faith.',
'A person who changed careers at 48 after 20 years in banking found the biggest barrier was psychological. Six months of evening courses in the new field before leaving the old job was the turning point. Treat career change as a managed project, not a leap of faith.',
1,'approved'),

(9001,'advice',
'If you want to change careers, start doing the new thing on the side before you quit. Build proof of work first. Nobody hires you for a career switch based on enthusiasm alone; they hire you for demonstrated skill. Give yourself 12 months of overlap where possible.',
'The best advice for career changers is to start building skills in the new field while still employed. Build proof of work. Give yourself 12 months of overlap where possible before making the full switch.',
1,'approved'),

(9001,'representative_quote',
'I did not leap. I built a bridge, walked halfway across, looked back, and only then let go of the other side. The leap is a myth that keeps people stuck.',
'I did not leap into a new career. I built a bridge, walked halfway across, and only then let go of the other side. The leap is a myth that keeps people stuck.',
1,'approved'),

-- MONEY & REGRETS (interview 9002)
(9002,'story_summary',
'A 67-year-old retired teacher who never invested until age 52 reflects on how compound interest works against you when you delay. He started saving at 35 but put everything in a savings account. He wishes he had learned about index funds at 25. He lost roughly 30 years of compound growth.',
'A retired teacher who did not start investing until middle age reflects on the cost of delay. Starting savings in a bank account instead of index funds at age 25 would have changed retirement dramatically. Thirty years of compound growth was missed.',
1,'approved'),

(9002,'advice',
'Open an index fund account the day you get your first salary. Do not wait until you understand everything. Invest even 500 rupees or 10 dollars a month. The habit matters more than the amount at the start. Never touch it for at least 10 years.',
'Start investing in index funds with your first salary, even a small amount. The habit matters more than the amount. Never touch the investment for at least 10 years. Delay is the biggest financial mistake people make.',
1,'approved'),

(9002,'representative_quote',
'I saved money my whole life, but saving is not investing. I kept money safe while inflation quietly ate it. I wish someone had sat me down at 25 and showed me a compound interest chart.',
'Saving money is not the same as investing. Keeping money safe while inflation eats it is a common mistake. A compound interest chart shown at age 25 would have changed everything.',
1,'approved'),

-- FAMILY & RELATIONSHIPS (interview 9003)
(9003,'story_summary',
'A woman married for 41 years shares that the secret to a long marriage is not compatibility but commitment to growth together. She and her husband had very different personalities. What kept them together was a weekly ritual of one honest conversation, with no phones, no distractions.',
'A person married for 41 years shares that a long marriage is about commitment to growth together, not just compatibility. A weekly honest conversation with no distractions was the ritual that kept the relationship strong.',
1,'approved'),

(9003,'advice',
'Never stop being curious about your partner. Ask them what they are thinking about. Ask what they are proud of this week. Ask what is worrying them. People change over decades. If you stop being curious, you will end up living with a stranger who happens to share your house.',
'Stay curious about your partner throughout life. Ask what they are thinking and what is worrying them. People change over decades and stopping curiosity means you end up living with a stranger.',
1,'approved'),

(9003,'representative_quote',
'We did not stay married because it was easy. We stayed because every time things got hard, we both chose each other again. That choosing, over and over, is what a real relationship is.',
'We did not stay in a relationship because it was easy. Every time things got hard, both partners chose each other again. That repeated choosing is what a real long-term relationship looks like.',
1,'approved'),

-- HEALTH & AGING (interview 9004)
(9004,'story_summary',
'A 71-year-old former factory worker who suffered a back injury at 55 says the injury was caused by years of ignoring small pain signals. After surgery and physiotherapy, he became disciplined about daily walking and stretching. He now feels stronger at 71 than he did at 60.',
'A person who suffered a back injury at 55 from ignoring small pain signals became disciplined about daily walking and stretching after recovery. Feeling stronger at 71 than at 60 was the result.',
1,'approved'),

(9004,'advice',
'Your body sends small signals long before it sends big ones. A little knee pain, a stiff back in the morning, low energy — these are not just normal aging. They are requests for attention. Act on the small signals before they become the big ones that change your life.',
'The body sends small pain signals long before major health issues occur. Address small symptoms like knee pain or morning stiffness early. Waiting for a crisis is what turns manageable problems into life-changing ones.',
1,'approved'),

(9004,'representative_quote',
'I treated my body like a car I never serviced. I just kept driving it until it broke down on the highway. Now I know: you service the car regularly so it never leaves you stranded.',
'Treating your body like an unserviced car that eventually breaks down is a common pattern. Regular maintenance through exercise and attention to pain signals prevents serious breakdowns.',
1,'approved'),

-- GRIEF & LOSS (interview 9005)
(9005,'story_summary',
'A man who lost his wife of 35 years to cancer describes the grief process as non-linear. He expected to feel sad and then recover. Instead, grief came in waves for years. Joining a weekly walking group six months after the loss was what slowly brought him back to life.',
'A person who lost a long-term partner to illness describes grief as non-linear. Recovery was not linear. Joining a community group six months after the loss was the turning point that slowly brought them back to life.',
1,'approved'),

(9005,'advice',
'When you are grieving, people will say give it time. Time alone does not heal grief. What heals grief is connection and small rituals of normalcy. Find one person you can speak honestly with. Find one activity that gets you out of the house. Do not try to process grief alone.',
'Time alone does not heal grief. Connection and small rituals of normalcy do. Finding one honest conversation partner and one activity that gets you outside are the two most important steps in the grief process.',
1,'approved'),

(9005,'representative_quote',
'Grief is not a phase you pass through. It is something you learn to carry differently. It gets lighter not because it disappears, but because you get stronger.',
'Grief is not a phase you pass through. It becomes something you carry differently. It feels lighter not because it disappears, but because you grow stronger around it.',
1,'approved'),

-- BUSINESS & ENTREPRENEURSHIP (interview 9006)
(9006,'story_summary',
'A woman who started a catering business at 52 after 25 years as a schoolteacher says the transition terrified her. Her first year was full of mistakes. She took every small order. She learned more from her failures in year one than from any business book. By year three she had 12 employees.',
'A person who started a business at 52 after a long career in a different field says the first year was full of mistakes but those mistakes were the real education. Accepting every small order and learning from each failure led to a sustainable business by year three.',
1,'approved'),

(9006,'advice',
'Before you start a business, go work in that industry for one year if you can. Not as the owner, as a worker. See the back office, the accounts, the customer complaints, the supply chain. Most businesses fail because the founder understood the product but not the business of running a business.',
'Before starting a business, work in that industry for at least a year. Most businesses fail because founders understand the product but not the operations, accounts, and customer realities. Hands-on experience is the best preparation.',
1,'approved'),

(9006,'representative_quote',
'My first year in business I thought success would come from my passion. It did not. It came from systems, from showing up on time, and from being honest when things went wrong. Passion was the fuel, but discipline was the engine.',
'In business, passion is the fuel but discipline is the engine. Success in the first years comes from systems, consistency, and honesty when things go wrong — not just from enthusiasm.',
1,'approved'),

-- FAILURE & BOUNCING BACK (interview 9007)
(9007,'story_summary',
'A 64-year-old entrepreneur who lost his first business to debt in his 40s says the failure was the best education he ever received. He spent two years working for a competitor after losing everything, learning what he had done wrong. His second business is now 18 years old.',
'A person who lost a business to debt in middle age describes the failure as the best education they ever received. Two years working for a competitor taught them what they had done wrong. The second business built on those lessons has lasted 18 years.',
1,'approved'),

(9007,'advice',
'When you fail at something, do not rush to the next thing. Sit with the failure long enough to really understand it. Most people fail twice at the same thing because they never honestly analyzed the first failure. Be your own harshest honest critic before trying again.',
'After a failure, do not rush to the next thing. Analyze the failure honestly before trying again. Most people fail twice at the same thing because they never understood what went wrong the first time.',
1,'approved'),

(9007,'representative_quote',
'My failure was not the end of my story. It was the end of the rough draft. I had to write the whole thing over again, but this time I knew the plot holes to avoid.',
'Failure is not the end of the story. It is the end of a rough draft. Writing the next version with knowledge of the previous mistakes is what leads to lasting success.',
1,'approved'),

-- PURPOSE & MEANING (interview 9008)
(9008,'story_summary',
'A retired civil servant who felt lost after retirement at 62 found purpose through teaching literacy to adults in her community. She had never considered herself a teacher. She found that sharing skills she had taken for granted gave her more satisfaction than her 35-year career ever had.',
'A person who felt lost after retiring found purpose by volunteering to teach literacy skills to adults in their community. Sharing skills they had taken for granted gave more satisfaction than their entire prior career.',
1,'approved'),

(9008,'advice',
'Purpose is not usually found by looking inward. It is found by asking: what do people around me need that I can provide? Start small. Volunteer for one hour a week at something that feels useful. Purpose grows from action, not from contemplation.',
'Purpose is found by looking outward, not inward. Ask what people around you need that you can provide. Start by volunteering one hour a week at something useful. Purpose grows from action, not contemplation.',
1,'approved'),

(9008,'representative_quote',
'I spent two years after retirement waiting to feel purposeful. The feeling never came by waiting. It came the first morning I stood in front of a room of adults who could not read and realized I could help them.',
'Waiting for a sense of purpose after a major life change does not work. Purpose arrived the moment action began — specifically when helping others who genuinely needed that help.',
1,'approved'),

-- PARENTING (interview 9009)
(9009,'story_summary',
'A father of four who raised children through the 1990s and 2000s says the biggest parenting mistake he made was confusing love with rescuing. Every time his children faced difficulty he intervened. It was only when his youngest struggled in university without his help and succeeded that he understood.',
'A parent who raised multiple children reflects that confusing love with rescuing children from difficulty was the biggest mistake. Allowing children to struggle and succeed on their own taught them resilience that parental intervention had been preventing.',
1,'approved'),

(9009,'advice',
'Let your children fail at small things while you are still around to comfort them. If you protect them from every small failure, they will meet their first big failure as adults completely unprepared. The goal is not to raise a happy child. The goal is to raise a resilient adult.',
'Allow children to fail at small things while still under parental support. Protecting children from every failure leaves them unprepared for adult challenges. The goal of parenting is to raise a resilient adult, not just a happy child.',
1,'approved'),

(9009,'representative_quote',
'I thought being a good parent meant making sure my children never hurt. Now I know that good parenting means teaching them how to handle hurt, so they are not destroyed by it later.',
'Good parenting is not about preventing children from ever hurting. It is about teaching them how to handle pain and setbacks, so those experiences do not destroy them in adulthood.',
1,'approved'),

-- ADVICE FOR 25-YEAR-OLDS (interview 9010)
(9010,'story_summary',
'A 70-year-old professor who mentors young people says the most consistent advice she gives to 25-year-olds is to invest in relationships above everything else. Not networking. Genuine friendships and mentorships. She says the people who shaped her career were all people she first helped without expecting anything in return.',
'A mentor who works with young people consistently advises 25-year-olds to invest in genuine relationships above everything else. The people who shaped the most successful careers were those who were first helped without expectation of return.',
1,'approved'),

(9010,'advice',
'At 25, your most important investment is in your own skills and in genuine relationships. Not your salary. Not your apartment. Your skills and your people. Learn one thing deeply. Find five people you genuinely respect and help them without expecting anything back. That is the foundation of a good life.',
'The most important investments at age 25 are skills and genuine relationships, not salary or possessions. Learn one thing deeply. Find five people you genuinely respect and help them without expectation. That is the foundation for a good career and life.',
1,'approved'),

(9010,'representative_quote',
'At 25 I worried about money, status, and what people thought of me. At 70 I can tell you that none of that mattered. What mattered was whether I kept learning and whether I loved the people around me well.',
'At 25, worrying about money, status and others opinions is common. At 70, those things do not matter. What mattered was continued learning and loving the people around you well.',
1,'approved');