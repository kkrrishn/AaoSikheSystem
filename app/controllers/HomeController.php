<?php

declare(strict_types=1);

namespace App\Controllers;

use AaoSikheSystem\view\View;
use AaoSikheSystem\rate_limit\RateLimiter;
use AaoSikheSystem\cache\CacheManager;
use AaoSikheSystem\Security\CaptchaValidator;
use AaoSikheSystem\helper\HeaderHelper;
use AaoSikheSystem\Security\SecurityHelper;
use AaoSikheSystem\db\DBManager;
use AaoSikheSystem\helper\SystemHelper;
use App\helpers\MetaDataHelper;


class HomeController
{
    private View $view;
    private RateLimiter $rateLimiter;
    private CacheManager $cacheManager;

    private DBManager $dbManager;

    public function __construct()
    {
        // Initialize CacheManager (singleton pattern)
        $this->cacheManager = CacheManager::getInstance([
            'default' => 'file',
            'prefix' => 'aao_sikhe_',
            'drivers' => [
                'file' => [
                    'path' => __DIR__ . '/../../storage/cache/',
                    'ttl' => 3600
                ]
            ]
        ]);
        $this->dbManager = DBManager::getInstance();
        // Initialize RateLimiter with CacheManager dependency
        $this->rateLimiter = new RateLimiter($this->cacheManager);

        // Initialize View
        $this->view = new View(__DIR__ . '/../views/pages', '../storage/cache/views');
        MetaDataHelper::init(
            baseUri: BASE_URI ?? BASE_URI,
            assetsPath: '/public/assets',
            version: '1.0.0'
        );
    }

    /**
     * Home page
     */
    public function index()
    {




        // Get client IP for rate limiting
        $clientIp = SystemHelper::getClientIP();

        // Apply rate limiting (max 20 requests per minute per IP)
        $rateLimitKey = 'home_index_' . $clientIp;
        if (!$this->rateLimiter->attempt($rateLimitKey, 20, 60)) {
            http_response_code(429);
            echo "Too many requests. Please try again later.";
            return;
        }

        MetaDataHelper::setupPage('home');

        MetaDataHelper::addStructuredData([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => 'AaoSikheSystem',
            'image' => MetaDataHelper::getAssetUrl('images/logo.png'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '123 Learning Street',
                'addressLocality' => 'New Delhi',
                'addressRegion' => 'Delhi',
                'postalCode' => '110001',
                'addressCountry' => 'IN'
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '28.6139',
                'longitude' => '77.2090'
            ],
            'url' => BASE_URI,
            'telephone' => '+91-9999999999',
            'openingHours' => 'Mo-Su 09:00-21:00',
            'priceRange' => '₹₹'
        ], 'local_business');
        MetaDataHelper::addStructuredData(
            MetaDataHelper::generateFaqStructuredData([
                [
                    'question' => 'Is AaoSikheSystem free?',
                    'answer' => 'Some courses are free, premium courses are paid.'
                ],
                [
                    'question' => 'Do you provide certificates?',
                    'answer' => 'Yes, certificates are provided after course completion.'
                ]
            ]),
            'faq'
        );

        // Generate all metadata
        $metadata = MetaDataHelper::renderForLayout();


        // Try to get cached data first
        $cacheKey = 'home_page_data';
        $cachedData = $this->cacheManager->remember($cacheKey, 300, function () {
            // This callback executes only if cache doesn't exist or expired
            return $this->getHomePageData();
        });

        // Pass data to view
        echo $this->view->render('home', [
            'pageTitle' => 'AaoSikheSystem - Learn, Grow, Succeed',
            'pageDescription' => 'Join AaoSikheSystem for quality education. Learn programming, data science, web development and more with expert instructors.',
            'data' => $cachedData,
            'user' => $_SESSION['user'] ?? null,
            'metadata' => $metadata,
            'rate_limit_remaining' => $this->rateLimiter->remaining($rateLimitKey, 20, 60)
        ]);
    }






    public function clearCache()
    {
        // Check if user is admin (you should implement proper authentication)
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo "Access denied.";
            return;
        }

        // Clear specific cache keys
        $this->cacheManager->delete('home_page_data');
        $this->cacheManager->delete('about_page_data');
        $this->cacheManager->delete('services_page_data');
        $this->cacheManager->delete('api_courses');

        // Clear all cache with prefix
        $this->cacheManager->clear();

        // Clear rate limits for current IP
        $clientIp = SystemHelper::getClientIP();
        $this->rateLimiter->clear('home_index_' . $clientIp);
        $this->rateLimiter->clear('about_page_' . $clientIp);
        $this->rateLimiter->clear('services_page_' . $clientIp);
        $this->rateLimiter->clear('contact_page_' . $clientIp);

        echo "Cache and rate limits cleared successfully.";
    }

    /**
     * Get system statistics
     */
    public function getStats()
    {
        // Admin only access
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo "Access denied.";
            return;
        }

        $clientIp = SystemHelper::getClientIP();

        $stats = [
            'cache' => [
                'home_page_data' => $this->cacheManager->has('home_page_data'),
                'about_page_data' => $this->cacheManager->has('about_page_data'),
                'services_page_data' => $this->cacheManager->has('services_page_data'),
            ],
            'rate_limits' => [
                'home' => $this->rateLimiter->getStats('home_index_' . $clientIp, 20, 60),
                'about' => $this->rateLimiter->getStats('about_page_' . $clientIp, 15, 60),
                'services' => $this->rateLimiter->getStats('services_page_' . $clientIp, 15, 60),
            ],
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        header('Content-Type: application/json');
        echo json_encode($stats);
    }



    private function getHomePageData(): array
    {
        // Simulate expensive data fetching for home page
        sleep(1);
        return [
            'featured_courses' => [
                ['title' => 'Complete Web Development', 'students' => 2500, 'rating' => 4.5],
                ['title' => 'Data Science & ML', 'students' => 1800, 'rating' => 4.8],
                ['title' => 'Mobile App Development', 'students' => 1200, 'rating' => 4.3]
            ],
            'stats' => [
                'total_students' => 10500,
                'total_courses' => 150,
                'expert_instructors' => 45,
                'success_rate' => '98%'
            ],
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
}
