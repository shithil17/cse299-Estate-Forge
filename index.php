<?php
require_once 'conn.php';
session_start();

$db = new Database();
$conn = $db->getConnection();

$isLoggedIn = false;
$firstName = "";
$sid = "";

// 1. Capture Session ID from URL or Session
if (!empty($_GET['sid'])) {
    $sid = $_GET['sid'];
    $_SESSION['SessionID'] = $sid;
} elseif (!empty($_SESSION['SessionID'])) {
    $sid = $_SESSION['SessionID'];
}

// 2. Validate Session against Database
if (!empty($sid)) {
    $stmt = $conn->prepare("SELECT ui.FirstName FROM session s JOIN userinfo ui ON s.UserID = ui.UserID WHERE s.SessionID = ?");
    if ($stmt) {
        $stmt->bind_param("s", $sid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $isLoggedIn = true;
            $firstName = htmlspecialchars($row['FirstName']);
        } else {
            $sid = "";
            unset($_SESSION['SessionID']);
            unset($_SESSION['UserID']);
        }
        $stmt->close();
    }
}

// 3. Helper Function to safely append SessionID to links
function buildUrl($url, $sid) {
    if (empty($sid)) return $url;
    if (strpos($url, '#') === 0 || strpos($url, 'mailto:') === 0 || strpos($url, 'http') === 0) return $url;
    $hash = '';
    if (strpos($url, '#') !== false) {
        $parts = explode('#', $url);
        $url   = $parts[0];
        $hash  = '#' . $parts[1];
    }
    $separator = (strpos($url, '?') === false) ? '?' : '&';
    return $url . $separator . 'sid=' . urlencode($sid) . $hash;
}

// 4. Fetch featured products (up to 30, JS rotates 3 at a time)
$featuredProducts = [];
$pStmt = $conn->prepare("
    SELECT p.ProductID, p.ProductTitle, p.ProductCategory,
           p.ProductImage1, p.ProductPrice, p.PriceType, p.ProductStatus,
           g.CompanyName, g.Office
    FROM products p
    JOIN `groups` g ON p.CompanyID = g.CompanyID
    WHERE p.ProductStatus IN ('Available', 'Ongoing', 'Upcoming')
    ORDER BY RAND()
    LIMIT 30
");
if ($pStmt) {
    $pStmt->execute();
    $pResult = $pStmt->get_result();
    while ($pRow = $pResult->fetch_assoc()) {
        $featuredProducts[] = $pRow;
    }
    $pStmt->close();
}
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rems | AI-Driven Real Estate Hub</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#002045",
                        secondary: "#13696a",
                        tertiary: "#00261d",
                        background: "#fcf8ff",
                        surface: "#fcf8ff",
                        "surface-low": "#f5f2ff",
                        "surface-high": "#e8e6ff",
                        "surface-highest": "#e1e0ff",
                        "on-surface": "#171837",
                        "on-surface-variant": "#43474e",
                        "outline-variant": "#c4c6cf",
                        "primary-container": "#1a365d",
                        "secondary-container": "#a2eded",
                        "tertiary-fixed": "#4afdd3",
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="styles.css" />
    <style>
        .product-card-hidden { display: none !important; }
    </style>
</head>
<body class="bg-surface text-on-surface font-[Inter]">

    <!-- ── HEADER ─────────────────────────────────────────────────────────── -->
    <header class="fixed top-0 z-50 w-full glass-nav shadow-[0_32px_32px_-12px_rgba(23,24,55,0.04)]">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8">
            <a href="<?= buildUrl('index.php', $sid) ?>" class="text-xl font-semibold tracking-tight text-primary">Rems</a>

            <nav id="desktop-nav" class="hidden items-center gap-8 text-sm font-medium tracking-tight md:flex">
                <a href="#home"      class="border-b-2 border-secondary pb-1 font-semibold text-secondary">Home</a>
                <a href="#featured"  class="text-on-surface-variant transition-colors hover:text-secondary">Properties</a>
                <a href="#ecosystem" class="text-on-surface-variant transition-colors hover:text-secondary">AI Tools</a>
                <a href="<?= buildUrl('screens/cost-estimator/index.php', $sid) ?>"
                   class="text-on-surface-variant transition-colors hover:text-secondary">Cost Estimator</a>
                <a href="#contact"   class="text-on-surface-variant transition-colors hover:text-secondary">Contact</a>
            </nav>

            <div class="hidden items-center gap-4 md:flex">
                <?php if ($isLoggedIn): ?>
                    <span class="text-sm font-semibold text-primary mr-2">Hello, <?= $firstName ?></span>
                <?php else: ?>
                    <a href="<?= buildUrl('login.php', $sid) ?>"
                       class="rounded-lg px-4 py-2 text-sm font-medium text-on-surface-variant transition-all hover:bg-surface-low">
                        Login
                    </a>
                <?php endif; ?>
                <a href="<?= buildUrl('settings.php', $sid) ?>"
                   class="rounded-lg bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white">
                    Settings
                </a>
            </div>

            <button id="menu-toggle" class="md:hidden rounded-lg p-2 text-primary"
                    aria-label="Toggle menu" aria-expanded="false">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>

        <!-- Mobile nav -->
        <div id="mobile-nav" class="hidden border-t border-outline-variant/30 bg-white/95 px-6 py-4 md:hidden">
            <div class="flex flex-col gap-3 text-sm font-medium">
                <?php if ($isLoggedIn): ?>
                    <span class="block px-4 py-2 text-primary font-semibold bg-surface-low rounded-lg mb-2">
                        Hello, <?= $firstName ?>
                    </span>
                <?php endif; ?>
                <a href="#home"      class="mobile-link">Home</a>
                <a href="#featured"  class="mobile-link">Properties</a>
                <a href="#ecosystem" class="mobile-link">AI Tools</a>
                <a href="<?= buildUrl('screens/cost-estimator/index.php', $sid) ?>" class="mobile-link">Cost Estimator</a>
                <a href="#contact"   class="mobile-link">Contact</a>
                <?php if (!$isLoggedIn): ?>
                    <a href="<?= buildUrl('login.php', $sid) ?>" class="mobile-link text-secondary">Login</a>
                <?php endif; ?>
                <a href="<?= buildUrl('settings.php', $sid) ?>" class="mobile-link text-primary">Settings</a>
            </div>
        </div>
    </header>

    <main id="home" class="pt-16">

        <!-- ── HERO ───────────────────────────────────────────────────────── -->
        <section class="relative overflow-hidden bg-surface">
            <div class="mx-auto grid min-h-[calc(100vh-4rem)] max-w-7xl items-center gap-12 px-6 py-16 md:grid-cols-2 md:px-8">
                <div class="space-y-8 reveal">
                    <div class="inline-flex items-center gap-2 rounded-full bg-secondary-container px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-950">
                        <span class="material-symbols-outlined text-sm">auto_awesome</span>
                        Next-Gen Real Estate
                    </div>
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-primary md:text-6xl">
                        Your Complete Real Estate Hub
                        <span class="block text-secondary">Powered by AI</span>
                    </h1>
                    <p class="max-w-xl text-lg leading-relaxed text-on-surface-variant">
                        Discover verified properties across Bangladesh where AI helps evaluate value,
                        ownership confidence, and regional market movement before you invest.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#featured"
                           class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-primary to-primary-container px-8 py-4 font-bold text-white transition-all hover:-translate-y-1 hover:shadow-lg">
                            Browse Properties
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                        <a href="#contact"
                           class="rounded-xl bg-surface-high px-8 py-4 font-bold text-primary transition-all hover:bg-surface-highest">
                            List Your Property
                        </a>
                    </div>
                </div>

                <div class="relative reveal">
                    <div class="overflow-hidden rounded-[2rem] shadow-2xl md:rotate-2 md:transition-transform md:hover:rotate-0">
                        <img src="assets/images/hero-property.png" alt="Luxury modern architectural villa"
                             class="h-[420px] w-full object-cover md:h-[620px]" />
                    </div>
                    <div class="-left-2 -bottom-6 mt-6 max-w-xs rounded-2xl border border-outline-variant/20 bg-white p-5 shadow-xl md:absolute md:mt-0">
                        <div class="mb-4 flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-tertiary-fixed">
                                <span class="material-symbols-outlined text-emerald-950">insights</span>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-primary">AI Valuation</div>
                                <div class="text-xs text-on-surface-variant">Estimated +12.4% Growth</div>
                            </div>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-high">
                            <div class="h-full w-3/4 bg-secondary"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── SEARCH BAR ─────────────────────────────────────────────────── -->
        <!--
            On submit this form GETs pages/products.php with params:
              ?search=...  full-text against ProductTitle, ProductDescription, CompanyName, Office
              &type=...    matches ProductCategory exactly
              &price=...   "min-max" range parsed server-side in products.php
              &sid=...     session forwarding
        -->
        <section class="relative z-30 mx-auto -mt-10 max-w-6xl px-6 md:px-8">
            <form id="search-form" action="<?= buildUrl('pages/products.php', $sid) ?>" method="GET"
                  class="flex flex-col gap-2 rounded-2xl border border-outline-variant/20 bg-white p-4 shadow-2xl md:flex-row md:items-center md:rounded-full md:p-2">

                <?php if (!empty($sid)): ?>
                    <input type="hidden" name="sid" value="<?= htmlspecialchars($sid) ?>" />
                <?php endif; ?>

                <!-- Keyword / Location -->
                <div class="search-field">
                    <span class="material-symbols-outlined text-on-surface-variant">location_on</span>
                    <input id="location" name="search" type="text"
                           placeholder="Search properties, companies..."
                           class="search-input" />
                </div>

                <!-- Category — option values MUST match ProductCategory in DB exactly -->
                <div class="search-field">
                    <span class="material-symbols-outlined text-on-surface-variant">home</span>
                    <select id="propertyType" name="type" class="search-input">
                        <option value="">All Types</option>
                        <option value="Property">Property</option>
                        <option value="Raw Material">Raw Material</option>
                        <option value="Rental">Rental</option>
                        <option value="Land">Land</option>
                        <option value="Services">Services</option>
                    </select>
                </div>

                <!-- Price Range -->
                <div class="search-field">
                    <span class="material-symbols-outlined text-on-surface-variant">payments</span>
                    <select id="price" name="price" class="search-input">
                        <option value="">Any Price</option>
                        <option value="0-5000000">Under 50 Lakhs</option>
                        <option value="5000000-10000000">50 Lakhs – 1 Crore</option>
                        <option value="10000000-50000000">1 Crore – 5 Crores</option>
                        <option value="50000000-9999999999">Above 5 Crores</option>
                    </select>
                </div>

                <button type="submit"
                        class="flex w-full items-center justify-center rounded-xl bg-primary p-4 text-white transition-colors hover:bg-secondary md:w-auto md:rounded-full md:p-5">
                    <span class="material-symbols-outlined">search</span>
                </button>
            </form>
        </section>

        <!-- ── FEATURED LISTINGS ──────────────────────────────────────────── -->
        <section id="featured" class="mx-auto max-w-7xl px-6 py-24 md:px-8">
            <div class="mb-12 flex flex-col items-start justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <h2 class="mb-2 text-4xl font-bold tracking-tight text-primary">Featured Listings</h2>
                    <p class="text-on-surface-variant">Handpicked properties verified by our AI protocol.</p>
                </div>
                <a href="<?= buildUrl('pages/products.php', $sid) ?>"
                   class="group flex items-center gap-2 font-bold text-secondary transition-all hover:gap-4">
                    View All Properties
                    <span class="material-symbols-outlined">trending_flat</span>
                </a>
            </div>

            <?php if (empty($featuredProducts)): ?>
                <p class="py-16 text-center text-on-surface-variant">No listings available right now. Check back soon.</p>
            <?php else: ?>

                <div id="featured-grid" class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <?php foreach ($featuredProducts as $i => $p):
                        // Status badge colour
                        $badgeClass = match($p['ProductStatus']) {
                            'Available'     => 'bg-emerald-300 text-emerald-950',
                            'Ongoing'       => 'bg-cyan-200 text-cyan-950',
                            'Upcoming'      => 'bg-yellow-200 text-yellow-900',
                            'Sold'          => 'bg-red-200 text-red-900',
                            default         => 'bg-gray-200 text-gray-800',
                        };
                        $img       = !empty($p['ProductImage1']) ? htmlspecialchars($p['ProductImage1']) : 'assets/images/placeholder.png';
                        $detailUrl = buildUrl('pages/view-product.php?id=' . urlencode($p['ProductID']), $sid);
                    ?>
                    <article class="property-card reveal<?= $i >= 3 ? ' product-card-hidden' : '' ?>"
                             data-card-index="<?= $i ?>">
                        <div class="relative aspect-square overflow-hidden">
                            <img src="<?= $img ?>"
                                 alt="<?= htmlspecialchars($p['ProductTitle']) ?>"
                                 class="property-image"
                                 onerror="this.src='assets/images/placeholder.png'" />
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($p['ProductStatus']) ?></span>
                            <?php if (!empty($p['ProductPrice'])): ?>
                                <span class="price-pill">BDT <?= htmlspecialchars($p['ProductPrice']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <p class="mb-1 flex items-center gap-1 text-xs text-on-surface-variant">
                                <span class="material-symbols-outlined text-xs">business</span>
                                <?= htmlspecialchars($p['CompanyName']) ?>
                            </p>
                            <p class="mb-2 flex items-center gap-1 text-xs text-on-surface-variant">
                                <span class="material-symbols-outlined text-xs">location_on</span>
                                <?= htmlspecialchars($p['Office']) ?>
                            </p>
                            <h3 class="mb-4 line-clamp-2 text-xl font-bold text-primary">
                                <?= htmlspecialchars($p['ProductTitle']) ?>
                            </h3>
                            <a href="<?= $detailUrl ?>" class="cta-secondary inline-block text-center">View Details</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination dots -->
                <div id="featured-dots" class="mt-8 flex justify-center gap-2"></div>

            <?php endif; ?>
        </section>

        <!-- ── ECOSYSTEM ──────────────────────────────────────────────────── -->
        <section id="ecosystem" class="mx-auto max-w-7xl px-6 pb-24 md:px-8">
            <div class="mb-12 text-center">
                <h2 class="mb-3 text-4xl font-bold tracking-tight text-primary md:text-5xl">Intelligent Ecosystem</h2>
                <p class="mx-auto max-w-3xl text-on-surface-variant">
                    Beyond listings, we provide the tools for a smoother real estate experience powered by custom-trained AI models.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <article class="rounded-2xl bg-surface-high p-6 shadow-[0_24px_48px_-28px_rgba(23,24,55,0.2)]">
                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-white text-primary shadow-sm">
                        <span class="material-symbols-outlined">description</span>
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-primary">Property Description Generator</h3>
                    <p class="mb-6 text-sm leading-relaxed text-on-surface-variant">
                        Upload a few photos, and our AI writes high-converting, localized descriptions in seconds across multiple languages.
                    </p>
                    <a href="#featured" class="inline-flex items-center gap-2 font-semibold text-secondary transition-all hover:gap-3">
                        Try it now <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </a>
                </article>
                <article class="rounded-2xl bg-white p-6 shadow-[0_24px_48px_-28px_rgba(23,24,55,0.2)]">
                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-secondary-container text-emerald-950 shadow-sm">
                        <span class="material-symbols-outlined">verified</span>
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-primary">Smart Verify</h3>
                    <p class="text-sm leading-relaxed text-on-surface-variant">
                        Automated blockchain-backed document verification for properties and ownership history.
                    </p>
                </article>
                <article class="rounded-2xl bg-white p-6 shadow-[0_24px_48px_-28px_rgba(23,24,55,0.2)]">
                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-surface-high text-secondary shadow-sm">
                        <span class="material-symbols-outlined">calendar_month</span>
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-primary">Inquiry Auto-Response</h3>
                    <p class="text-sm leading-relaxed text-on-surface-variant">
                        24/7 intelligent agent that handles leads, answers common questions, and schedules property viewings.
                    </p>
                </article>
            </div>
        </section>

        <!-- ── HOW IT WORKS ───────────────────────────────────────────────── -->
        <section class="mx-auto max-w-7xl px-6 pb-24 md:px-8">
            <div class="mb-12 text-center">
                <h2 class="mb-3 text-4xl font-bold tracking-tight text-primary md:text-5xl">Streamlined for Everyone</h2>
                <p class="mx-auto max-w-2xl text-on-surface-variant">
                    A simple workflow for buyers, sellers, and developers to move from discovery to action with less friction.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <article class="rounded-2xl bg-white p-8 text-center shadow-[0_20px_40px_-28px_rgba(23,24,55,0.2)]">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-surface-high text-primary">
                        <span class="material-symbols-outlined">person_add</span>
                    </div>
                    <h3 class="mb-3 text-lg font-bold text-primary">1. Register</h3>
                    <p class="text-sm leading-relaxed text-on-surface-variant">Create an account for personalized market insights and property saves.</p>
                </article>
                <article class="rounded-2xl bg-white p-8 text-center shadow-[0_20px_40px_-28px_rgba(23,24,55,0.2)]">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-surface-high text-primary">
                        <span class="material-symbols-outlined">fact_check</span>
                    </div>
                    <h3 class="mb-3 text-lg font-bold text-primary">2. AI Verifies</h3>
                    <p class="text-sm leading-relaxed text-on-surface-variant">Our system checks listing data accuracy and property validity before anything goes live.</p>
                </article>
                <article class="rounded-2xl bg-white p-8 text-center shadow-[0_20px_40px_-28px_rgba(23,24,55,0.2)]">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-surface-high text-primary">
                        <span class="material-symbols-outlined">handshake</span>
                    </div>
                    <h3 class="mb-3 text-lg font-bold text-primary">3. Find & Contact</h3>
                    <p class="text-sm leading-relaxed text-on-surface-variant">Seamlessly connect with sellers or developers through our secure messaging portal.</p>
                </article>
            </div>
        </section>

        <!-- ── CTA BANNER ─────────────────────────────────────────────────── -->
        <section class="mx-auto max-w-7xl px-6 pb-24 md:px-8">
            <div class="rounded-[2rem] bg-gradient-to-r from-primary to-primary-container px-8 py-10 text-white md:px-12 md:py-12">
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                    <div>
                        <h2 class="mb-3 text-3xl font-bold md:text-4xl">Ready to list your property or find your next investment?</h2>
                        <p class="max-w-2xl text-blue-100">Start with Rems today and move from browsing to action with fewer steps.</p>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <a href="<?= buildUrl('pages/providers.php', $sid) ?>"
                           class="rounded-xl bg-tertiary-fixed px-6 py-3 font-bold text-emerald-950 transition-transform hover:scale-105">
                            Get Started
                        </a>
                        <a href="mailto:sales@Rems.bd"
                           class="rounded-xl border border-white/20 bg-white/10 px-6 py-3 font-bold text-white transition-colors hover:bg-white/20">
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ── FOOTER ─────────────────────────────────────────────────────────── -->
    <footer id="contact" class="bg-surface-low text-on-surface">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 py-14 md:grid-cols-4 md:px-8">
            <div>
                <span class="mb-4 block text-2xl font-bold text-primary">Rems</span>
                <p class="text-sm leading-relaxed text-on-surface-variant">Redefining real estate with intelligent curation and verified security.</p>
            </div>
            <div>
                <h4 class="mb-4 font-bold text-primary">Platform</h4>
                <ul class="space-y-3 text-sm text-on-surface-variant">
                    <li><a href="https://www.google.com/maps/search/property+developers+in+bangladesh" class="hover:text-secondary">Search Map</a></li>
                    <li><a href="<?= buildUrl('screens/cost-estimator/index.php', $sid) ?>" class="hover:text-secondary">AI Estimator</a></li>
                    <li><a href="mailto:partners@Rems.bd" class="hover:text-secondary">Developer Portal</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 font-bold text-primary">Company</h4>
                <ul class="space-y-3 text-sm text-on-surface-variant">
                    <li><a href="#home" class="hover:text-secondary">About Us</a></li>
                    <li><a href="mailto:careers@Rems.bd" class="hover:text-secondary">Careers</a></li>
                    <li><a href="mailto:legal@Rems.bd" class="hover:text-secondary">Privacy Policy</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 font-bold text-primary">Support</h4>
                <ul class="space-y-3 text-sm text-on-surface-variant">
                    <li><a href="mailto:support@Rems.bd" class="hover:text-secondary">Help Center</a></li>
                    <li><a href="mailto:sales@Rems.bd" class="hover:text-secondary">Contact Sales</a></li>
                    <li><a href="mailto:api@Rems.bd" class="hover:text-secondary">API Docs</a></li>
                </ul>
            </div>
        </div>
        <div class="mx-auto flex max-w-7xl flex-col gap-2 border-t border-outline-variant/30 px-6 py-6 text-sm text-on-surface-variant md:flex-row md:items-center md:justify-between md:px-8">
            <span>© 2026 Rems. AI-Driven Real Estate Curation.</span>
            <div class="flex gap-6">
                <a href="<?= buildUrl('screens/cost-estimator/index.php', $sid) ?>" class="hover:text-secondary">System Status</a>
                <a href="mailto:security@Rems.bd" class="hover:text-secondary">Security</a>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>

    <!-- ── CARD ROTATION (every 5 s, shows 3 at a time) ──────────────────── -->
    <script>
    (function () {
        const grid   = document.getElementById('featured-grid');
        const dotsEl = document.getElementById('featured-dots');
        if (!grid) return;

        const cards   = Array.from(grid.querySelectorAll('article[data-card-index]'));
        const total   = cards.length;
        const perPage = 3;
        const pages   = Math.ceil(total / perPage);

        if (total <= perPage) {
            // No rotation needed — show all, hide dots
            cards.forEach(c => c.classList.remove('product-card-hidden'));
            if (dotsEl) dotsEl.style.display = 'none';
            return;
        }

        let current = 0;

        // Build dot buttons
        const dots = [];
        for (let i = 0; i < pages; i++) {
            const d = document.createElement('button');
            d.className = 'w-2.5 h-2.5 rounded-full transition-colors';
            d.setAttribute('aria-label', 'Go to page ' + (i + 1));
            d.addEventListener('click', () => { goTo(i); resetTimer(); });
            dotsEl.appendChild(d);
            dots.push(d);
        }

        function goTo(page) {
            current = page;
            cards.forEach((c, idx) => {
                const inRange = idx >= page * perPage && idx < (page + 1) * perPage;
                c.classList.toggle('product-card-hidden', !inRange);
            });
            dots.forEach((d, i) => {
                d.className = 'w-2.5 h-2.5 rounded-full transition-colors ' +
                    (i === current ? 'bg-secondary scale-125' : 'bg-outline-variant');
            });
        }

        goTo(0);

        let timer = setInterval(() => goTo((current + 1) % pages), 5000);
        function resetTimer() { clearInterval(timer); timer = setInterval(() => goTo((current + 1) % pages), 5000); }
    })();
    </script>

</body>
</html>
