<?php
require_once '../conn.php';

$db = new Database();
$conn = $db->getConnection();

$productID = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if (empty($productID)) {
    header("Location: products.php");
    exit();
}

// Joined Query for Product and Group details
$sql = "SELECT p.*, g.* FROM products p 
        JOIN groups g ON p.CompanyID = g.CompanyID 
        WHERE p.ProductID = '$productID' 
        LIMIT 1";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    die("Property listing not found.");
}

$data = $result->fetch_assoc();

// Filter valid images
$images = array_filter([
    $data['ProductImage1'],
    $data['ProductImage2'],
    $data['ProductImage3'],
    $data['ProductImage4'],
    $data['ProductImage5']
]);
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Rems | <?= htmlspecialchars($data['ProductTitle']) ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />
        <style>
            @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
            .animate-fade-up { animation: fadeIn 0.6s ease-out forwards; }
            .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(196, 198, 207, 0.3); }
        </style>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: "#002045",
                            secondary: "#13696a",
                            surface: "#fcf8ff",
                            "on-surface": "#171837",
                            "on-surface-variant": "#43474e",
                            "outline-variant": "#c4c6cf",
                        },
                    },
                },
            };
        </script>
    </head>
    <body class="bg-surface text-on-surface font-[Inter]">
        
        <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-outline-variant/30">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8">
                <a href="../index.php" class="text-xl font-bold tracking-tight text-primary">Rems</a>
                <a href="products.php" class="group flex items-center gap-2 text-sm font-semibold text-on-surface-variant hover:text-secondary transition-colors">
                    <span class="material-symbols-outlined text-sm transition-transform group-hover:-translate-x-1">arrow_back</span>
                    Back to Listings
                </a>
            </div>
        </header>

        <main class="pt-24 pb-20">
            <div class="mx-auto max-w-7xl px-6 md:px-8">
                
                <!-- NEW: Integrated Search Form routing to products.php -->
                <div class="mb-10 animate-fade-up">
                    <form
                        id="search-form"
                        action="products.php"
                        method="GET"
                        class="flex flex-col gap-2 rounded-2xl border border-outline-variant/20 bg-white p-4 shadow-xl md:flex-row md:items-center md:rounded-full md:p-2"
                    >
                        <!-- Location / Keyword Search -->
                        <div class="flex flex-1 items-center gap-2 px-4 py-2">
                            <span class="material-symbols-outlined text-on-surface-variant">location_on</span>
                            <input
                                id="location"
                                name="search"
                                type="text"
                                placeholder="Search locations or keywords..."
                                class="w-full bg-transparent text-sm outline-none placeholder:text-on-surface-variant/60"
                            />
                        </div>
                        
                        <div class="hidden h-8 w-px bg-outline-variant/30 md:block"></div>
                        
                        <!-- Property Type -->
                        <div class="flex flex-1 items-center gap-2 px-4 py-2">
                            <span class="material-symbols-outlined text-on-surface-variant">home</span>
                            <select id="propertyType" name="type" class="w-full bg-transparent text-sm outline-none cursor-pointer">
                                <option value="">All Types</option>
                                <option value="Projects">Projects</option>
                                <option value="Commercial">Rental</option>
                                <option value="Land">Land Plot</option>
                                <option value="Services">Services</option>
                            </select>
                        </div>

                        <div class="hidden h-8 w-px bg-outline-variant/30 md:block"></div>
                        
                        <!-- Price Range -->
                        <div class="flex flex-1 items-center gap-2 px-4 py-2">
                            <span class="material-symbols-outlined text-on-surface-variant">payments</span>
                            <select id="price" name="price" class="w-full bg-transparent text-sm outline-none cursor-pointer">
                                <option value="">Any Price</option>
                                <option value="0-5000000">Under 50 Lakhs</option>
                                <option value="5000000-10000000">50 Lakhs - 1 Crore</option>
                                <option value="10000000-50000000">1 Crore - 5 Crores</option>
                                <option value="50000000-9999999999">Above 5 Crores</option>
                            </select>
                        </div>
                        
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-xl bg-primary p-4 text-white transition-colors hover:bg-secondary md:w-auto md:rounded-full md:p-4"
                        >
                            <span class="material-symbols-outlined">search</span>
                        </button>
                    </form>
                </div>
                <!-- END Search Form -->

                <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 animate-fade-up" style="animation-delay: 0.1s;">
                    
                    <div class="lg:col-span-8 space-y-6">
                        <div class="relative aspect-[16/9] overflow-hidden rounded-[2rem] bg-slate-200 shadow-2xl">
                            <img id="main-image" src="<?= htmlspecialchars($images[0]) ?>" class="h-full w-full object-cover transition-all duration-700" alt="Property">
                            <div class="absolute bottom-6 left-6 flex gap-2">
                                <span class="rounded-full bg-primary/90 px-4 py-1.5 text-xs font-bold uppercase text-white backdrop-blur-md">
                                    <?= htmlspecialchars($data['ProductStatus']) ?>
                                </span>
                                <span class="rounded-full bg-white/90 px-4 py-1.5 text-xs font-bold uppercase text-primary backdrop-blur-md">
                                    <?= htmlspecialchars($data['ProductCategory']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide">
                            <?php foreach($images as $img): ?>
                                <button onclick="document.getElementById('main-image').src='<?= htmlspecialchars($img) ?>'" 
                                        class="h-24 w-32 flex-shrink-0 overflow-hidden rounded-2xl border-2 border-transparent hover:border-secondary focus:border-secondary transition-all active:scale-95 shadow-sm">
                                    <img src="<?= htmlspecialchars($img) ?>" class="h-full w-full object-cover" alt="Thumbnail">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="lg:col-span-4 space-y-6">
                        <div class="p-8 rounded-[2rem] glass-card shadow-xl transition-all hover:shadow-2xl">
                            <div class="mb-6 flex items-center gap-4 border-b border-outline-variant/30 pb-6">
                                <img src="<?= htmlspecialchars($data['LogoAddress']) ?>" alt="Logo" class="h-16 w-16 rounded-2xl object-cover shadow-sm">
                                <div>
                                    <h2 class="text-xl font-extrabold text-primary"><?= htmlspecialchars($data['CompanyName']) ?></h2>
                                    <p class="text-xs font-semibold text-secondary uppercase tracking-widest">Verified Partner</p>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div class="flex items-start gap-3">
                                    <span class="material-symbols-outlined text-secondary">location_on</span>
                                    <div>
                                        <p class="text-xs font-bold text-on-surface-variant uppercase">Office</p>
                                        <p class="text-sm font-medium leading-relaxed"><?= htmlspecialchars($data['Office']) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-secondary">call</span>
                                    <div>
                                        <p class="text-xs font-bold text-on-surface-variant uppercase">Contact</p>
                                        <p class="text-sm font-medium"><?= htmlspecialchars($data['ContactNumber']) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-secondary">mail</span>
                                    <div>
                                        <p class="text-xs font-bold text-on-surface-variant uppercase">Email</p>
                                        <p class="text-sm font-medium"><?= htmlspecialchars($data['ContactEmail']) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 space-y-3">
                                <a href="mailto:<?= $data['ContactEmail'] ?>" class="block w-full rounded-2xl bg-primary py-4 text-center text-sm font-bold text-white transition-all hover:bg-primary/90 hover:scale-[1.02] active:scale-95 shadow-lg">
                                    Inquire Now
                                </a>
                                <a href="<?= htmlspecialchars($data['PageLink']) ?>" target="_blank" class="block w-full rounded-2xl border border-primary py-4 text-center text-sm font-bold text-primary transition-all hover:bg-surface-low">
                                    Visit Website
                                </a>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-[2rem] shadow-lg grayscale hover:grayscale-0 transition-all duration-500">
                            <img src="<?= htmlspecialchars($data['SecondaryImage']) ?>" class="h-40 w-full object-cover" alt="Company Showcase">
                        </div>
                    </div>
                </div>

                <div class="mt-16 grid grid-cols-1 lg:grid-cols-12 animate-fade-up" style="animation-delay: 0.2s;">
                    <div class="lg:col-span-8">
                        <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-outline-variant/30 pb-10">
                            <div>
                                <h1 class="text-4xl font-black text-primary mb-2"><?= htmlspecialchars($data['ProductTitle']) ?></h1>
                                <p class="text-sm font-bold text-on-surface-variant uppercase tracking-tighter">ID: <?= htmlspecialchars($data['ProductID']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-secondary uppercase mb-1"><?= htmlspecialchars($data['PriceType']) ?></p>
                                <p class="text-4xl font-black text-primary">BDT <?= htmlspecialchars($data['ProductPrice']) ?></p>
                            </div>
                        </div>

                        <div class="prose max-w-none">
                            <h3 class="flex items-center gap-2 text-2xl font-bold text-primary mb-6">
                                <span class="material-symbols-outlined">description</span>
                                Property Description
                            </h3>
                            <div class="text-lg leading-relaxed text-on-surface-variant whitespace-pre-wrap">
                                <?= nl2br(htmlspecialchars($data['ProductDescription'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <footer class="border-t border-outline-variant/30 py-12 text-center">
            <p class="text-sm font-medium text-on-surface-variant">© 2026 Rems. Developed with advanced AI curation.</p>
        </footer>

    </body>
</html>