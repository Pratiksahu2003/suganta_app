<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $blogPost->title }} - {{ $companyInfo['name'] }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px 20px;
        }
        .blog-post {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .blog-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
        }
        .blog-content {
            padding: 20px;
        }
        .blog-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 10px 0;
            line-height: 1.3;
        }
        .blog-meta {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .blog-meta span {
            margin-right: 15px;
        }
        .blog-excerpt {
            color: #495057;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .read-more-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.2s ease;
        }
        .read-more-btn:hover {
            transform: translateY(-2px);
        }
        .cta-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .cta-section h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 18px;
        }
        .cta-section p {
            margin: 0 0 20px 0;
            color: #6c757d;
            font-size: 14px;
        }
        .cta-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .cta-btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .cta-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .cta-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .cta-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        .footer h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .footer p {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.8;
        }
        .footer-links {
            margin: 15px 0;
        }
        .footer-links a {
            color: #74b9ff;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .unsubscribe {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #34495e;
        }
        .unsubscribe a {
            color: #95a5a6;
            text-decoration: none;
            font-size: 12px;
        }
        .unsubscribe a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                box-shadow: none;
            }
            .header, .content, .footer {
                padding: 20px 15px;
            }
            .cta-buttons {
                flex-direction: column;
            }
            .cta-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $companyInfo['name'] }}</h1>
            <p>📚 Your Latest Educational Content is Here!</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h2 style="color: #2c3e50; margin-bottom: 20px;">🎉 New Blog Post Published!</h2>
            
            <div class="blog-post">
                @if($blogPost->featured_image)
                    <img src="{{ asset('storage/' . $blogPost->featured_image) }}" alt="{{ $blogPost->title }}" class="blog-image">
                @else
                    <div class="blog-image" style="display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 48px;">
                        📚
                    </div>
                @endif
                
                <div class="blog-content">
                    <h3 class="blog-title">{{ $blogPost->title }}</h3>
                    
                    <div class="blog-meta">
                        <span>📅 {{ $blogPost->published_at->format('M d, Y') }}</span>
                        <span>⏱️ {{ $blogPost->reading_time_display }}</span>
                        @if($blogPost->author)
                            <span>✍️ {{ $blogPost->author->name }}</span>
                        @endif
                    </div>
                    
                    <div class="blog-excerpt">
                        {{ $blogPost->excerpt }}
                    </div>
                    
                    <a href="{{ route('blog.show', $blogPost->slug) }}" class="read-more-btn">
                        Read Full Article →
                    </a>
                </div>
            </div>

            <!-- Call to Action Section -->
            <div class="cta-section">
                <h3>🚀 Ready to Learn More?</h3>
                <p>Explore our platform and find the perfect tutor for your educational journey!</p>
                <div class="cta-buttons">
                    <a href="{{ route('search.index') }}" class="cta-btn cta-btn-primary">
                        Find Tutors
                    </a>
                    <a href="{{ route('blog.index') }}" class="cta-btn cta-btn-secondary">
                        Browse All Posts
                    </a>
                </div>
            </div>

            <!-- Additional Information -->
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                <h4 style="color: #2c3e50; margin-top: 0;">💡 Why You're Receiving This</h4>
                <p style="margin-bottom: 0; color: #6c757d; font-size: 14px;">
                    You're subscribed to our newsletter and will receive updates about new blog posts, 
                    educational content, and platform features. We're committed to keeping you informed 
                    about the latest in education and tutoring.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <h4>{{ $companyInfo['name'] }}</h4>
            <p>Connecting students with expert tutors for personalized learning experiences.</p>
            
            <div class="footer-links">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('about') }}">About Us</a>
                <a href="{{ route('contact') }}">Contact</a>
                <a href="{{ route('blog.index') }}">Blog</a>
            </div>
            
            <p>
                📧 {{ $companyInfo['email'] }} | 
                📞 {{ $companyInfo['phone'] }} | 
                🌐 {{ $companyInfo['website'] }}
            </p>
            
            <div class="unsubscribe">
                <p>
                    Don't want to receive these emails? 
                    <a href="{{ $unsubscribeUrl }}">Unsubscribe here</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
