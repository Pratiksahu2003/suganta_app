<?php

namespace Database\Seeders;

use App\Models\Chatbot\ChatbotBotSetting;
use App\Models\Chatbot\ChatbotFaq;
use App\Models\Chatbot\ChatbotIntent;
use App\Models\Chatbot\ChatbotIntentKeyword;
use App\Models\Chatbot\ChatbotIntentResponse;
use App\Models\Chatbot\ChatbotKeyword;
use Illuminate\Database\Seeder;

class ChatbotSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBotSettings();
        $this->seedKeywords();
        $this->seedFaqs();
        $this->seedIntents();
    }

    protected function seedBotSettings(): void
    {
        $settings = [
            [
                'key' => 'bot_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Master switch to enable/disable the chatbot auto-replies.',
            ],
            [
                'key' => 'welcome_message',
                'value' => "Welcome to SuGanta! 🎓\n\nI'm here to help you find the best tutors, courses, and educational opportunities.\n\nHow can I assist you today?\n\n📚 Find a Tutor\n💰 Pricing Info\n📝 Book a Session\n❓ FAQ",
                'type' => 'string',
                'description' => 'Welcome message sent to first-time users.',
            ],
            [
                'key' => 'fallback_message',
                'value' => "Thanks for reaching out! 🙏\n\nI couldn't find a specific answer to your question. Our team will get back to you shortly.\n\nIn the meantime, visit 👉 suganta.com for more info!",
                'type' => 'string',
                'description' => 'Default message when no match is found and AI is disabled.',
            ],
            [
                'key' => 'business_hours',
                'value' => '{"start": "09:00", "end": "21:00", "timezone": "Asia/Kolkata"}',
                'type' => 'json',
                'description' => 'Business hours for human handoff notifications.',
            ],
            [
                'key' => 'max_ai_retries',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Maximum retries for AI fallback before using static fallback.',
            ],
            [
                'key' => 'ai_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable AI-powered responses (Gemini/Grok) as fallback.',
            ],
        ];

        foreach ($settings as $setting) {
            ChatbotBotSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }
    }

    protected function seedKeywords(): void
    {
        $keywords = [
            // ── Greetings (English + Hindi) ──────────────
            ['keyword' => 'hi', 'response' => "Hi there! 👋 Welcome to SuGanta. How can I help you today?", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'hello', 'response' => "Hello! 😊 Welcome to SuGanta. I'm here to help you find the best tutors and courses!", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'hey', 'response' => "Hey! 👋 Thanks for reaching out to SuGanta. How can I assist you?", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'namaste', 'response' => "Namaste! 🙏 SuGanta mein aapka swagat hai. Kaise help kar sakte hain?", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'hii', 'response' => "Hii! 👋 Welcome to SuGanta! How can I help you today?", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'hlo', 'response' => "Hello! 😊 How can SuGanta help you today?", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'good morning', 'response' => "Good morning! ☀️ Welcome to SuGanta. Ready to start your learning journey?", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'good evening', 'response' => "Good evening! 🌙 Welcome to SuGanta. How can I help you today?", 'category' => 'greeting', 'priority' => 100],
            ['keyword' => 'kya hal hai', 'response' => "Sab badhiya! 😊 SuGanta mein aapka swagat hai. Kaise madad kar sakte hain?", 'category' => 'greeting', 'priority' => 95],
            ['keyword' => 'kaise ho', 'response' => "Main bilkul theek! 😊 Aapki kya help kar sakta hoon? Tutor chahiye ya course info?", 'category' => 'greeting', 'priority' => 95],

            // ── Courtesy & Farewell ──────────────────────
            ['keyword' => 'thanks', 'response' => "You're welcome! 😊 Feel free to reach out anytime. Happy learning! 📚", 'category' => 'courtesy', 'priority' => 90],
            ['keyword' => 'thank you', 'response' => "You're welcome! 🎉 We're always here to help. Happy learning with SuGanta!", 'category' => 'courtesy', 'priority' => 90],
            ['keyword' => 'dhanyawad', 'response' => "Dhanyawad aapka bhi! 🙏 SuGanta pe kabhi bhi aayein. Happy learning! 📚", 'category' => 'courtesy', 'priority' => 90],
            ['keyword' => 'shukriya', 'response' => "Shukriya! 😊 SuGanta pe hamesha aapka swagat hai. Visit suganta.com!", 'category' => 'courtesy', 'priority' => 90],
            ['keyword' => 'bye', 'response' => "Goodbye! 👋 All the best with your studies. Visit suganta.com anytime!", 'category' => 'farewell', 'priority' => 90],
            ['keyword' => 'ok', 'response' => "Great! 👍 Let me know if you need anything else. I'm always here to help! 😊", 'category' => 'courtesy', 'priority' => 85],
            ['keyword' => 'okay', 'response' => "Perfect! 👍 Feel free to ask me anything about tutors, courses, or exams!", 'category' => 'courtesy', 'priority' => 85],

            // ── Pricing & Fees ───────────────────────────
            ['keyword' => 'pricing', 'response' => "💰 Our pricing varies by tutor and subject.\n\nVisit 👉 suganta.com/pricing for detailed plans.\n\nOr tell me what subject you're interested in!", 'category' => 'pricing', 'priority' => 80],
            ['keyword' => 'price', 'response' => "💰 For pricing details, visit 👉 suganta.com/pricing\n\nPrices depend on the subject, tutor experience, and session type.", 'category' => 'pricing', 'priority' => 80],
            ['keyword' => 'fees', 'response' => "💰 Fees vary by tutor and subject. Check 👉 suganta.com/pricing for plans.\n\nWant me to find a tutor for a specific subject?", 'category' => 'pricing', 'priority' => 80],
            ['keyword' => 'cost', 'response' => "💰 Typical pricing:\n• Group: ₹200-500/session\n• 1-on-1: ₹500-2000/session\n\nVisit suganta.com/pricing for details!", 'category' => 'pricing', 'priority' => 80],
            ['keyword' => 'kitna paisa', 'response' => "💰 Tutor aur subject ke hisaab se fees alag hoti hai.\n\n• Group class: ₹200-500\n• Private: ₹500-2000\n\nDetails: suganta.com/pricing", 'category' => 'pricing', 'priority' => 80],
            ['keyword' => 'free', 'response' => "✅ Yes! Many tutors offer FREE demo sessions! 🎉\n\nVisit suganta.com, find a tutor, and click 'Book Demo'. No payment needed!", 'category' => 'pricing', 'priority' => 75],

            // ── Subjects & Exams ─────────────────────────
            ['keyword' => 'maths', 'response' => "📐 Looking for a Maths tutor? We have experts for all levels!\n\nFrom Class 1 to competitive exams (JEE/NEET). Find one at 👉 suganta.com", 'category' => 'subjects', 'priority' => 70],
            ['keyword' => 'physics', 'response' => "⚛️ Need a Physics tutor? SuGanta has top-rated Physics teachers!\n\nFor school, JEE, NEET prep. Browse at 👉 suganta.com", 'category' => 'subjects', 'priority' => 70],
            ['keyword' => 'chemistry', 'response' => "🧪 Chemistry tutor needed? We've got you covered!\n\nOrganic, Inorganic, Physical — all levels. Find tutors at 👉 suganta.com", 'category' => 'subjects', 'priority' => 70],
            ['keyword' => 'english', 'response' => "📝 English tutor? We have experts for speaking, writing, grammar & literature!\n\nBrowse English tutors at 👉 suganta.com", 'category' => 'subjects', 'priority' => 70],
            ['keyword' => 'coding', 'response' => "💻 Want to learn coding? We have tutors for Python, Java, C++, Web Dev & more!\n\nStart coding at 👉 suganta.com", 'category' => 'subjects', 'priority' => 70],
            ['keyword' => 'jee', 'response' => "🎯 JEE Preparation? Our expert tutors can help!\n\nJEE Main + Advanced coaching available online & offline.\n\nFind JEE tutors at 👉 suganta.com", 'category' => 'exams', 'priority' => 75],
            ['keyword' => 'neet', 'response' => "🏥 NEET Preparation? We have specialized tutors!\n\nPhysics, Chemistry, Biology — complete NEET coaching.\n\nBrowse NEET tutors at 👉 suganta.com", 'category' => 'exams', 'priority' => 75],
            ['keyword' => 'upsc', 'response' => "🏛️ UPSC Preparation? Our mentors can guide you!\n\nPrelims, Mains, Interview — comprehensive coaching.\n\nFind UPSC mentors at 👉 suganta.com", 'category' => 'exams', 'priority' => 75],

            // ── Platform Features ────────────────────────
            ['keyword' => 'contact', 'response' => "📞 Contact us:\n\n🌐 suganta.com\n📧 support@suganta.co\n\nOr continue chatting here – I'm happy to help!", 'category' => 'support', 'priority' => 70],
            ['keyword' => 'help', 'response' => "I can help you with:\n\n📚 Finding tutors\n💰 Pricing info\n📝 Booking sessions\n🎓 Course details\n\nWhat would you like to know?", 'category' => 'support', 'priority' => 70],
            ['keyword' => 'website', 'response' => "🌐 Visit us at 👉 suganta.com\n\nYou'll find tutors, courses, notes, and much more!", 'category' => 'info', 'priority' => 60],
            ['keyword' => 'app', 'response' => "📱 Download the SuGanta app for the best experience!\n\nAvailable on Android and iOS. Search 'SuGanta' in your app store.", 'category' => 'info', 'priority' => 60],
            ['keyword' => 'booking', 'response' => "📝 To book a session:\n\n1. Visit suganta.com\n2. Find your tutor\n3. Select a time slot\n4. Confirm booking!\n\nIt's that simple! 🎉", 'category' => 'booking', 'priority' => 75],
            ['keyword' => 'book', 'response' => "📝 Want to book a session? Visit suganta.com, find a tutor, and book directly!\n\nDemo sessions are FREE with most tutors! 🆓", 'category' => 'booking', 'priority' => 75],
            ['keyword' => 'notes', 'response' => "📒 Study notes available on SuGanta!\n\nBrowse notes by subject, exam, or class. Some free, some premium.\n\nCheck them out at 👉 suganta.com/notes", 'category' => 'features', 'priority' => 65],
            ['keyword' => 'course', 'response' => "🎓 SuGanta offers courses for various subjects and exams!\n\nVideo courses, live classes, and more.\n\nExplore courses at 👉 suganta.com", 'category' => 'features', 'priority' => 65],
            ['keyword' => 'refund', 'response' => "💰 Refund policy:\n\n• Cancel before session: Full refund\n• Quality issues: Contact support\n\n📧 Email: support@suganta.co for refund requests.", 'category' => 'payments', 'priority' => 70],
            ['keyword' => 'payment', 'response' => "💳 We accept UPI, Cards, Net Banking & Wallets.\n\nAll payments secure via Cashfree. 🔒\n\nNeed help with a payment? Email support@suganta.co", 'category' => 'payments', 'priority' => 70],

            // ── Hindi/Hinglish Common Queries ────────────
            ['keyword' => 'tutor chahiye', 'response' => "📚 Bilkul! Aapko kis subject ka tutor chahiye?\n\nSuGanta pe 1000+ verified tutors hain. Visit 👉 suganta.com", 'category' => 'tutors', 'priority' => 80],
            ['keyword' => 'teacher chahiye', 'response' => "📚 Haan ji! Teacher dhundhne ke liye suganta.com pe jaayein.\n\nSubject, city, aur budget ke hisaab se tutor milega! 🎉", 'category' => 'tutors', 'priority' => 80],
            ['keyword' => 'padhai', 'response' => "📖 Padhai mein help chahiye? SuGanta pe best tutors hain!\n\nSubject bataiye, hum aapko perfect tutor dhundhenge! 💪", 'category' => 'general', 'priority' => 70],
            ['keyword' => 'class', 'response' => "📚 Looking for classes? SuGanta has online + offline options for all subjects!\n\nTell me which class/subject and I'll guide you. 😊", 'category' => 'general', 'priority' => 65],
        ];

        foreach ($keywords as $keyword) {
            ChatbotKeyword::updateOrCreate(
                ['keyword' => $keyword['keyword']],
                array_merge($keyword, ['is_active' => true, 'hit_count' => 0]),
            );
        }
    }

    protected function seedFaqs(): void
    {
        // 1. Core General FAQs
        $coreFaqs = [
            [
                'question' => 'What is SuGanta?',
                'answer'   => "🎓 SuGanta is India's leading education platform connecting students with top tutors, coaching institutes, and global opportunities.\n\nWe help you find the perfect tutor for any subject, exam, or skill.\n\nVisit 👉 suganta.in to get started!",
                'category' => 'general',
                'priority' => 100,
            ],
            [
                'question' => 'How do I find a tutor?',
                'answer'   => "📚 Finding a tutor on SuGanta is easy!\n\n1. Visit suganta.in\n2. Search by subject, exam, or location\n3. Browse tutor profiles and reviews\n4. Book a session directly\n\nOr tell me what subject you need help with!",
                'category' => 'tutors',
                'priority' => 90,
            ],
            [
                'question' => 'How do I book a session?',
                'answer'   => "📝 To book a session:\n\n1. Find a tutor on suganta.in\n2. Check their availability\n3. Select a time slot\n4. Complete the booking\n\nYou can book demo sessions too! Visit 👉 suganta.in/sessions",
                'category' => 'booking',
                'priority' => 90,
            ],
            [
                'question' => 'What subjects are available?',
                'answer'   => "📚 We cover a wide range of subjects:\n\n• Mathematics, Physics, Chemistry, Biology\n• English, Hindi, Languages\n• Computer Science, Coding\n• Competitive Exams (JEE, NEET, UPSC)\n• Music, Art, Sports\n• And many more!\n\nBrowse all at 👉 suganta.in",
                'category' => 'subjects',
                'priority' => 80,
            ],
            [
                'question' => 'How do I become a tutor?',
                'answer'   => "🎓 Want to teach on SuGanta?\n\n1. Register at suganta.in\n2. Complete your tutor profile\n3. Add your subjects and qualifications\n4. Set your pricing and availability\n5. Start getting students!\n\nJoin 1000+ tutors already on the platform!",
                'category' => 'tutors',
                'priority' => 80,
            ],
            [
                'question' => 'Is there a free trial?',
                'answer'   => "✅ Yes! Many tutors offer free demo sessions.\n\nYou can also explore the platform, browse tutors, and access free resources without any payment.\n\nStart at 👉 suganta.in",
                'category' => 'pricing',
                'priority' => 70,
            ],
            [
                'question' => 'What payment methods are accepted?',
                'answer'   => "💳 We accept:\n\n• UPI (GPay, PhonePe, Paytm)\n• Credit/Debit Cards\n• Net Banking\n• Wallets\n\nAll payments are secure and processed through Cashfree.",
                'category' => 'payments',
                'priority' => 70,
            ],
            [
                'question' => 'How do I contact support?',
                'answer'   => "📞 Need help? Reach us via:\n\n🌐 suganta.in/help\n📧 support@suganta.co\n💬 This chat (we're here!)\n\nOur support team typically responds within 24 hours.",
                'category' => 'support',
                'priority' => 90,
            ],
            [
                'question' => 'Can I get a refund?',
                'answer'   => "💰 Refund policy:\n\n• Cancelled sessions before start time: Full refund\n• Quality issues: Contact support for review\n• Subscription cancellation: Pro-rated refund\n\nFor refund requests, email support@suganta.co",
                'category' => 'payments',
                'priority' => 60,
            ],
            [
                'question' => 'Do you offer online classes?',
                'answer'   => "💻 Yes! We offer both:\n\n• Online classes (video call)\n• In-person classes (home tuition)\n\nOnline classes are available for all subjects. Find an online tutor at 👉 suganta.in",
                'category' => 'classes',
                'priority' => 70,
            ],
        ];

        foreach ($coreFaqs as $faq) {
            ChatbotFaq::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['is_active' => true, 'hit_count' => 0]),
            );
        }

        // 2. Procedural Generation for the remaining 1000+ FAQs
        $subjects = ['Maths', 'Physics', 'Chemistry', 'Biology', 'English', 'Hindi', 'Computer Science', 'Coding', 'History', 'Geography', 'Economics', 'Accounts', 'Business Studies', 'Sanskrit', 'French', 'German', 'Physical Education', 'Psychology', 'Sociology', 'Law'];
        $exams = ['JEE Main', 'JEE Advanced', 'NEET', 'UPSC', 'SSC', 'Banking', 'CAT', 'GATE', 'CUET', 'CBSE', 'ICSE', 'State Board', 'IELTS', 'TOEFL', 'GRE', 'GMAT', 'NDA', 'CLAT'];
        $classes = ['Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10', 'Class 11', 'Class 12', 'College', 'University'];
        $locations = ['Delhi', 'Mumbai', 'Bangalore', 'Hyderabad', 'Chennai', 'Kolkata', 'Pune', 'Ahmedabad', 'Jaipur', 'Lucknow', 'Patna', 'Online', 'Chandigarh', 'Bhopal', 'Indore', 'Surat', 'Kanpur'];

        $proceduralFaqs = [];
        $priority = 50;

        foreach ($subjects as $s) {
            $proceduralFaqs[] = ['question' => "Do you have tutors for $s?", 'answer' => "Yes! We have expert tutors for $s. You can find them by searching on suganta.in.", 'category' => 'subjects', 'priority' => $priority];
            $proceduralFaqs[] = ['question' => "What is the fee for $s tuition?", 'answer' => "Fees for $s vary based on tutor experience. Typical rates are ₹200-₹1500 per session. Visit suganta.in/pricing.", 'category' => 'pricing', 'priority' => $priority];
            $proceduralFaqs[] = ['question' => "Can I get a free demo for $s?", 'answer' => "Absolutely! Many of our $s tutors offer a free demo session. Book at suganta.in.", 'category' => 'booking', 'priority' => $priority];
        }

        foreach ($exams as $e) {
            $proceduralFaqs[] = ['question' => "How can I prepare for $e?", 'answer' => "SuGanta offers specialized coaching for $e. We provide top tutors and notes. Search on our platform.", 'category' => 'exams', 'priority' => $priority];
            $proceduralFaqs[] = ['question' => "Do you provide mock tests for $e?", 'answer' => "Yes, many of our tutors include comprehensive mock tests for $e.", 'category' => 'exams', 'priority' => $priority];
        }

        foreach ($classes as $c) {
            foreach (['Maths', 'Science', 'English'] as $s) {
                $proceduralFaqs[] = ['question' => "I need a $s tutor for $c.", 'answer' => "We have many excellent $s tutors teaching $c. Filter by $c on suganta.in to find matches.", 'category' => 'tutors', 'priority' => $priority];
            }
        }

        // Random combinations to reach 1050
        while (count($proceduralFaqs) < 1050) {
            $s = $subjects[array_rand($subjects)];
            $e = $exams[array_rand($exams)];
            $c = $classes[array_rand($classes)];
            $l = $locations[array_rand($locations)];

            $q_types = [
                ["Is there a $s tutor available in $l?", "Yes, we have $s tutors providing classes in $l (both home tuition and online). Check suganta.in for profiles.", "tutors"],
                ["Can you recommend a teacher for $e in $l?", "We have top-rated $e faculty available in $l. Book a demo on suganta.in.", "exams"],
                ["How much does a $c $s tutor charge?", "For $c $s, charges typically range from ₹300 to ₹1000 per session. Visit suganta.in/pricing.", "pricing"],
                ["Where can I find study material for $e?", "We have premium and free study notes for $e on suganta.in/notes.", "materials"],
                ["Do you offer online classes for $c $s?", "Yes, we have 100+ online tutors for $c $s. They use interactive whiteboards for the best experience.", "classes"],
                ["Is home tuition available for $s in $l?", "Yes, many tutors in $l offer home tuition for $s. Just filter by 'Offline' on our platform.", "tutors"],
                ["Can I change my $e tutor if I don't like them?", "Absolutely! You are never locked in. You can switch your $e tutor anytime freely.", "support"],
                ["Are there group classes for $c?", "Yes! Group classes for $c are available and are entirely cost-effective (₹150-₹300/session).", "classes"]
            ];

            $choice = $q_types[array_rand($q_types)];
            
            $proceduralFaqs[] = [
                'question' => $choice[0],
                'answer'   => $choice[1],
                'category' => $choice[2],
                'priority' => rand(10, 40)
            ];
        }

        // Deduplicate by question text
        $uniqueFaqs = [];
        foreach ($proceduralFaqs as $faq) {
            $uniqueFaqs[$faq['question']] = $faq;
        }

        // Ensure we add exactly what is left up to 1000+
        $faqsToInsert = array_slice(array_values($uniqueFaqs), 0, 1000);

        // Chunked insert to avoid memory/query limits
        $chunks = array_chunk($faqsToInsert, 100);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $faq) {
                ChatbotFaq::updateOrCreate(
                    ['question' => $faq['question']],
                    array_merge($faq, ['is_active' => true, 'hit_count' => 0]),
                );
            }
        }
    }

    protected function seedIntents(): void
    {
        $intents = [
            [
                'name' => 'greeting',
                'description' => 'User is greeting or starting a conversation',
                'confidence_threshold' => 0.5,
                'keywords' => [
                    ['keyword' => 'hello', 'weight' => 1.0],
                    ['keyword' => 'hi', 'weight' => 1.0],
                    ['keyword' => 'hey', 'weight' => 1.0],
                    ['keyword' => 'good morning', 'weight' => 1.5],
                    ['keyword' => 'good evening', 'weight' => 1.5],
                    ['keyword' => 'namaste', 'weight' => 1.0],
                    ['keyword' => 'howdy', 'weight' => 0.8],
                ],
                'responses' => [
                    ['response' => "Hello! 👋 Welcome to SuGanta! How can I help you with your learning journey today?", 'priority' => 10],
                    ['response' => "Hi there! 😊 I'm SuGanta's assistant. What can I help you with?", 'priority' => 5],
                ],
            ],
            [
                'name' => 'pricing_query',
                'description' => 'User is asking about pricing, fees, or costs',
                'confidence_threshold' => 0.5,
                'keywords' => [
                    ['keyword' => 'price', 'weight' => 2.0],
                    ['keyword' => 'cost', 'weight' => 2.0],
                    ['keyword' => 'fees', 'weight' => 2.0],
                    ['keyword' => 'pricing', 'weight' => 2.0],
                    ['keyword' => 'charges', 'weight' => 1.5],
                    ['keyword' => 'rate', 'weight' => 1.0],
                    ['keyword' => 'how much', 'weight' => 2.0],
                    ['keyword' => 'expensive', 'weight' => 1.5],
                    ['keyword' => 'affordable', 'weight' => 1.0],
                    ['keyword' => 'kitna', 'weight' => 1.5],
                    ['keyword' => 'paisa', 'weight' => 1.5],
                ],
                'responses' => [
                    ['response' => "💰 Our pricing depends on the subject, tutor experience, and session type.\n\nTypical ranges:\n• Group classes: ₹200-500/session\n• 1-on-1 tutoring: ₹500-2000/session\n\nFor detailed pricing, visit 👉 suganta.com/pricing", 'priority' => 10],
                ],
            ],
            [
                'name' => 'teacher_search',
                'description' => 'User is looking for a tutor or teacher',
                'confidence_threshold' => 0.5,
                'keywords' => [
                    ['keyword' => 'tutor', 'weight' => 2.0],
                    ['keyword' => 'teacher', 'weight' => 2.0],
                    ['keyword' => 'find', 'weight' => 1.0],
                    ['keyword' => 'search', 'weight' => 1.0],
                    ['keyword' => 'looking for', 'weight' => 1.5],
                    ['keyword' => 'need', 'weight' => 0.8],
                    ['keyword' => 'coaching', 'weight' => 1.5],
                    ['keyword' => 'classes', 'weight' => 1.0],
                    ['keyword' => 'tuition', 'weight' => 2.0],
                    ['keyword' => 'sir', 'weight' => 0.5],
                    ['keyword' => 'madam', 'weight' => 0.5],
                ],
                'responses' => [
                    ['response' => "📚 I can help you find the perfect tutor!\n\nTell me:\n1️⃣ What subject do you need?\n2️⃣ What class/level?\n3️⃣ Online or in-person?\n\nOr browse tutors at 👉 suganta.com", 'priority' => 10],
                ],
            ],
            [
                'name' => 'enrollment_interest',
                'description' => 'User wants to enroll, register, or join',
                'confidence_threshold' => 0.5,
                'keywords' => [
                    ['keyword' => 'enroll', 'weight' => 2.0],
                    ['keyword' => 'register', 'weight' => 2.0],
                    ['keyword' => 'join', 'weight' => 1.5],
                    ['keyword' => 'sign up', 'weight' => 2.0],
                    ['keyword' => 'admission', 'weight' => 2.0],
                    ['keyword' => 'start', 'weight' => 0.8],
                    ['keyword' => 'begin', 'weight' => 0.8],
                    ['keyword' => 'interested', 'weight' => 1.5],
                    ['keyword' => 'want to learn', 'weight' => 1.5],
                ],
                'responses' => [
                    ['response' => "🎉 Great! Getting started with SuGanta is easy!\n\n1. Visit 👉 suganta.com\n2. Sign up with your phone number\n3. Browse and connect with tutors\n4. Book your first session\n\nNeed help with anything specific?", 'priority' => 10],
                ],
            ],
            [
                'name' => 'demo_request',
                'description' => 'User wants a demo or trial session',
                'confidence_threshold' => 0.5,
                'keywords' => [
                    ['keyword' => 'demo', 'weight' => 2.5],
                    ['keyword' => 'trial', 'weight' => 2.5],
                    ['keyword' => 'free', 'weight' => 1.5],
                    ['keyword' => 'try', 'weight' => 1.0],
                    ['keyword' => 'sample', 'weight' => 1.5],
                    ['keyword' => 'test class', 'weight' => 2.0],
                ],
                'responses' => [
                    ['response' => "✅ Yes! Many tutors offer FREE demo sessions!\n\nTo book a demo:\n1. Visit 👉 suganta.com\n2. Find a tutor for your subject\n3. Click 'Book Demo'\n\nWant me to help you find a tutor for a specific subject?", 'priority' => 10],
                ],
            ],
            [
                'name' => 'complaint',
                'description' => 'User has a complaint or issue',
                'confidence_threshold' => 0.5,
                'keywords' => [
                    ['keyword' => 'complaint', 'weight' => 2.5],
                    ['keyword' => 'problem', 'weight' => 2.0],
                    ['keyword' => 'issue', 'weight' => 2.0],
                    ['keyword' => 'not working', 'weight' => 2.0],
                    ['keyword' => 'bad', 'weight' => 1.0],
                    ['keyword' => 'frustrated', 'weight' => 1.5],
                    ['keyword' => 'disappointed', 'weight' => 1.5],
                    ['keyword' => 'refund', 'weight' => 2.0],
                    ['keyword' => 'cancel', 'weight' => 1.5],
                ],
                'responses' => [
                    ['response' => "😔 I'm sorry to hear you're having trouble. Let me connect you with our support team.\n\n📧 Email: support@suganta.com\n💬 Or describe your issue here and we'll get back to you ASAP.\n\nYour concern is important to us! 🙏", 'priority' => 10],
                ],
            ],
            [
                'name' => 'goodbye',
                'description' => 'User is ending the conversation',
                'confidence_threshold' => 0.5,
                'keywords' => [
                    ['keyword' => 'bye', 'weight' => 2.0],
                    ['keyword' => 'goodbye', 'weight' => 2.0],
                    ['keyword' => 'see you', 'weight' => 1.5],
                    ['keyword' => 'take care', 'weight' => 1.5],
                    ['keyword' => 'good night', 'weight' => 1.5],
                    ['keyword' => 'later', 'weight' => 0.8],
                    ['keyword' => 'alvida', 'weight' => 1.5],
                ],
                'responses' => [
                    ['response' => "Goodbye! 👋 It was great chatting with you.\n\nRemember, SuGanta is always here to help with your educational journey! 🎓\n\nVisit us anytime at suganta.com 🌐", 'priority' => 10],
                    ['response' => "See you later! 😊 All the best with your studies!\n\n📚 Visit suganta.com for more resources.\n\nFeel free to message anytime!", 'priority' => 5],
                ],
            ],
        ];

        foreach ($intents as $intentData) {
            $intent = ChatbotIntent::updateOrCreate(
                ['name' => $intentData['name']],
                [
                    'description' => $intentData['description'],
                    'confidence_threshold' => $intentData['confidence_threshold'],
                    'is_active' => true,
                ],
            );

            // Sync keywords
            $intent->keywords()->delete();
            foreach ($intentData['keywords'] as $kw) {
                ChatbotIntentKeyword::create([
                    'intent_id' => $intent->id,
                    'keyword' => $kw['keyword'],
                    'weight' => $kw['weight'],
                ]);
            }

            // Sync responses
            $intent->responses()->delete();
            foreach ($intentData['responses'] as $resp) {
                ChatbotIntentResponse::create([
                    'intent_id' => $intent->id,
                    'response' => $resp['response'],
                    'priority' => $resp['priority'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
