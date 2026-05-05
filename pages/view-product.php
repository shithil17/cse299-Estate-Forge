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

/*
|--------------------------------------------------------------------------
| Fix image paths
|--------------------------------------------------------------------------
*/
function fixImagePath($path) {
    if (empty($path)) return '';

    // keep external links unchanged
    if (
        str_starts_with($path, 'http://') ||
        str_starts_with($path, 'https://') ||
        str_starts_with($path, '../')
    ) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

/*
|--------------------------------------------------------------------------
| Product images
|--------------------------------------------------------------------------
*/
$images = array_values(array_filter([
    fixImagePath($data['ProductImage1']),
    fixImagePath($data['ProductImage2']),
    fixImagePath($data['ProductImage3']),
    fixImagePath($data['ProductImage4']),
    fixImagePath($data['ProductImage5'])
]));
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-up {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(196, 198, 207, 0.3);
        }
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

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 animate-fade-up">

            <!-- LEFT SIDE -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Main Image -->
                <div class="relative aspect-[16/9] overflow-hidden rounded-[2rem] bg-slate-200 shadow-2xl">
                    <?php if (!empty($images)): ?>
                        <img 
                            id="main-image"
                            src="<?= htmlspecialchars($images[0]) ?>"
                            class="h-full w-full object-cover transition-all duration-700"
                            alt="Property"
                        >
                    <?php else: ?>
                        <div class="flex h-full items-center justify-center text-lg font-bold text-slate-500">
                            No Image Available
                        </div>
                    <?php endif; ?>

                    <div class="absolute bottom-6 left-6 flex gap-2">
                        <span class="rounded-full bg-primary/90 px-4 py-1.5 text-xs font-bold uppercase text-white backdrop-blur-md">
                            <?= htmlspecialchars($data['ProductStatus']) ?>
                        </span>
                        <span class="rounded-full bg-white/90 px-4 py-1.5 text-xs font-bold uppercase text-primary backdrop-blur-md">
                            <?= htmlspecialchars($data['ProductCategory']) ?>
                        </span>
                    </div>
                </div>

                <!-- Thumbnails -->
                <div class="flex gap-4 overflow-x-auto pb-4">
                    <?php foreach($images as $index => $img): ?>
                        <button 
                            onclick="setImage(<?= $index ?>)"
                            class="h-24 w-32 flex-shrink-0 overflow-hidden rounded-2xl border-2 border-transparent hover:border-secondary transition-all active:scale-95 shadow-sm"
                        >
                            <img src="<?= htmlspecialchars($img) ?>" class="h-full w-full object-cover" alt="Thumbnail">
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="lg:col-span-4 space-y-6">

                <div class="p-8 rounded-[2rem] glass-card shadow-xl">
                    <div class="mb-6 flex items-center gap-4 border-b border-outline-variant/30 pb-6">
                        <img 
                            src="<?= htmlspecialchars(fixImagePath($data['LogoAddress'])) ?>" 
                            alt="Logo" 
                            class="h-16 w-16 rounded-2xl object-cover shadow-sm"
                        >
                        <div>
                            <h2 class="text-xl font-extrabold text-primary"><?= htmlspecialchars($data['CompanyName']) ?></h2>
                            <p class="text-xs font-semibold text-secondary uppercase tracking-widest">Verified Partner</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <p class="text-xs font-bold text-on-surface-variant uppercase">Office</p>
                            <p class="text-sm font-medium"><?= htmlspecialchars($data['Office']) ?></p>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-on-surface-variant uppercase">Contact</p>
                            <p class="text-sm font-medium"><?= htmlspecialchars($data['ContactNumber']) ?></p>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-on-surface-variant uppercase">Email</p>
                            <p class="text-sm font-medium"><?= htmlspecialchars($data['ContactEmail']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[2rem] shadow-lg">
                    <img 
                        src="<?= htmlspecialchars(fixImagePath($data['SecondaryImage'])) ?>" 
                        class="h-40 w-full object-cover" 
                        alt="Company Showcase"
                    >
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="mt-16 animate-fade-up">
            <h1 class="text-4xl font-black text-primary mb-2"><?= htmlspecialchars($data['ProductTitle']) ?></h1>
            <p class="text-sm font-bold text-on-surface-variant uppercase mb-6">
                ID: <?= htmlspecialchars($data['ProductID']) ?>
            </p>

            <p class="text-sm font-bold text-secondary uppercase mb-1">
                <?= htmlspecialchars($data['PriceType']) ?>
            </p>

            <p class="text-4xl font-black text-primary mb-8">
                BDT <?= htmlspecialchars($data['ProductPrice']) ?>
            </p>

            <div class="text-lg leading-relaxed text-on-surface-variant whitespace-pre-wrap">
                <?= nl2br(htmlspecialchars($data['ProductDescription'])) ?>
            </div>
        </div>

    </div>
</main>

<footer class="border-t border-outline-variant/30 py-12 text-center">
    <p class="text-sm font-medium text-on-surface-variant">© 2026 Rems</p>
</footer>

<!-- FLOATING SCHEDULE BUTTON -->
<a href="../AI/schedule.php?id=<?= urlencode($data['ProductID']) ?>" class="fixed bottom-8 right-8 z-50 group flex items-center gap-2 rounded-full bg-secondary px-4 py-4 text-white shadow-2xl transition-all duration-300 hover:bg-primary hover:px-6">
    <span class="material-symbols-outlined">notifications_active</span>
    <span class="max-w-0 overflow-hidden whitespace-nowrap text-sm font-bold opacity-0 transition-all duration-300 group-hover:max-w-xs group-hover:opacity-100">
        Want to schedule?
    </span>
</a>

<script>
    const images = <?= json_encode($images) ?>;
    let currentIndex = 0;
    const mainImage = document.getElementById("main-image");

    function setImage(index) {
        if (!mainImage) return;

        currentIndex = index;
        mainImage.style.opacity = 0;

        setTimeout(() => {
            mainImage.src = images[currentIndex];
            mainImage.style.opacity = 1;
        }, 300);
    }

    function autoSlide() {
        if (images.length <= 1) return;

        currentIndex = (currentIndex + 1) % images.length;
        setImage(currentIndex);
    }

    if (images.length > 1) {
        setInterval(autoSlide, 5000);
    }
</script>

</body>
</html>