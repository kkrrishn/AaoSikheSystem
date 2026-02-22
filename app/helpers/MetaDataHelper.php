<?php
declare(strict_types=1);
namespace App\helpers;


class MetaDataHelper
{
    private static array $metadata = [];
    private static array $openGraph = [];
    private static array $twitterCard = [];
    private static array $structuredData = [];
    private static array $breadcrumbs = [];
    private static array $canonical = [];
    private static string $baseUri = '';
    private static string $assetsPath = '';
    private static string $version = '1.0.0';
    private static array $pageConfigs = [];

    /**
     * Initialize the helper with base configuration
     */
    public static function init(string $baseUri = '', string $assetsPath = '/public/assets/', string $version = '1.0.0'): void
    {
        self::$baseUri = rtrim($baseUri, '/');
        self::$assetsPath = rtrim($assetsPath, '/');
        self::$version = $version;

        // Default page configurations
        self::$pageConfigs = [
            'home' => [
                'title' => 'EduLearn - Online Learning Platform',
                'description' => 'Learn from industry experts with our comprehensive online courses. Gain in-demand skills and advance your career with EduLearn.',
                'keywords' => ['online courses', 'e-learning', 'skill development', 'career growth', 'professional training'],
                'image' => 'home-og.jpg',
                'type' => 'website'
            ],
            'courses' => [
                'title' => 'Online Courses | EduLearn',
                'description' => 'Browse our catalog of expert-led courses in technology, business, design, and more. Start learning today!',
                'keywords' => ['technology courses', 'business courses', 'design courses', 'certification programs', 'skill training'],
                'image' => 'courses-og.jpg',
                'type' => 'website'
            ],
            'contact' => [
                'title' => 'Contact Us | EduLearn - Get in Touch',
                'description' => 'Contact EduLearn for course inquiries, technical support, pricing, and corporate training. Available Monday-Friday, 9am-6pm PST.',
                'keywords' => ['contact edulearn', 'support', 'course inquiry', 'technical help', 'customer service'],
                'image' => 'contact-og.jpg',
                'type' => 'website'
            ],
            'about' => [
                'title' => 'About Us | EduLearn - Our Mission',
                'description' => 'Learn about EduLearn\'s mission to make quality education accessible to everyone. Meet our team and discover our story.',
                'keywords' => ['about edulearn', 'our mission', 'company story', 'team members', 'education platform'],
                'image' => 'about-og.jpg',
                'type' => 'website'
            ],
            'blog' => [
                'title' => 'Blog & Articles | EduLearn',
                'description' => 'Read expert articles, learning tips, industry insights, and educational resources on our blog.',
                'keywords' => ['education blog', 'learning tips', 'industry insights', 'expert articles', 'educational resources'],
                'image' => 'blog-og.jpg',
                'type' => 'website'
            ],
            'course_detail' => [
                'title' => '{{course_title}} | EduLearn',
                'description' => '{{course_description}}',
                'keywords' => ['{{course_category}}', 'online course', 'skill development'],
                'image' => '{{course_image}}',
                'type' => 'article'
            ],
            'article_detail' => [
                'title' => '{{article_title}} | EduLearn Blog',
                'description' => '{{article_excerpt}}',
                'keywords' => ['{{article_tags}}'],
                'image' => '{{article_image}}',
                'type' => 'article'
            ]
        ];

        // Initialize default metadata
        self::reset();
    }

    /**
     * Reset metadata to defaults
     */
    public static function reset(): void
    {
        self::$metadata = [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'author' => 'EduLearn',
            'robots' => 'index, follow',
            'viewport' => 'width=device-width, initial-scale=1.0',
            'charset' => 'UTF-8',
            'language' => 'en',
            'theme_color' => '#0066cc',
            'color_scheme' => 'light',
            'generator' => 'AaoSikheSystem'
        ];

        self::$openGraph = [
            'og:type' => 'website',
            'og:site_name' => 'EduLearn',
            'og:locale' => 'en_US',
            'og:image:type' => 'image/jpeg',
            'og:image:width' => '1200',
            'og:image:height' => '630',
            'og:image:secure_url' => ''
        ];

        self::$twitterCard = [
            'twitter:card' => 'summary_large_image',
            'twitter:site' => '@edulearn',
            'twitter:creator' => '@edulearn'
        ];

        self::$structuredData = [];
        self::$breadcrumbs = [];
        self::$canonical = [];
    }

    /**
     * Setup metadata for a specific page type
     */
    public static function setupPage(string $pageType, array $data = [], string $canonicalUrl = ''): void
    {
        self::reset();
        
        if (!isset(self::$pageConfigs[$pageType])) {
            $pageType = 'home';
        }

        $config = self::$pageConfigs[$pageType];
        
        // Replace placeholders with actual data
        $title = self::replacePlaceholders($config['title'], $data);
        $description = self::replacePlaceholders($config['description'], $data);
        $keywords = self::replacePlaceholdersArray($config['keywords'], $data);
        $image = self::replacePlaceholders($config['image'], $data);
        $type = $config['type'];

        // Set metadata
        self::setTitle($title);
        self::setDescription($description);
        self::setKeywords($keywords);
        self::setOgType($type);
        
        // Set image if available
        if ($image) {
            $fullImagePath = self::getAssetUrl($image);
            self::setOgImage($fullImagePath, $title);
        }

        // Set canonical URL
        if ($canonicalUrl) {
            self::setCanonical($canonicalUrl);
        } else {
            self::setCanonical(self::$baseUri . '/' . $pageType);
        }

        // Add default breadcrumb
        self::addBreadcrumb('Home', self::$baseUri . '/', 1);
        
        // Generate structured data based on page type
        self::generatePageStructuredData($pageType, $data);
    }

    /**
     * Setup contact page with specific data
     */
    public static function setupContactPage(array $contactInfo = [], array $faqs = [], string $canonicalUrl = ''): void
    {
        self::setupPage('contact', [], $canonicalUrl);
        
        // Add contact structured data
        $structuredContactData = self::generateContactStructuredData($contactInfo);
        if ($structuredContactData) {
            self::$structuredData['contact'] = $structuredContactData;
        }
        
        // Add FAQ structured data
        if (!empty($faqs)) {
            $faqData = self::generateFaqStructuredData($faqs);
            if ($faqData) {
                self::$structuredData['faq'] = $faqData;
            }
        }
    }

    /**
     * Setup course detail page
     */
    public static function setupCoursePage(array $courseData, string $canonicalUrl = ''): void
    {
        $data = [
            'course_title' => $courseData['title'] ?? '',
            'course_description' => $courseData['description'] ?? '',
            'course_category' => $courseData['category'] ?? '',
            'course_image' => $courseData['image'] ?? 'course-default.jpg',
            'course_price' => $courseData['price'] ?? '',
            'course_rating' => $courseData['rating'] ?? '',
            'course_instructor' => $courseData['instructor'] ?? '',
            'course_duration' => $courseData['duration'] ?? ''
        ];

        self::setupPage('course_detail', $data, $canonicalUrl);
        
        // Add course structured data
        $structuredCourseData = self::generateCourseStructuredData($courseData);
        if ($structuredCourseData) {
            self::$structuredData['course'] = $structuredCourseData;
        }
    }

    /**
     * Setup article/blog detail page
     */
    public static function setupArticlePage(array $articleData, string $canonicalUrl = ''): void
    {
        $data = [
            'article_title' => $articleData['title'] ?? '',
            'article_excerpt' => $articleData['excerpt'] ?? '',
            'article_tags' => $articleData['tags'] ?? '',
            'article_image' => $articleData['image'] ?? 'article-default.jpg',
            'article_author' => $articleData['author'] ?? '',
            'article_published_date' => $articleData['published_date'] ?? '',
            'article_modified_date' => $articleData['modified_date'] ?? ''
        ];

        self::setupPage('article_detail', $data, $canonicalUrl);
        
        // Add article structured data
        $structuredArticleData = self::generateArticleStructuredData($articleData);
        if ($structuredArticleData) {
            self::$structuredData['article'] = $structuredArticleData;
        }
    }

    /**
     * Set page title
     */
    public static function setTitle(string $title): void
    {
        self::$metadata['title'] = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        self::$openGraph['og:title'] = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        self::$twitterCard['twitter:title'] = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set page description
     */
    public static function setDescription(string $description): void
    {
        $desc = htmlspecialchars(substr(strip_tags($description), 0, 160), ENT_QUOTES, 'UTF-8');
        self::$metadata['description'] = $desc;
        self::$openGraph['og:description'] = $desc;
        self::$twitterCard['twitter:description'] = $desc;
    }

    /**
     * Set page keywords
     */
    public static function setKeywords(array $keywords): void
    {
        $keywords = array_map('htmlspecialchars', $keywords);
        self::$metadata['keywords'] = implode(', ', $keywords);
    }

    /**
     * Set page author
     */
    public static function setAuthor(string $author): void
    {
        self::$metadata['author'] = htmlspecialchars($author, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set robots meta tag
     */
    public static function setRobots(string $robots): void
    {
        self::$metadata['robots'] = $robots;
    }

    /**
     * Set canonical URL
     */
    public static function setCanonical(string $url): void
    {
        if (strpos($url, 'http') !== 0) {
            $url = self::$baseUri . '/' . ltrim($url, '/');
        }
        
        self::$canonical = [
            'url' => $url,
            'absolute' => true
        ];
        
        self::$openGraph['og:url'] = $url;
    }

    /**
     * Set Open Graph image
     */
    public static function setOgImage(string $imageUrl, string $alt = ''): void
    {
        if (strpos($imageUrl, 'http') !== 0) {
            $imageUrl = self::$baseUri . '/' . ltrim($imageUrl, '/');
        }
        
        self::$openGraph['og:image'] = $imageUrl;
        self::$openGraph['og:image:alt'] = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        self::$twitterCard['twitter:image'] = $imageUrl;
        self::$twitterCard['twitter:image:alt'] = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set Open Graph type
     */
    public static function setOgType(string $type): void
    {
        $validTypes = ['website', 'article', 'book', 'profile', 'music.song', 'music.album', 
                      'music.playlist', 'music.radio_station', 'video.movie', 'video.episode', 
                      'video.tv_show', 'video.other'];
        
        if (in_array($type, $validTypes)) {
            self::$openGraph['og:type'] = $type;
        }
    }

    /**
     * Add breadcrumb item
     */
    public static function addBreadcrumb(string $name, string $url, int $position): void
    {
        if (strpos($url, 'http') !== 0) {
            $url = self::$baseUri . '/' . ltrim($url, '/');
        }
        
        self::$breadcrumbs[] = [
            'position' => $position,
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'url' => $url
        ];
        
        // Sort by position
        usort(self::$breadcrumbs, function($a, $b) {
            return $a['position'] <=> $b['position'];
        });
    }

    /**
     * Generate structured data based on page type
     */
    private static function generatePageStructuredData(string $pageType, array $data = []): void
    {
        // Always add organization and website structured data
        self::$structuredData['organization'] = self::generateOrganizationStructuredData();
        self::$structuredData['website'] = self::generateWebsiteStructuredData();
        
        // Add breadcrumb structured data
        $breadcrumbData = self::generateBreadcrumbStructuredData();
        if ($breadcrumbData) {
            self::$structuredData['breadcrumb'] = $breadcrumbData;
        }
    }

    /**
     * Generate contact page structured data
     */
    public static function generateContactStructuredData(array $contactInfo = []): array
    {
        $defaultContactInfo = [
            'phone' => '+1 (555) 123-4567',
            'email' => 'support@edulearn.com',
            'street' => '123 Learning Street',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip' => '94107',
            'country' => 'US',
            'areaServed' => 'Worldwide'
        ];
        
        $contactInfo = array_merge($defaultContactInfo, $contactInfo);
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => self::$metadata['title'] ?? 'Contact Us',
            'description' => self::$metadata['description'] ?? '',
            'url' => self::$canonical['url'] ?? self::$baseUri . '/contact',
            'mainEntity' => [
                '@type' => 'Organization',
                'name' => 'EduLearn',
                'url' => self::$baseUri,
                'logo' => self::getAssetUrl('/images/logo.png'),
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => $contactInfo['phone'],
                    'contactType' => 'customer service',
                    'email' => $contactInfo['email'],
                    'areaServed' => $contactInfo['areaServed'],
                    'availableLanguage' => ['English', 'Spanish'],
                    'hoursAvailable' => [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                        'opens' => '09:00',
                        'closes' => '18:00'
                    ]
                ],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $contactInfo['street'],
                    'addressLocality' => $contactInfo['city'],
                    'addressRegion' => $contactInfo['state'],
                    'postalCode' => $contactInfo['zip'],
                    'addressCountry' => $contactInfo['country']
                ]
            ]
        ];
    }

    /**
     * Generate FAQ structured data
     */
    public static function generateFaqStructuredData(array $faqs): array
    {
        $items = [];
        
        foreach ($faqs as $index => $faq) {
            if (!isset($faq['question'], $faq['answer'])) {
                continue;
            }
            
            $items[] = [
                '@type' => 'Question',
                'name' => htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => htmlspecialchars(strip_tags($faq['answer']), ENT_QUOTES, 'UTF-8')
                ]
            ];
        }
        
        if (empty($items)) {
            return [];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $items
        ];
    }

    /**
     * Generate course structured data
     */
    public static function generateCourseStructuredData(array $courseData): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => htmlspecialchars($courseData['title'] ?? '', ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($courseData['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'provider' => [
                '@type' => 'Organization',
                'name' => 'EduLearn',
                'sameAs' => self::$baseUri
            ],
            'courseCode' => $courseData['code'] ?? '',
            'educationalCredentialAwarded' => $courseData['credential'] ?? 'Certificate of Completion',
            'timeRequired' => $courseData['duration'] ?? 'P1M',
            'hasCourseInstance' => [
                '@type' => 'CourseInstance',
                'courseMode' => 'online',
                'courseWorkload' => $courseData['workload'] ?? ''
            ]
        ];
        
        if (isset($courseData['price'])) {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => $courseData['price'],
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'url' => self::$canonical['url'] ?? self::$baseUri
            ];
        }
        
        if (isset($courseData['instructor'])) {
            $data['instructor'] = [
                '@type' => 'Person',
                'name' => $courseData['instructor']
            ];
        }
        
        if (isset($courseData['rating'])) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $courseData['rating'],
                'ratingCount' => $courseData['rating_count'] ?? 100,
                'bestRating' => '5',
                'worstRating' => '1'
            ];
        }
        
        return $data;
    }

    /**
     * Generate article structured data
     */
    public static function generateArticleStructuredData(array $articleData): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => htmlspecialchars($articleData['title'] ?? '', ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($articleData['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'),
            'image' => self::getAssetUrl($articleData['image'] ?? 'article-default.jpg'),
            'author' => [
                '@type' => 'Person',
                'name' => $articleData['author'] ?? 'EduLearn Team'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'EduLearn',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::getAssetUrl('/images/logo.png')
                ]
            ],
            'datePublished' => $articleData['published_date'] ?? date('Y-m-d'),
            'dateModified' => $articleData['modified_date'] ?? date('Y-m-d'),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => self::$canonical['url'] ?? self::$baseUri
            ]
        ];
        
        if (isset($articleData['keywords']) && is_array($articleData['keywords'])) {
            $data['keywords'] = implode(', ', $articleData['keywords']);
        }
        
        return $data;
    }

    /**
     * Generate breadcrumb structured data
     */
    private static function generateBreadcrumbStructuredData(): array
    {
        if (empty(self::$breadcrumbs)) {
            return [];
        }
        
        $items = [];
        
        foreach (self::$breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url']
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }

    /**
     * Generate organization structured data
     */
    public static function generateOrganizationStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'EduLearn',
            'url' => self::$baseUri,
            'logo' => self::getAssetUrl('/images/logo.png'),
            'sameAs' => [
                'https://facebook.com/edulearn',
                'https://twitter.com/edulearn',
                'https://linkedin.com/company/edulearn',
                'https://instagram.com/edulearn'
            ],
            'contactPoint' => [
                [
                    '@type' => 'ContactPoint',
                    'telephone' => '+1 (555) 123-4567',
                    'contactType' => 'customer service',
                    'email' => 'support@edulearn.com',
                    'availableLanguage' => 'English'
                ]
            ]
        ];
    }

    /**
     * Generate WebSite structured data
     */
    public static function generateWebsiteStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'EduLearn',
            'url' => self::$baseUri,
            'potentialAction' => [
                [
                    '@type' => 'SearchAction',
                    'target' => self::$baseUri . '/search?q={search_term_string}',
                    'query-input' => 'required name=search_term_string'
                ]
            ]
        ];
    }

    /**
     * Render all meta tags
     */
    public static function renderMetaTags(): string
    {
        $html = [];
        
        // Basic meta tags
        foreach (self::$metadata as $name => $content) {
            if (!empty($content)) {
                if ($name === 'charset') {
                    $html[] = "<meta charset=\"{$content}\">";
                } elseif ($name === 'viewport') {
                    $html[] = "<meta name=\"viewport\" content=\"{$content}\">";
                } else {
                    $html[] = "<meta name=\"{$name}\" content=\"{$content}\">";
                }
            }
        }
        
        // Theme color
        $html[] = "<meta name=\"theme-color\" content=\"" . self::$metadata['theme_color'] . "\">";
        $html[] = "<meta name=\"color-scheme\" content=\"" . self::$metadata['color_scheme'] . "\">";
        
        // Open Graph
        foreach (self::$openGraph as $property => $content) {
            if (!empty($content)) {
                if (is_array($content)) {
                    foreach ($content as $item) {
                        $html[] = "<meta property=\"{$property}\" content=\"{$item}\">";
                    }
                } else {
                    $html[] = "<meta property=\"{$property}\" content=\"{$content}\">";
                }
            }
        }
        
        // Twitter Card
        foreach (self::$twitterCard as $name => $content) {
            if (!empty($content)) {
                $html[] = "<meta name=\"{$name}\" content=\"{$content}\">";
            }
        }
        
        // Canonical URL
        if (!empty(self::$canonical) && !empty(self::$canonical['url'])) {
            $html[] = "<link rel=\"canonical\" href=\"" . self::$canonical['url'] . "\">";
        }
        
        return implode("\n    ", $html);
    }

    /**
     * Render structured data as JSON-LD
     */
    public static function renderStructuredData(): string
    {
        if (empty(self::$structuredData)) {
            return '';
        }
        
        $html = [];
        
        foreach (self::$structuredData as $data) {
            if (!empty($data)) {
                $html[] = '<script type="application/ld+json">';
                $html[] = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $html[] = '</script>';
            }
        }
        
        return implode("\n    ", $html);
    }

    /**
     * Render favicon links
     */
    public static function renderFavicons(): string
    {
        $html = [];
        
        $faviconPath = self::$assetsPath . '/favicon';
        
        $favicons = [
            ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => $faviconPath . '/apple-touch-icon.png'],
            ['rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => $faviconPath . '/favicon-32x32.png'],
            ['rel' => 'icon', 'type' => 'image/png', 'sizes' => '16x16', 'href' => $faviconPath . '/favicon-16x16.png'],
            ['rel' => 'manifest', 'href' => $faviconPath . '/site.webmanifest'],
            ['rel' => 'mask-icon', 'href' => $faviconPath . '/safari-pinned-tab.svg', 'color' => '#0066cc']
        ];
        
        foreach ($favicons as $favicon) {
            $attrs = '';
            foreach ($favicon as $key => $value) {
                $attrs .= " {$key}=\"{$value}\"";
            }
            $html[] = "<link{$attrs}>";
        }
        
        return implode("\n    ", $html);
    }

    /**
     * Render preconnect links for performance
     */
    public static function renderPreconnects(): string
    {
        $html = [];
        
        $preconnects = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://cdn.jsdelivr.net'
        ];
        
        foreach ($preconnects as $url) {
            $html[] = "<link rel=\"preconnect\" href=\"{$url}\" crossorigin>";
        }
        
        return implode("\n    ", $html);
    }
/**
 * Render full metadata bundle (HEAD ready)
 */
public static function render(): string
{
    return implode("\n\n", [
        '<title>' . self::getPageTitle() . '</title>',
        self::renderMetaTags(),
        self::renderFavicons(),
        self::renderPreconnects(),
        self::renderStructuredData()
    ]);
}
/**
 * Render metadata as array for layouts
 */
public static function renderForLayout(): array
{
    return [
        'pageTitle'     => self::getPageTitle(),
        'metaTags'      => self::renderMetaTags(),
        'structuredData'=> self::renderStructuredData(),
        'favicons'      => self::renderFavicons(),
        'preconnects'   => self::renderPreconnects(),
    ];
}

    /**
     * Get page title with site name
     */
    public static function getPageTitle(): string
    {
        $title = self::$metadata['title'] ?? '';
        
        if (empty($title)) {
            return 'EduLearn - Online Learning Platform';
        }
        
        // Add site name if not already in title
        if (!str_contains($title, 'EduLearn')) {
            return $title . ' | EduLearn';
        }
        
        return $title;
    }

    /**
     * Get metadata by key
     */
    public static function get(string $key, $default = null)
    {
        return self::$metadata[$key] ?? $default;
    }

    /**
     * Get all metadata
     */
    public static function getAll(): array
    {
        return [
            'metadata' => self::$metadata,
            'openGraph' => self::$openGraph,
            'twitterCard' => self::$twitterCard,
            'structuredData' => self::$structuredData,
            'breadcrumbs' => self::$breadcrumbs,
            'canonical' => self::$canonical
        ];
    }

    /**
     * Get asset URL with versioning
     */
    public static function getAssetUrl(string $path): string
    {
        $path = ltrim($path, '/');
        
        // If path starts with http:// or https://, return as-is
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        
        // Check if it's a full URL path
        if (strpos($path, self::$baseUri) === 0) {
            return $path;
        }
        
        // Build full URL with versioning
        $fullPath = self::$baseUri . self::$assetsPath . '/' . $path;
        
        // Add version query parameter if version is set
        if (self::$version) {
            $separator = (strpos($fullPath, '?') === false) ? '?' : '&';
            $fullPath .= $separator . 'v=' . self::$version;
        }
        
        return $fullPath;
    }

    /**
     * Replace placeholders in string
     */
    private static function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            if (strpos($text, $placeholder) !== false) {
                $text = str_replace($placeholder, $value, $text);
            }
        }
        return $text;
    }

    /**
     * Replace placeholders in array
     */
    private static function replacePlaceholdersArray(array $array, array $data): array
    {
        $result = [];
        foreach ($array as $item) {
            $result[] = self::replacePlaceholders($item, $data);
        }
        return $result;
    }

    /**
     * Add custom structured data
     */
    public static function addStructuredData(array $data, string $key = null): void
    {
        if ($key) {
            self::$structuredData[$key] = $data;
        } else {
            self::$structuredData[] = $data;
        }
    }

    /**
     * Set additional Open Graph properties
     */
    public static function setOgProperty(string $property, string $value): void
    {
        self::$openGraph[$property] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set additional Twitter Card properties
     */
    public static function setTwitterProperty(string $property, string $value): void
    {
        self::$twitterCard[$property] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}