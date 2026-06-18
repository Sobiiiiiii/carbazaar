<?php
/**
 * SEO Helper Functions
 * Generate meta tags, Open Graph, Schema.org markup
 */

// Default SEO values
$default_seo = [
    'site_name' => 'CarBazar',
    'site_url' => 'http://localhost/carbazar/',
    'site_description' => 'Pakistan\'s #1 Online Auto Marketplace - Buy & Sell Used Cars and Genuine Spare Parts. Best deals on Honda, Toyota, Suzuki cars in Karachi, Lahore, Islamabad.',
    'site_keywords' => 'used cars pakistan, buy car online, sell car, spare parts, auto parts, honda city, toyota corolla, suzuki alto, car marketplace pakistan',
    'site_logo' => 'http://localhost/carbazar/uploads/logo.png',
    'site_image' => 'http://localhost/carbazar/uploads/og-image.jpg',
    'twitter_handle' => '@CarBazarPK',
    'fb_app_id' => '',
];

/**
 * Generate SEO meta tags
 */
function generate_seo_tags($page_data = []) {
    global $default_seo;
    
    $title = $page_data['title'] ?? 'Buy & Sell Used Cars & Spare Parts';
    $description = $page_data['description'] ?? $default_seo['site_description'];
    $keywords = $page_data['keywords'] ?? $default_seo['site_keywords'];
    $image = $page_data['image'] ?? $default_seo['site_image'];
    $url = $page_data['url'] ?? $default_seo['site_url'];
    $type = $page_data['type'] ?? 'website';
    
    // Full title with site name
    $full_title = $title . ' - ' . $default_seo['site_name'];
    
    echo '
    <!-- Primary Meta Tags -->
    <meta name="title" content="' . htmlspecialchars($full_title) . '">
    <meta name="description" content="' . htmlspecialchars($description) . '">
    <meta name="keywords" content="' . htmlspecialchars($keywords) . '">
    <meta name="author" content="CarBazar">
    <meta name="robots" content="index, follow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="' . htmlspecialchars($type) . '">
    <meta property="og:url" content="' . htmlspecialchars($url) . '">
    <meta property="og:title" content="' . htmlspecialchars($full_title) . '">
    <meta property="og:description" content="' . htmlspecialchars($description) . '">
    <meta property="og:image" content="' . htmlspecialchars($image) . '">
    <meta property="og:site_name" content="' . htmlspecialchars($default_seo['site_name']) . '">
    <meta property="og:locale" content="en_PK">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="' . htmlspecialchars($url) . '">
    <meta property="twitter:title" content="' . htmlspecialchars($full_title) . '">
    <meta property="twitter:description" content="' . htmlspecialchars($description) . '">
    <meta property="twitter:image" content="' . htmlspecialchars($image) . '">
    <meta property="twitter:site" content="' . htmlspecialchars($default_seo['twitter_handle']) . '">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="' . htmlspecialchars($url) . '">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="' . $default_seo['site_url'] . 'favicon.ico">
    <link rel="apple-touch-icon" href="' . $default_seo['site_url'] . 'apple-touch-icon.png">
    ';
}

/**
 * Generate JSON-LD Schema for Organization
 */
function generate_organization_schema() {
    global $default_seo;
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?= $default_seo['site_name'] ?>",
        "url": "<?= $default_seo['site_url'] ?>",
        "logo": "<?= $default_seo['site_logo'] ?>",
        "description": "<?= htmlspecialchars($default_seo['site_description']) ?>",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "PK"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Service",
            "availableLanguage": ["English", "Urdu"]
        },
        "sameAs": [
            "https://facebook.com/carbazarpk",
            "https://twitter.com/carbazarpk",
            "https://instagram.com/carbazarpk"
        ]
    }
    </script>
    <?php
}

/**
 * Generate JSON-LD Schema for Car Product
 */
function generate_car_schema($car) {
    global $default_seo;
    $image_url = (!empty($car['image']) && $car['image'] !== 'default.jpg') 
        ? $default_seo['site_url'] . 'uploads/' . $car['image']
        : $default_seo['site_image'];
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Car",
        "name": "<?= htmlspecialchars($car['title']) ?>",
        "brand": {
            "@type": "Brand",
            "name": "<?= htmlspecialchars($car['brand']) ?>"
        },
        "model": "<?= htmlspecialchars($car['model']) ?>",
        "vehicleModelDate": "<?= htmlspecialchars($car['year']) ?>",
        "mileageFromOdometer": {
            "@type": "QuantitativeValue",
            "value": "<?= $car['mileage'] ?>",
            "unitCode": "KMT"
        },
        "fuelType": "<?= ucfirst($car['fuel_type']) ?>",
        "vehicleTransmission": "<?= ucfirst($car['transmission']) ?>",
        "color": "<?= htmlspecialchars($car['color']) ?>",
        "image": "<?= $image_url ?>",
        "offers": {
            "@type": "Offer",
            "price": "<?= $car['price'] ?>",
            "priceCurrency": "PKR",
            "availability": "<?= $car['is_sold'] ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock' ?>",
            "seller": {
                "@type": "Person",
                "name": "<?= htmlspecialchars($car['seller_name'] ?? 'Seller') ?>"
            }
        },
        "itemCondition": "https://schema.org/UsedCondition"
    }
    </script>
    <?php
}

/**
 * Generate JSON-LD Schema for Product (Spare Parts)
 */
function generate_product_schema($product) {
    global $default_seo;
    $image_url = (!empty($product['image']) && $product['image'] !== 'default.jpg') 
        ? $default_seo['site_url'] . 'uploads/' . $product['image']
        : $default_seo['site_image'];
    
    $price = $product['discount_price'] ?? $product['price'];
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "<?= htmlspecialchars($product['name']) ?>",
        "description": "<?= htmlspecialchars(substr($product['description'] ?? '', 0, 200)) ?>",
        "image": "<?= $image_url ?>",
        "brand": {
            "@type": "Brand",
            "name": "<?= htmlspecialchars($product['brand'] ?? 'Generic') ?>"
        },
        "offers": {
            "@type": "Offer",
            "price": "<?= $price ?>",
            "priceCurrency": "PKR",
            "availability": "<?= $product['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' ?>",
            "itemCondition": "https://schema.org/NewCondition"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?= $product['rating'] ?? 4.5 ?>",
            "reviewCount": "<?= $product['reviews_count'] ?? 0 ?>"
        }
    }
    </script>
    <?php
}

/**
 * Generate Breadcrumb Schema
 */
function generate_breadcrumb_schema($items) {
    global $default_seo;
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            <?php foreach ($items as $index => $item): ?>
            {
                "@type": "ListItem",
                "position": <?= $index + 1 ?>,
                "name": "<?= htmlspecialchars($item['name']) ?>",
                "item": "<?= htmlspecialchars($default_seo['site_url'] . $item['url']) ?>"
            }<?= $index < count($items) - 1 ? ',' : '' ?>
            <?php endforeach; ?>
        ]
    }
    </script>
    <?php
}
?>
