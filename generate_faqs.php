<?php
$subjects = ["Maths", "Physics", "Chemistry", "Biology", "English", "Hindi", "Computer Science", "Coding", "History", "Geography", "Economics", "Accounts", "Business Studies", "Sanskrit", "French", "German", "Physical Education", "Psychology", "Sociology", "Law"];
$exams = ["JEE Main", "JEE Advanced", "NEET", "UPSC", "SSC", "Banking", "CAT", "GATE", "CUET", "CBSE", "ICSE", "State Board", "IELTS", "TOEFL", "GRE", "GMAT", "NDA", "CLAT"];
$classes = ["Class 1", "Class 2", "Class 3", "Class 4", "Class 5", "Class 6", "Class 7", "Class 8", "Class 9", "Class 10", "Class 11", "Class 12", "College", "University"];
$locations = ["Delhi", "Mumbai", "Bangalore", "Hyderabad", "Chennai", "Kolkata", "Pune", "Ahmedabad", "Jaipur", "Lucknow", "Patna", "Online", "Chandigarh", "Bhopal", "Indore", "Surat", "Kanpur"];

$faqs = [
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

$questions_added = [];
foreach($faqs as $f) {
    $questions_added[$f['question']] = true;
}

$priority = 50;

// Systematically add questions
foreach ($subjects as $s) {
    $faqs[] = ["question" => "Do you have tutors for $s?", "answer" => "Yes! We have expert tutors for $s. You can find them by searching on suganta.in.", "category" => "subjects", "priority" => $priority];
    $faqs[] = ["question" => "What is the fee for $s tuition?", "answer" => "Fees for $s vary based on tutor experience. Typical rates are ₹200-₹1500 per session. Visit suganta.in/pricing.", "category" => "pricing", "priority" => $priority];
    $faqs[] = ["question" => "Can I get a free demo for $s?", "answer" => "Absolutely! Many of our $s tutors offer a free demo session. Book at suganta.in.", "category" => "booking", "priority" => $priority];
}

foreach ($exams as $e) {
    $faqs[] = ["question" => "How can I prepare for $e?", "answer" => "SuGanta offers specialized coaching for $e. We provide top tutors and notes. Search on our platform.", "category" => "exams", "priority" => $priority];
    $faqs[] = ["question" => "Do you provide mock tests for $e?", "answer" => "Yes, many of our tutors include comprehensive mock tests for $e.", "category" => "exams", "priority" => $priority];
}

foreach ($classes as $c) {
    foreach (["Maths", "Science", "English"] as $s) {
        $faqs[] = ["question" => "I need a $s tutor for $c.", "answer" => "We have many excellent $s tutors teaching $c. Filter by $c on suganta.in to find matches.", "category" => "tutors", "priority" => $priority];
    }
}

// Random generation to reach 1100
while (count($faqs) < 1100) {
    $s = $subjects[array_rand($subjects)];
    $e = $exams[array_rand($exams)];
    $c = $classes[array_rand($classes)];
    $l = $locations[array_rand($locations)];
    
    $q_types = [
        ["Is there a $s tutor available in $l?", "Yes, we have $s tutors providing classes in $l. Check suganta.in for profiles.", "tutors"],
        ["Can you recommend a teacher for $e in $l?", "We have top-rated $e faculty available in $l. Book a demo on suganta.in.", "exams"],
        ["How much does a $c $s tutor charge?", "For $c $s, charges typically range from ₹300 to ₹1000 per session. Visit suganta.in/pricing.", "pricing"],
        ["Where can I find study material for $e?", "We have premium and free study notes for $e on suganta.in/notes.", "materials"],
        ["Do you offer online classes for $c $s?", "Yes, we have 100+ online tutors for $c $s. They use interactive whiteboards for the best experience.", "classes"],
        ["Is home tuition available for $s in $l?", "Yes, many tutors in $l offer home tuition for $s. Just filter by 'Offline' on our platform.", "tutors"],
        ["Can I change my $e tutor if I don't like them?", "Absolutely! You are never locked in. You can switch your $e tutor anytime freely.", "support"],
        ["Are there group classes for $c?", "Yes! Group classes for $c are available and are entirely cost-effective (₹150-₹300/session).", "classes"]
    ];
    
    $choice = $q_types[array_rand($q_types)];
    $q = $choice[0];
    
    if (!isset($questions_added[$q])) {
        $questions_added[$q] = true;
        $faqs[] = [
            "question" => $q,
            "answer" => $choice[1],
            "category" => $choice[2],
            "priority" => rand(10, 40)
        ];
    }
}

// Slice to exactly 1050
$faqs = array_slice($faqs, 0, 1050);

@mkdir(dirname(__DIR__) . '/database/seeders/data', 0777, true);
file_put_contents(dirname(__DIR__) . '/database/seeders/data/faqs.json', json_encode($faqs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Generated " . count($faqs) . " FAQs";
?>
