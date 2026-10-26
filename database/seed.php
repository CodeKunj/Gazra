<?php
/**
 * Cielo Cafe and Restro - Database Seeder
 * Populates initial settings, admin users, tables with secure tokens,
 * categories and items from menu.json, and starter loyalty rules.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\Money;
use App\Core\Security;
use App\Models\Setting;
use App\Models\StaffUser;
use App\Models\Table;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\ItemVariant;
use App\Models\ItemAddon;
use App\Models\RewardRule;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\GalleryItem;
use App\Services\AppDataService;

echo "========================================================\n";
echo " Cielo Cafe & Restro - Database Seeder\n";
echo "========================================================\n";

try {
    $db = Database::getInstance();

    // 1. Seed Dynamic Settings (CMS, GST, Loyalty, Cafe Info)
    echo "\n1. Seeding Dynamic Settings & CMS...\n";
    $settings = [
        // Tax / GST (Managed from Admin Panel per user request)
        ['key' => 'gst_enabled', 'value' => '1', 'group' => 'tax', 'type' => 'bool'],
        ['key' => 'gst_cgst_percent', 'value' => '2.5', 'group' => 'tax', 'type' => 'float'],
        ['key' => 'gst_sgst_percent', 'value' => '2.5', 'group' => 'tax', 'type' => 'float'],
        ['key' => 'cafe_gstin', 'value' => '24AAACC1234F1Z5', 'group' => 'tax', 'type' => 'string'],

        // Loyalty Points (Managed from Admin Panel per user request)
        ['key' => 'loyalty_enabled', 'value' => '1', 'group' => 'loyalty', 'type' => 'bool'],
        ['key' => 'loyalty_points_per_100', 'value' => '10', 'group' => 'loyalty', 'type' => 'int'],
        ['key' => 'loyalty_point_value_paise', 'value' => '100', 'group' => 'loyalty', 'type' => 'int'], // 1 point = ₹1
        ['key' => 'loyalty_min_redeem_points', 'value' => '50', 'group' => 'loyalty', 'type' => 'int'],
        ['key' => 'loyalty_max_redeem_percent', 'value' => '50.0', 'group' => 'loyalty', 'type' => 'float'],
        ['key' => 'loyalty_review_points', 'value' => '50', 'group' => 'loyalty', 'type' => 'int'],

        // Payments (Project ready for online gateway, active with Counter/UPI)
        ['key' => 'payment_counter_cash', 'value' => '1', 'group' => 'payment', 'type' => 'bool'],
        ['key' => 'payment_counter_upi', 'value' => '1', 'group' => 'payment', 'type' => 'bool'],
        ['key' => 'payment_online_razorpay', 'value' => '0', 'group' => 'payment', 'type' => 'bool'],
        ['key' => 'payment_upi_id', 'value' => '8200306871@okbizaxis', 'group' => 'payment', 'type' => 'string'],

        // Cafe Details
        ['key' => 'cafe_name', 'value' => 'RiTiVerse', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_tagline', 'value' => "Heritage Cafe & Cultural Space", 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_phone', 'value' => '+91 82003 06871', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_whatsapp', 'value' => '918200306871', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_email', 'value' => 'hello@ritiverse.in', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_address', 'value' => 'Vadodara, Gujarat', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_hours', 'value' => '11:00 AM - 10:30 PM', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_google_maps_embed', 'value' => 'https://maps.google.com/maps?q=Vadodara,%20Gujarat&t=&z=13&ie=UTF8&iwloc=&output=embed', 'group' => 'cafe', 'type' => 'string'],

        // Dynamic Website CMS
        ['key' => 'cms_hero_name', 'value' => 'RiTiVerse', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_hero_tagline', 'value' => 'Heritage Cafe & Cultural Space', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_hero_badge', 'value' => 'Vadodara, Gujarat', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_story_heading', 'value' => 'A Heritage Sanctuary Born from Dignity, Inclusion & Authentic Taste', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_story_quote', 'value' => 'Where warm hospitality meets authentic taste in Vadodara, Gujarat.', 'group' => 'cms', 'type' => 'string'],

        // Global & Per-Page SEO & Webmaster Integrations
        ['key' => 'seo_global_site_title', 'value' => 'RiTiVerse | Heritage Cafe & Cultural Space · Vadodara, Gujarat', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_global_meta_description', 'value' => 'Experience warm hospitality, heritage architecture, and delicious homestyle cuisine at RiTiVerse, Vadodara, Gujarat.', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_global_meta_keywords', 'value' => 'RiTiVerse, RiTiVerse Cafe, Vadodara cafe, Best cafe in Vadodara, Gujarat cafe, Heritage cafe Vadodara', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_global_canonical_url', 'value' => 'https://ritiverse.in', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_global_robots', 'value' => 'index, follow', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_global_og_image', 'value' => 'assets/images/cafe/hero-table.jpg', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_google_site_verification', 'value' => '4bX8jQp9zM1_sK2wL8vN0pQ7rT3uY6', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_bing_site_verification', 'value' => 'A483E58D98C127D480BC4B4958309482', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_google_analytics_id', 'value' => 'G-RITIVERSE26', 'group' => 'seo', 'type' => 'string'],
        ['key' => 'seo_meta_pixel_id', 'value' => '987654321012345', 'group' => 'seo', 'type' => 'string'],
    ];

    foreach ($settings as $s) {
        Setting::set($s['key'], $s['value'], $s['group'], $s['type']);
    }
    echo "  ✔ Settings & CMS configurations seeded (" . count($settings) . " entries)\n";

    // 2. Seed Default Staff & Admin Users
    echo "\n2. Seeding Staff Accounts...\n";
    $staffData = [
        ['name' => 'Cielo Owner', 'email' => 'admin@cielocafe.com', 'password' => 'CieloAdmin@2026', 'role' => 'owner'],
        ['name' => 'Kitchen Head', 'email' => 'kitchen@cielocafe.com', 'password' => 'Kitchen@2026', 'role' => 'kitchen'],
    ];

    foreach ($staffData as $st) {
        $existing = StaffUser::findBy('email', $st['email']);
        if (!$existing) {
            StaffUser::createStaff($st['name'], $st['email'], $st['password'], $st['role']);
            echo "  ✔ Created staff account: {$st['email']} ({$st['role']})\n";
        } else {
            echo "  ℹ Staff account {$st['email']} already exists\n";
        }
    }

    // 3. Seed Cafe Tables with Secure QR Tokens
    echo "\n3. Seeding Cafe Tables & Tokens...\n";
    for ($i = 1; $i <= 10; $i++) {
        $tblNum = sprintf('%02d', $i);
        $existing = Table::findBy('table_number', $tblNum);
        if (!$existing) {
            $token = Security::generateTableToken(16);
            Table::create([
                'table_number' => $tblNum,
                'qr_token' => $token,
                'capacity' => ($i <= 4) ? 2 : (($i <= 8) ? 4 : 8),
                'is_active' => 1,
            ]);
            echo "  ✔ Created Table {$tblNum} [Token: {$token}]\n";
        }
    }

    // 4. Seed Menu Categories & Items
    echo "\n4. Seeding Menu Categories & Items...\n";
    $menuCategories = [
        ['slug' => 'coffee-beverages', 'name' => 'Coffee & Beverages', 'description' => 'Specialty estate espresso, slow cold brews, and handcrafted signature coolers.'],
        ['slug' => 'starters', 'name' => 'Starters', 'description' => 'Artisanal crostinis, fondue bowls, and crispy small bites for sharing.'],
        ['slug' => 'mains', 'name' => 'Mains', 'description' => 'Hearty Continental plates, risottos, and wholesome European cafe bowls.'],
        ['slug' => 'pizza-pasta', 'name' => 'Pizza & Pasta', 'description' => '48-hour fermented crust pizzas and slow-simmered artisanal pasta.'],
        ['slug' => 'desserts', 'name' => 'Desserts', 'description' => 'Artisanal bakes, sweet table finishers, and comforting desserts.'],
    ];

    $categoryMap = [];
    foreach ($menuCategories as $index => $cat) {
        $existing = MenuCategory::findBy('slug', $cat['slug']);
        if (!$existing) {
            $catId = MenuCategory::create([
                'slug' => $cat['slug'],
                'name' => $cat['name'],
                'description' => $cat['description'],
                'sort_order' => $index,
                'time_slot' => 'all_day',
                'is_active' => 1,
            ]);
            $categoryMap[$cat['slug']] = (int)$catId;
            echo "  ✔ Category created: {$cat['name']}\n";
        } else {
            $categoryMap[$cat['slug']] = (int)$existing['id'];
        }
    }

    $menuItems = [
        ['category' => 'coffee-beverages', 'name' => 'RiTiVerse Citrus Cold Brew', 'description' => 'Single-origin 18-hour cold brew infused with fresh orange zest and sparkling tonic.', 'price' => 240, 'is_veg' => 1, 'tag' => 'Signature', 'image' => 'assets/images/dishes/orange-espresso-tonic.jpg'],
        ['category' => 'coffee-beverages', 'name' => 'Sunset Spritzer Mocktail', 'description' => 'Vibrant layered mocktail featuring pomegranate reduction, citrus cordial, and crushed fresh mint.', 'price' => 220, 'is_veg' => 1, 'tag' => 'Bestseller', 'image' => 'assets/images/dishes/sunset-mocktail.jpg'],
        ['category' => 'coffee-beverages', 'name' => 'Artisan Caramel Flat White', 'description' => 'Double ristretto shot with velvety micro-foam, madagascar vanilla bean, and salted caramel drizzle.', 'price' => 210, 'is_veg' => 1, 'tag' => 'Barista Pick', 'image' => 'assets/images/cafe/hero-table.jpg'],
        ['category' => 'coffee-beverages', 'name' => 'Iced Shakerato Lemonade', 'description' => 'Vigorously shaken dark roast espresso over crushed ice with organic Sicilian lemon syrup.', 'price' => 230, 'is_veg' => 1, 'tag' => 'Refreshing', 'image' => 'assets/images/cafe/interior-atmosphere.jpg'],
        ['category' => 'coffee-beverages', 'name' => 'Spanish Cortado & Dark Mocha', 'description' => 'Balanced 1:1 espresso and silky textured milk with melted 70% dark Belgian cocoa.', 'price' => 220, 'is_veg' => 1, 'tag' => 'Classic', 'image' => 'assets/images/cafe/all-day-spread.jpg'],

        ['category' => 'starters', 'name' => 'Artisanal Burrata Bruschetta', 'description' => 'Charred sourdough baguette topped with ripe plum tomatoes, creamy stracciatella, Kalamata olives, and fresh basil.', 'price' => 290, 'is_veg' => 1, 'tag' => 'Must Try', 'image' => 'assets/images/dishes/bruschetta-burrata.jpg'],
        ['category' => 'starters', 'name' => 'Warm Cheese Fondue Platter', 'description' => 'Melted Gruyère and Emmental dip served with seasoned pita triangles, roasted broccoli florets, and herb croutons.', 'price' => 380, 'is_veg' => 1, 'tag' => 'Chef Special', 'image' => 'assets/images/dishes/cheese-fondue-platter.jpg'],
        ['category' => 'starters', 'name' => 'Crispy Peri-Peri Truffle Fries', 'description' => 'Hand-cut golden potato batons tossed in house spice rub, micro-greens, and served with garlic aioli.', 'price' => 210, 'is_veg' => 1, 'tag' => 'Crowd Favorite', 'image' => 'assets/images/dishes/peri-peri-fries.jpg'],
        ['category' => 'starters', 'name' => 'Classic Tomato Basil Crostini', 'description' => 'Toasted garlic sourdough slices crowned with slow-roasted cherry tomatoes, cold-pressed olive oil, and lime mist.', 'price' => 260, 'is_veg' => 1, 'tag' => 'Cafe Classic', 'image' => 'assets/images/dishes/bruschetta-carved.jpg'],
        ['category' => 'starters', 'name' => 'Charred Cottage Cheese Skewers', 'description' => 'Soft Malai paneer cubes cured in sun-dried tomato pesto and grilled over open flame.', 'price' => 320, 'is_veg' => 1, 'tag' => 'Tandoor Twist', 'image' => 'assets/images/cafe/hero-table.jpg'],

        ['category' => 'mains', 'name' => 'Wild Mushroom Risotto Parmigiano', 'description' => 'Slow-stirred Arborio rice in Porcini mushroom broth, white wine reduction, finished with aged 24-month Parmigiano.', 'price' => 420, 'is_veg' => 1, 'tag' => 'House Specialty', 'image' => 'assets/images/cafe/all-day-spread.jpg'],
        ['category' => 'mains', 'name' => 'Rustic Tuscan Vegetable Stufato', 'description' => 'Slow-braised zucchini, bell peppers, baby aubergine, and chickpeas in rich pomodoro sauce with garlic sourdough.', 'price' => 390, 'is_veg' => 1, 'tag' => 'Comfort Food', 'image' => 'assets/images/cafe/hero-table.jpg'],
        ['category' => 'mains', 'name' => 'Creamy Polenta with Truffle Glaze', 'description' => 'Golden sweetcorn polenta cooked with butter and mascarpone, topped with charred greens and balsamic drizzle.', 'price' => 370, 'is_veg' => 1, 'tag' => 'Gluten-Free', 'image' => 'assets/images/dishes/bruschetta-artisan.jpg'],
        ['category' => 'mains', 'name' => 'RiTiVerse Mediterranean Mezze Bowl', 'description' => 'Warm falafel patties, beetroot hummus, herb tabbouleh, marinated olives, and toasted pita crisps.', 'price' => 360, 'is_veg' => 1, 'tag' => 'All-Day Bowl', 'image' => 'assets/images/dishes/cheese-fondue-platter.jpg'],

        ['category' => 'pizza-pasta', 'name' => 'Spaghetti Pomodoro E Basilico', 'description' => 'Bronze-cut spaghetti twirled in sweet San Marzano tomato sauce, fresh cherry tomatoes, Kalamata olives, and sweet basil.', 'price' => 360, 'is_veg' => 1, 'tag' => 'Signature', 'image' => 'assets/images/cafe/menu-cover-spaghetti.jpg'],
        ['category' => 'pizza-pasta', 'name' => 'Genovese Pesto & Sundried Tomato Pizza', 'description' => 'Hand-stretched 48-hr crust, basil-pinenut pesto base, confit tomatoes, sun-dried tomatoes, and Fior di Latte mozzarella.', 'price' => 460, 'is_veg' => 1, 'tag' => 'Chef Special', 'image' => 'assets/images/dishes/pesto-pizza.jpg'],
        ['category' => 'pizza-pasta', 'name' => 'RiTiVerse Quattro Formaggi Deluxe', 'description' => 'Creamy Mozzarella, Gorgonzola, smoked Scamorza, and shaved Parmesan with charred garlic and oregano.', 'price' => 490, 'is_veg' => 1, 'tag' => 'Cheese Lover', 'image' => 'assets/images/cafe/all-day-spread.jpg'],
        ['category' => 'pizza-pasta', 'name' => 'Truffle Penne Al Funghi', 'description' => 'Al dente penne rigate enveloped in rich white mushroom velouté, black truffle oil, and cracked pink peppercorn.', 'price' => 390, 'is_veg' => 1, 'tag' => 'Bestseller', 'image' => 'assets/images/cafe/hero-table.jpg'],
        ['category' => 'pizza-pasta', 'name' => 'Fire-Roasted Bell Pepper Pizza', 'description' => 'Spicy arrabbiata sauce base, chargrilled sweet peppers, yellow zucchini, jalapeños, and fresh bocconcini.', 'price' => 440, 'is_veg' => 1, 'tag' => 'Spicy', 'image' => 'assets/images/cafe/all-day-spread.jpg'],

        ['category' => 'desserts', 'name' => 'Classic Venetian Tiramisu', 'description' => 'Espresso-soaked Savoiardi ladyfingers layered with whipped mascarpone cream and dusted with raw cocoa powder.', 'price' => 280, 'is_veg' => 1, 'tag' => 'Signature', 'image' => 'assets/images/cafe/menu-cover-spaghetti.jpg'],
        ['category' => 'desserts', 'name' => 'Warm Belgian Molten Cake', 'description' => 'Gooey chocolate center served warm with Madagascan vanilla bean gelato and dark chocolate crumble.', 'price' => 310, 'is_veg' => 1, 'tag' => 'Bestseller', 'image' => 'assets/images/cafe/hero-table.jpg'],
        ['category' => 'desserts', 'name' => 'Baked New York Blueberry Cheesecake', 'description' => 'Creamy Philadelphia cheesecake on a crunchy graham crust crowned with slow-reduced wild berry compote.', 'price' => 290, 'is_veg' => 1, 'tag' => 'Chef Special', 'image' => 'assets/images/dishes/bruschetta-carved.jpg'],
        ['category' => 'desserts', 'name' => 'Espresso Affogato Al Caffe', 'description' => 'Two scoops of artisanal vanilla bean gelato drowned in a freshly pulled double shot of RiTiVerse espresso.', 'price' => 210, 'is_veg' => 1, 'tag' => 'Classic', 'image' => 'assets/images/dishes/orange-espresso-tonic.jpg'],
    ];

    $itemCount = 0;
    foreach ($menuItems as $index => $item) {
        $catId = $categoryMap[$item['category']] ?? null;
        if (!$catId) continue;

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['name'])));
        $existing = MenuItem::findBy('slug', $slug);

        if (!$existing) {
            $pricePaise = Money::toPaise($item['price']);
            $newId = MenuItem::create([
                'category_id' => $catId,
                'name' => $item['name'],
                'slug' => $slug,
                'description' => $item['description'] ?? '',
                'price_paise' => $pricePaise,
                'is_veg' => $item['is_veg'],
                'tag' => $item['tag'] ?? null,
                'image_url' => $item['image'] ?? 'menu/unnamed.png',
                'is_available' => 1,
                'sort_order' => $index,
            ]);

            // Sample variants for beverages & pizzas
            if (str_contains($slug, 'cold-brew') || str_contains($slug, 'white') || str_contains($slug, 'espresso')) {
                ItemVariant::create(['item_id' => $newId, 'name' => 'Regular', 'price_paise' => $pricePaise, 'sort_order' => 1]);
                ItemVariant::create(['item_id' => $newId, 'name' => 'Large (Grande)', 'price_paise' => $pricePaise + 4000, 'sort_order' => 2]);
                ItemAddon::create(['item_id' => $newId, 'name' => 'Extra Espresso Shot', 'extra_price_paise' => 5000, 'is_available' => 1]);
                ItemAddon::create(['item_id' => $newId, 'name' => 'Vanilla Bean Syrup', 'extra_price_paise' => 4000, 'is_available' => 1]);
            } elseif (str_contains($slug, 'pizza')) {
                ItemVariant::create(['item_id' => $newId, 'name' => '10-inch Medium', 'price_paise' => $pricePaise, 'sort_order' => 1]);
                ItemVariant::create(['item_id' => $newId, 'name' => '12-inch Large', 'price_paise' => $pricePaise + 12000, 'sort_order' => 2]);
                ItemAddon::create(['item_id' => $newId, 'name' => 'Extra Fresh Burrata', 'extra_price_paise' => 9000, 'is_available' => 1]);
                ItemAddon::create(['item_id' => $newId, 'name' => 'Truffle Oil Glaze', 'extra_price_paise' => 6000, 'is_available' => 1]);
            }

            $itemCount++;
        }
    }
    echo "  ✔ Seeded {$itemCount} dishes with variants & add-ons\n";

    // 4b. Seed Visual Diary Gallery Items
    echo "\n4b. Seeding Gallery Items...\n";
    $gallerySeed = [
        ['title' => 'Evening Table Spread', 'category' => 'Experience', 'image' => 'assets/images/cafe/hero-table.jpg', 'aspect' => 'aspect-[4/5]', 'caption' => 'Handcrafted snacks, artisanal pasta, signature coffee, and ambient evening lights in Vadodara.', 'sort_order' => 1],
        ['title' => 'Pesto & Sun-Dried Tomato Pizza', 'category' => 'Pizza', 'image' => 'assets/images/dishes/pesto-pizza.jpg', 'aspect' => 'aspect-[3/4]', 'caption' => 'Handcrafted crust topped with house basil-pinenut pesto and fresh cherry tomatoes.', 'sort_order' => 2],
        ['title' => 'RiTiVerse Sunset Cooler', 'category' => 'Beverages', 'image' => 'assets/images/dishes/sunset-mocktail.jpg', 'aspect' => 'aspect-[3/4]', 'caption' => 'Layered pomegranate and citrus cooler infused with fresh mint leaves.', 'sort_order' => 3],
        ['title' => 'Artisanal Burrata Crostini', 'category' => 'Starters', 'image' => 'assets/images/dishes/bruschetta-burrata.jpg', 'aspect' => 'aspect-[4/5]', 'caption' => 'Toasted bread with fresh burrata, blistered cherry tomatoes, and microgreens.', 'sort_order' => 4],
        ['title' => 'Spaghetti Pomodoro & Official Menu', 'category' => 'Cafe Classics', 'image' => 'assets/images/cafe/menu-cover-spaghetti.jpg', 'aspect' => 'aspect-[3/4]', 'caption' => 'Slow-simmered pomodoro sauce with bronze-cut spaghetti and gold cutlery.', 'sort_order' => 5],
        ['title' => 'Warm Cheese Fondue Platter', 'category' => 'Starters', 'image' => 'assets/images/dishes/cheese-fondue-platter.jpg', 'aspect' => 'aspect-[4/5]', 'caption' => 'Velvety cheese fondue with crispy seasoned nachos, broccoli, and croutons.', 'sort_order' => 6],
        ['title' => 'Signature Citrus Cold Brew', 'category' => 'Coffee', 'image' => 'assets/images/dishes/orange-espresso-tonic.jpg', 'aspect' => 'aspect-[3/4]', 'caption' => 'Single origin espresso cold brew paired with orange slice and sparkling tonic.', 'sort_order' => 7],
        ['title' => 'Spiced Peri-Peri Truffle Fries', 'category' => 'Starters', 'image' => 'assets/images/dishes/peri-peri-fries.jpg', 'aspect' => 'aspect-[4/5]', 'caption' => 'Crispy golden cut fries seasoned with house spice blend and microgreens.', 'sort_order' => 8],
        ['title' => 'Heritage Garden Feast', 'category' => 'Experience', 'image' => 'assets/images/cafe/all-day-spread.jpg', 'aspect' => 'aspect-[3/4]', 'caption' => 'Fresh baked pizza, comforting plates, and aromatic brewed coffees in the garden.', 'sort_order' => 9],
        ['title' => 'Gourmet Bruschetta Art', 'category' => 'Starters', 'image' => 'assets/images/dishes/bruschetta-artisan.jpg', 'aspect' => 'aspect-[4/5]', 'caption' => 'Carefully plated heirloom tomato and herbs on grilled artisan bread.', 'sort_order' => 10],
    ];

    $galleryCount = 0;
    foreach ($gallerySeed as $g) {
        $existing = GalleryItem::findBy('title', $g['title']);
        if (!$existing) {
            GalleryItem::create([
                'title' => $g['title'],
                'category' => $g['category'],
                'image' => $g['image'],
                'aspect' => $g['aspect'],
                'caption' => $g['caption'],
                'sort_order' => $g['sort_order'],
                'is_active' => 1,
            ]);
            $galleryCount++;
        }
    }
    echo "  ✔ Seeded {$galleryCount} visual gallery items\n";

    // 5. Seed Starter Loyalty Rules
    echo "\n5. Seeding Starter Loyalty Rules...\n";
    $rules = [
        [
            'name' => '5 Visits in 30 Days Milestone',
            'trigger_type' => 'visit_milestone',
            'config_json' => json_encode([
                'visits_required' => 5,
                'window_days' => 30,
                'min_order_paise' => 20000, // ₹200 min order
                'cooldown_days' => 30,
            ]),
            'reward_json' => json_encode([
                'type' => 'free_item',
                'title' => 'Complimentary Specialty Coffee / Dessert',
                'item_name' => 'Citrus Cold Brew or Venetian Tiramisu',
            ]),
            'validity_days' => 30,
            'is_repeatable' => 1,
            'cooldown_days' => 30,
            'is_active' => 1,
        ],
        [
            'name' => 'Verified Review Reward',
            'trigger_type' => 'review_submit',
            'config_json' => json_encode(['points' => 50]),
            'reward_json' => json_encode(['type' => 'points', 'points' => 50]),
            'validity_days' => 365,
            'is_repeatable' => 1,
            'cooldown_days' => 0,
            'is_active' => 1,
        ],
        [
            'name' => 'Spend Milestone Rewards',
            'trigger_type' => 'order_spend',
            'config_json' => json_encode(['points_per_100_spent' => 10]),
            'reward_json' => json_encode(['type' => 'points_rate', 'rate' => 10]),
            'validity_days' => 365,
            'is_repeatable' => 1,
            'cooldown_days' => 0,
            'is_active' => 1,
        ]
    ];

    foreach ($rules as $r) {
        $existing = RewardRule::findBy('name', $r['name']);
        if (!$existing) {
            RewardRule::create($r);
            echo "  ✔ Loyalty Rule created: {$r['name']}\n";
        }
    }

    // 6. Seed Starter Coupons
    echo "\n6. Seeding Starter Coupons...\n";
    $coupons = [
        [
            'code' => 'CIELO10',
            'title' => '10% Off All Dining',
            'type' => 'percentage',
            'value' => 10.0,
            'min_order_paise' => 30000, // ₹300 min
            'max_discount_paise' => 10000, // ₹100 max cap
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => date('Y-m-d', strtotime('+90 days')),
            'usage_limit_total' => 1000,
            'usage_limit_per_user' => 3,
            'audience' => 'all',
            'is_active' => 1,
        ],
        [
            'code' => 'WELCOME50',
            'title' => 'Flat ₹50 Off First Table Order',
            'type' => 'flat',
            'value' => 50.0,
            'min_order_paise' => 25000, // ₹250 min
            'max_discount_paise' => 5000,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => date('Y-m-d', strtotime('+90 days')),
            'usage_limit_total' => 500,
            'usage_limit_per_user' => 1,
            'audience' => 'first_time',
            'is_active' => 1,
        ],
        [
            'code' => 'CIELOVIP',
            'title' => 'VIP Connoisseur Flat ₹100 Off',
            'type' => 'flat',
            'value' => 100.0,
            'min_order_paise' => 60000, // ₹600 min
            'max_discount_paise' => 10000,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => date('Y-m-d', strtotime('+90 days')),
            'usage_limit_total' => 200,
            'usage_limit_per_user' => 5,
            'audience' => 'vip',
            'is_active' => 1,
        ],
    ];

    foreach ($coupons as $c) {
        $existing = Coupon::findBy('code', $c['code']);
        if (!$existing) {
            Coupon::create($c);
            echo "  ✔ Coupon created: {$c['code']} ({$c['title']})\n";
        }
    }

    // 7. Seed Time-Based Offers (Happy Hours & Combos)
    echo "\n7. Seeding Promotional Offers...\n";
    $offers = [
        [
            'title' => 'Aperitivo Happy Hour - 20% Off Beverages',
            'type' => 'happy_hour',
            'rules_json' => json_encode([
                'categories' => ['coffee-beverages', 'specialty-coffee', 'beverages', 'coffee'],
                'discount_percent' => 20.0,
            ]),
            'schedule_json' => json_encode([
                'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday'],
                'start_time' => '15:00',
                'end_time' => '18:00',
            ]),
            'banner_url' => 'assets/images/cafe/happy-hour.jpg',
            'priority' => 1,
            'is_active' => 1,
        ],
        [
            'title' => 'Milano Sourdough Combo (Pasta + Coffee)',
            'type' => 'combo',
            'rules_json' => json_encode([
                'required_categories' => ['pizza-pasta', 'coffee-beverages'],
                'bundle_discount_paise' => 5000, // ₹50 savings
            ]),
            'schedule_json' => json_encode([
                'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'start_time' => '11:00',
                'end_time' => '23:30',
            ]),
            'banner_url' => 'assets/images/cafe/combo-promo.jpg',
            'priority' => 2,
            'is_active' => 1,
        ],
    ];

    foreach ($offers as $off) {
        $existing = Offer::findBy('title', $off['title']);
        if (!$existing) {
            Offer::create($off);
            echo "  ✔ Offer created: {$off['title']}\n";
        }
    }

    echo "\n8. Clearing Runtime Cache...\n";
    AppDataService::clearCache();
    echo "  ✔ AppDataService cache cleared and primed for database-first reads\n";

    echo "\n✔ Seeding completed successfully!\n";
} catch (Throwable $e) {
    echo "\n✖ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
