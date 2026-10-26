<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Models\Setting;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\GalleryItem;
use App\Models\Review;

class AppDataService
{
    private const CACHE_TTL = 3600; // 1 hour TTL for frequently accessed data

    public static function getBusiness(): array
    {
        return Cache::remember('ritiverse_business_data', self::CACHE_TTL, function () {
            $cafeName = (string)Setting::get('cafe_name', 'RiTiVerse');
            $shortName = (string)Setting::get('cafe_short_name', 'RiTiVerse');
            $phone = (string)Setting::get('cafe_phone', '+91 82003 06871');
            $whatsapp = (string)Setting::get('cafe_whatsapp', '918200306871');
            $email = (string)Setting::get('cafe_email', 'hello@ritiverse.in');
            $addressStr = (string)Setting::get('cafe_address', 'Vadodara, Gujarat');
            $hoursDisplay = (string)Setting::get('cafe_hours', 'Open Daily · 11:00 AM – 10:30 PM');
            $tagline = (string)Setting::get('cafe_tagline', "Gujarat's First Queer-Led Heritage Cafe & Cultural Space");
            $secTagline = (string)Setting::get('cafe_secondary_tagline', "Where Heritage, Inclusion & Flavour Bloom.");
            $desc = (string)Setting::get('cafe_description', "Experience warm hospitality, heritage architecture, and delicious homestyle cuisine at RiTiVerse, Vadodara, Gujarat.");

            $coordinates = Setting::get('cafe_coordinates', [
                'lat' => 22.307159,
                'lng' => 73.181219,
            ]);

            $googleMapsUrl = (string)Setting::get('cafe_google_maps_url', 'https://maps.google.com/?q=Vadodara,+Gujarat');
            $googleMapsEmbedUrl = (string)Setting::get('cafe_google_maps_embed_url', 'https://maps.google.com/maps?q=Vadodara,%20Gujarat&t=&z=13&ie=UTF8&iwloc=&output=embed');
            $googleReviewUrl = (string)Setting::get('cafe_google_review_url', 'https://maps.google.com/?q=Vadodara,+Gujarat');

            $addressData = Setting::get('cafe_address_data', [
                'name' => $cafeName,
                'line1' => 'Vadodara',
                'line2' => 'Gujarat',
                'city' => 'Vadodara',
                'state' => 'Gujarat',
                'postalCode' => '390001',
                'country' => 'India',
                'full' => $addressStr,
            ]);
            $addressData['full'] = $addressStr;

            $hoursData = Setting::get('cafe_hours_data', [
                'display' => $hoursDisplay,
                'openTime' => '11:00',
                'closeTime' => '22:30',
                'days' => 'Monday – Sunday',
                'schemaDays' => 'Mo-Su 11:00-22:30',
                'schedule' => [
                    ['day' => 'Monday', 'open' => '11:00 AM', 'close' => '10:30 PM', 'openHour' => 11, 'closeHour' => 22.5],
                    ['day' => 'Tuesday', 'open' => '11:00 AM', 'close' => '10:30 PM', 'openHour' => 11, 'closeHour' => 22.5],
                    ['day' => 'Wednesday', 'open' => '11:00 AM', 'close' => '10:30 PM', 'openHour' => 11, 'closeHour' => 22.5],
                    ['day' => 'Thursday', 'open' => '11:00 AM', 'close' => '10:30 PM', 'openHour' => 11, 'closeHour' => 22.5],
                    ['day' => 'Friday', 'open' => '11:00 AM', 'close' => '10:30 PM', 'openHour' => 11, 'closeHour' => 22.5],
                    ['day' => 'Saturday', 'open' => '11:00 AM', 'close' => '10:30 PM', 'openHour' => 11, 'closeHour' => 22.5],
                    ['day' => 'Sunday', 'open' => '11:00 AM', 'close' => '10:30 PM', 'openHour' => 11, 'closeHour' => 22.5],
                ]
            ]);

            $links = Setting::get('cafe_links', [
                'instagram' => 'https://www.instagram.com/ritiverse/',
                'facebook' => 'https://www.facebook.com/ritiverse',
                'zomato' => 'https://www.zomato.com/vadodara',
                'swiggy' => 'https://www.swiggy.com/city/vadodara',
                'menuPdf' => '#menu-download',
            ]);

            $story = Setting::get('cafe_story', [
                'eyebrow' => 'HERITAGE MEETS INCLUSION',
                'heading' => (string)(Setting::get('cms_story_heading') ?: 'Heritage Sanctuary in Vadodara, Gujarat'),
                'paragraph1' => 'Nestled in Vadodara, Gujarat, RiTiVerse is celebrated as a vibrant dining space and cultural hub, born out of a shared vision of dignity, artistic expression, and radical love.',
                'paragraph2' => 'Located in Vadodara, Gujarat, RiTiVerse blends soulful homestyle dishes, fragrant beverages, and artisanal coffees in a lush open-air garden and tranquil setting.',
                'quote' => (string)(Setting::get('cms_story_quote') ?: 'A safe, beautiful table where everyone belongs and authentic flavours flourish in Vadodara.'),
            ]);
            if ($heading = Setting::get('cms_story_heading')) {
                $story['heading'] = (string)$heading;
            }
            if ($quote = Setting::get('cms_story_quote')) {
                $story['quote'] = (string)$quote;
            }

            $qualityIngredients = Setting::get('cafe_quality_ingredients', [
                'eyebrow' => 'HOMESTYLE HERITAGE & FRESH CRAFT',
                'heading' => 'Prepared With Love, Heritage & Pure Ingredients',
                'paragraph' => 'From our fragrant Mogra Shikanji and slow-steeped filter coffees to hand-rolled theplas, authentic Maharashtrian misal, and artisanal cafe plates, every dish is prepared with homestyle care, dignity, and farm-fresh regional produce.',
                'stats' => [
                    ['value' => '100%', 'label' => 'Inclusive & Safe Space'],
                    ['value' => 'Fresh', 'label' => 'Farm-to-Kitchen Produce'],
                    ['value' => 'Pure', 'label' => 'Homestyle Heritage Recipes'],
                ]
            ]);

            $events = Setting::get('cafe_events', [
                'eyebrow' => 'GATHER & CELEBRATE',
                'heading' => 'Cultural Gatherings & Soulful Evenings at RiTiVerse',
                'subheading' => 'Whether it is an acoustic live session, an inclusive community circle, an art exhibition, a poetry reading, or an intimate birthday celebration in Vadodara, Gujarat, RiTiVerse creates unforgettable moments in a warm, welcoming atmosphere.',
                'features' => [
                    'Customized homestyle Gujarati, Maharashtrian & Cafe menus',
                    'Warm community hospitality & open courtyard aesthetics',
                    'Serene garden and heritage brick seating for up to 60 guests',
                    'Acoustic audio setup for poetry, art exhibitions & social meets',
                ]
            ]);

            return [
                'name' => $cafeName,
                'shortName' => $shortName,
                'tagline' => $tagline,
                'secondaryTagline' => $secTagline,
                'description' => $desc,
                'foundedYear' => (string)Setting::get('cafe_founded_year', '2023'),
                'city' => (string)Setting::get('cafe_city', 'Vadodara, Gujarat'),
                'currency' => (string)Setting::get('cafe_currency', '₹'),
                'phone' => $phone,
                'phoneDisplay' => $phone,
                'phoneRaw' => preg_replace('/[^0-9]/', '', $phone),
                'email' => $email,
                'whatsappNumber' => $whatsapp,
                'whatsappDefaultMessage' => (string)Setting::get('cafe_whatsapp_default_message', "Hello {$cafeName}, I would like to reserve a table / enquire about visits & events."),
                'orderWhatsAppMessage' => (string)Setting::get('cafe_order_whatsapp_message', "Hello {$cafeName}, I would like to order fresh food for takeout."),
                'address' => $addressData,
                'coordinates' => $coordinates,
                'googleMapsUrl' => $googleMapsUrl,
                'googleMapsEmbedUrl' => $googleMapsEmbedUrl,
                'googleReviewUrl' => $googleReviewUrl,
                'hours' => $hoursData,
                'links' => $links,
                'story' => $story,
                'qualityIngredients' => $qualityIngredients,
                'events' => $events,
            ];
        });
    }

    public static function getMenu(): array
    {
        return Cache::remember('ritiverse_menu_data', self::CACHE_TTL, function () {
            $categoriesRaw = MenuCategory::getActive('sort_order ASC, id ASC');
            $itemsRaw = MenuItem::where('is_available = 1', [], 'sort_order ASC, id ASC');

            $categories = [];
            $catIdToSlug = [];
            foreach ($categoriesRaw as $cat) {
                $slug = $cat['slug'] ?: ('cat-' . $cat['id']);
                $catIdToSlug[$cat['id']] = $slug;
                $categories[] = [
                    'id' => $slug,
                    'label' => $cat['name'],
                    'description' => $cat['description'] ?? '',
                ];
            }

            $items = [];
            foreach ($itemsRaw as $item) {
                $catSlug = $catIdToSlug[$item['category_id']] ?? 'mains';
                $priceRupees = (int)round(($item['price_paise'] ?? 0) / 100);

                $items[] = [
                    'id' => 'item-' . $item['id'],
                    'categoryId' => $catSlug,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'price' => $priceRupees,
                    'isVeg' => (bool)($item['is_veg'] ?? 1),
                    'tag' => $item['tag'] ?? null,
                    'image' => $item['image_url'] ?: 'assets/images/cafe/all-day-spread.jpg',
                ];
            }

            return [
                'currencySymbol' => (string)Setting::get('currency_symbol', '₹'),
                'downloadMenuUrl' => (string)Setting::get('menu_download_url', '#menu-download'),
                'categories' => $categories,
                'items' => $items,
            ];
        });
    }

    public static function getGallery(): array
    {
        return Cache::remember('ritiverse_gallery_data', self::CACHE_TTL, function () {
            $itemsRaw = GalleryItem::getActive('sort_order ASC, id ASC');

            $items = [];
            foreach ($itemsRaw as $gi) {
                $items[] = [
                    'id' => 'gal-' . $gi['id'],
                    'title' => $gi['title'],
                    'category' => $gi['category'] ?? 'Experience',
                    'image' => $gi['image'],
                    'aspect' => $gi['aspect'] ?? 'aspect-[4/5]',
                    'caption' => $gi['caption'] ?? '',
                ];
            }

            return [
                'instagramHandle' => (string)Setting::get('gallery_instagram_handle', '@ritiverse'),
                'instagramUrl' => (string)Setting::get('gallery_instagram_url', 'https://www.instagram.com/ritiverse/'),
                'items' => $items,
            ];
        });
    }

    public static function getReviews(): array
    {
        return Cache::remember('ritiverse_reviews_data', self::CACHE_TTL, function () {
            $approved = Review::getApprovedWithDetails(12);

            $formatted = [];
            foreach ($approved as $ar) {
                $formatted[] = [
                    'id' => 'db-' . $ar['id'],
                    'author' => $ar['user_name'] ?: 'Verified Diner',
                    'badge' => '★ Verified Diner',
                    'rating' => (int)($ar['rating'] ?? 5),
                    'date' => !empty($ar['created_at']) ? date('M Y', strtotime($ar['created_at'])) : 'Recent',
                    'text' => $ar['review_text'] ?? '',
                    'dishMentioned' => $ar['primary_dish'] ?? 'Artisanal Selection',
                ];
            }

            return [
                'averageRating' => (float)Setting::get('reviews_avg_rating', 4.8),
                'totalReviewsCount' => (string)Setting::get('reviews_total_count', '680+'),
                'googleReviewLink' => (string)Setting::get('cafe_google_review_url', 'https://maps.google.com/?q=Vadodara,+Gujarat'),
                'reviews' => $formatted,
            ];
        });
    }

    public static function getAllSeoConfig(): array
    {
        $cafeName = (string)Setting::get('cafe_name', 'RiTiVerse');
        $city = (string)Setting::get('cafe_city', 'Vadodara, Gujarat');
        $defaultSiteTitle = (string)Setting::get('seo_global_site_title', "{$cafeName} | Heritage Cafe & Cultural Space · {$city}");
        $defaultMetaDesc = (string)Setting::get('seo_global_meta_description', "Experience warm hospitality, heritage architecture, and delicious homestyle cuisine at {$cafeName}, {$city}.");
        $defaultKeywords = (string)Setting::get('seo_global_meta_keywords', "{$cafeName}, {$cafeName} Cafe, Vadodara cafe, Best cafe in Vadodara, Gujarat cafe, Heritage cafe Vadodara, cultural space Vadodara");
        $canonicalBase = rtrim((string)Setting::get('seo_global_canonical_url', 'https://ritiverse.in'), '/');
        $globalRobots = (string)Setting::get('seo_global_robots', 'index, follow');
        $defaultOgImage = (string)Setting::get('seo_global_og_image', 'assets/images/cafe/hero-table.jpg');

        $gsc = (string)Setting::get('seo_google_site_verification', '');
        $bing = (string)Setting::get('seo_bing_site_verification', '');
        $gaId = (string)Setting::get('seo_google_analytics_id', '');
        $metaPixel = (string)Setting::get('seo_meta_pixel_id', '');
        $customHead = (string)Setting::get('seo_custom_head_code', '');

        // Clean verification tokens in case full meta tag was pasted
        if (preg_match('/content=["\']([^"\']+)["\']/i', $gsc, $m)) {
            $gsc = $m[1];
        }
        if (preg_match('/content=["\']([^"\']+)["\']/i', $bing, $m)) {
            $bing = $m[1];
        }
        if (preg_match('/[0-9]{8,20}/', $metaPixel, $m)) {
            $metaPixel = $m[0];
        }

        $defaultPages = [
            'home' => [
                'title' => "{$cafeName} | Heritage Cafe & Cultural Space in {$city}",
                'description' => "Experience warm hospitality, heritage architecture, and delicious homestyle cuisine at {$cafeName}, {$city}.",
                'keywords' => "{$cafeName}, cafe in Vadodara, heritage cafe, best coffee Vadodara",
                'og_image' => 'assets/images/cafe/hero-table.jpg',
                'robots' => 'index, follow',
                'path' => '',
            ],
            'menu' => [
                'title' => "Our Menu | Artisanal Coffee, Pizza & Homestyle Plates · {$cafeName}",
                'description' => "Browse our handcrafted menu featuring 48-hr fermented pizzas, bronze-cut pasta, signature cold brews, and comforting bowls in {$city}.",
                'keywords' => "menu, artisanal coffee, cold brew, wood fired pizza, pasta, Vadodara food",
                'og_image' => 'assets/images/cafe/menu-cover-spaghetti.jpg',
                'robots' => 'index, follow',
                'path' => 'menu',
            ],
            'about' => [
                'title' => "Our Story & Philosophy | {$cafeName} {$city}",
                'description' => "Discover the story of {$cafeName} in {$city} - a heritage sanctuary celebrating dignity, inclusion, and authentic taste.",
                'keywords' => "our story, heritage cafe, cultural sanctuary, inclusive space Vadodara",
                'og_image' => 'assets/images/cafe/interior-atmosphere.jpg',
                'robots' => 'index, follow',
                'path' => 'about',
            ],
            'events' => [
                'title' => "Events, Celebrations & Private Gatherings | {$cafeName}",
                'description' => "Host private celebrations, intimate birthdays, acoustic sessions, and poetry readings in our heritage garden courtyard.",
                'keywords' => "events, cafe private dining, birthday party Vadodara, live music cafe",
                'og_image' => 'assets/images/cafe/all-day-spread.jpg',
                'robots' => 'index, follow',
                'path' => 'events',
            ],
            'gallery' => [
                'title' => "Visual Diary & Ambiance Gallery | {$cafeName}",
                'description' => "Explore our visual diary of heritage ambiance, barista craft, artisanal dishes, and serene evening vibes.",
                'keywords' => "gallery, cafe photos, food photography, aesthetic cafe Vadodara",
                'og_image' => 'assets/images/cafe/hero-table.jpg',
                'robots' => 'index, follow',
                'path' => 'gallery',
            ],
            'contact' => [
                'title' => "Contact, Hours & Location in Vadodara | {$cafeName}",
                'description' => "Visit {$cafeName} in {$city}. Operating hours, direct phone, WhatsApp table reservations, and directions.",
                'keywords' => "contact, address, cafe opening hours, table booking, Vadodara directions",
                'og_image' => 'assets/images/cafe/interior-atmosphere.jpg',
                'robots' => 'index, follow',
                'path' => 'contact',
            ],
            'table' => [
                'title' => "Digital Table Menu & Contactless Ordering | {$cafeName}",
                'description' => "Place your table order directly from your mobile device. Fast, seamless contactless dining experience.",
                'keywords' => "table order, qr ordering, digital menu, contactless dining",
                'og_image' => 'assets/images/cafe/hero-table.jpg',
                'robots' => 'noindex, follow',
                'path' => 'table',
            ],
        ];

        $pagesConfig = Setting::get('seo_pages_data', $defaultPages);
        if (!is_array($pagesConfig)) {
            $pagesConfig = $defaultPages;
        } else {
            // Merge defaults for any missing page keys
            foreach ($defaultPages as $pk => $def) {
                if (!isset($pagesConfig[$pk]) || !is_array($pagesConfig[$pk])) {
                    $pagesConfig[$pk] = $def;
                } else {
                    $pagesConfig[$pk] = array_merge($def, array_filter($pagesConfig[$pk], fn($v) => $v !== null && $v !== ''));
                }
            }
        }

        return [
            'global' => [
                'site_title' => $defaultSiteTitle,
                'meta_description' => $defaultMetaDesc,
                'meta_keywords' => $defaultKeywords,
                'canonical_base' => $canonicalBase,
                'robots' => $globalRobots,
                'og_image' => $defaultOgImage,
                'google_site_verification' => $gsc,
                'bing_site_verification' => $bing,
                'google_analytics_id' => $gaId,
                'meta_pixel_id' => $metaPixel,
                'custom_head_code' => $customHead,
            ],
            'pages' => $pagesConfig,
        ];
    }

    public static function getSeo(?string $pageKey = 'home', ?string $fallbackTitle = null, ?string $fallbackDesc = null): array
    {
        $pageKey = $pageKey ?: 'home';

        $allSeo = Cache::remember('ritiverse_seo_data', self::CACHE_TTL, function () {
            return self::getAllSeoConfig();
        });

        $global = $allSeo['global'] ?? [];
        $pageData = $allSeo['pages'][$pageKey] ?? [];

        // Title resolution: per-page override > fallback title > global site title
        $title = !empty($pageData['title']) ? (string)$pageData['title'] : ($fallbackTitle ?: (string)($global['site_title'] ?? 'RiTiVerse'));

        // Description resolution: per-page override > fallback desc > global meta description
        $description = !empty($pageData['description']) ? (string)$pageData['description'] : ($fallbackDesc ?: (string)($global['meta_description'] ?? ''));

        // Keywords resolution
        $keywords = !empty($pageData['keywords']) ? (string)$pageData['keywords'] : (string)($global['meta_keywords'] ?? '');

        // Robots resolution
        $robots = !empty($pageData['robots']) ? (string)$pageData['robots'] : (string)($global['robots'] ?? 'index, follow');

        // OG Image resolution
        $ogImage = !empty($pageData['og_image']) ? (string)$pageData['og_image'] : (string)($global['og_image'] ?? 'assets/images/cafe/hero-table.jpg');

        // Canonical URL resolution
        $canonicalBase = rtrim((string)($global['canonical_base'] ?? 'https://ritiverse.in'), '/');
        $path = $pageData['path'] ?? ($pageKey === 'home' ? '' : $pageKey);
        $canonical = $canonicalBase . ($path !== '' ? '/' . ltrim($path, '/') : '');

        return [
            'page_key' => $pageKey,
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'robots' => $robots,
            'canonical' => $canonical,
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $ogImage,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => $ogImage,
            'google_site_verification' => (string)($global['google_site_verification'] ?? ''),
            'bing_site_verification' => (string)($global['bing_site_verification'] ?? ''),
            'google_analytics_id' => (string)($global['google_analytics_id'] ?? ''),
            'meta_pixel_id' => (string)($global['meta_pixel_id'] ?? ''),
            'custom_head_code' => (string)($global['custom_head_code'] ?? ''),
        ];
    }

    public static function clearCache(?string $section = null): void
    {
        if ($section === null) {
            Cache::forget('ritiverse_business_data');
            Cache::forget('ritiverse_menu_data');
            Cache::forget('ritiverse_gallery_data');
            Cache::forget('ritiverse_reviews_data');
            Cache::forget('ritiverse_seo_data');
        } else {
            Cache::forget("ritiverse_{$section}_data");
        }
    }
}
