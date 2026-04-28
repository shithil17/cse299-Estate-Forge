<?php
require_once '../conn.php';

$db = new Database();
$conn = $db->getConnection();

// 1. Pagination Configuration
$productsPerPage = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $productsPerPage;

// 2. Search Handling
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = "";
if (!empty($searchTerm)) {
    $safeSearch = $conn->real_escape_string($searchTerm);
    $searchQuery = " WHERE ProductTitle LIKE '%$safeSearch%' OR ProductCategory LIKE '%$safeSearch%'";
}

// 3. Get Total Count for Pagination
$countSql = "SELECT COUNT(*) as total FROM products" . $searchQuery;
$countResult = $conn->query($countSql);
$totalProducts = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $productsPerPage);

// 4. Fetch Products for Current Page
$sql = "SELECT * FROM products" . $searchQuery . " LIMIT $offset, $productsPerPage";
$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Rems | Properties</title>
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
        
        <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md shadow-sm">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8">
                <a href="index.html" class="text-xl font-semibold tracking-tight text-primary">Rems</a>
                <div class="hidden items-center gap-4 md:flex">
                    <form method="GET" action="products.php" class="relative">
                        <input 
                            type="text" name="search" placeholder="Search..." 
                            value="<?= htmlspecialchars($searchTerm) ?>"
                            class="rounded-full border border-outline-variant bg-surface-low px-4 py-1.5 pl-10 text-sm focus:ring-1 focus:ring-secondary outline-none"
                        />
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-sm text-on-surface-variant">search</span>
                    </form>
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16">
            <div class="mx-auto max-w-7xl px-6 md:px-8">
                
                <div class="mb-8 flex items-end justify-between">
                    <h1 class="text-3xl font-bold text-primary">Properties</h1>
                    <p class="text-sm text-on-surface-variant">Showing <?= $result->num_rows ?> of <?= $totalProducts ?> results</p>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                        <?php while($row = $result->fetch_assoc()): ?>
                            <a href="view-product.php?id=<?= urlencode($row['ProductID']) ?>" class="group block overflow-hidden rounded-2xl border border-outline-variant/30 bg-white transition-all hover:shadow-lg">
                                <div class="relative aspect-square overflow-hidden bg-slate-100">
                                    <img 
                                        src="<?= htmlspecialchars($row['ProductImage1']) ?>" 
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        alt="Property Image"
                                    />
                                    <div class="absolute top-3 left-3 flex flex-wrap gap-2">
                                        <span class="rounded-md bg-primary/90 px-2 py-1 text-[10px] font-bold uppercase text-white backdrop-blur">
                                            <?= htmlspecialchars($row['ProductStatus']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="mb-1 flex items-center justify-between">
                                        <span class="text-xs font-medium text-secondary"><?= htmlspecialchars($row['ProductCategory']) ?></span>
                                        <span class="text-[10px] text-on-surface-variant uppercase font-bold"><?= htmlspecialchars($row['PriceType']) ?></span>
                                    </div>
                                    <h3 class="mb-2 font-bold text-primary truncate"><?= htmlspecialchars($row['ProductTitle']) ?></h3>
                                    <div class="text-lg font-extrabold text-primary">
                                        BDT <?= htmlspecialchars($row['ProductPrice']) ?>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="mt-12 flex items-center justify-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>" class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant hover:bg-surface-high transition-colors">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>" 
                               class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold transition-colors <?= $i === $page ? 'bg-primary text-white' : 'border border-outline-variant hover:bg-surface-high' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>" class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant hover:bg-surface-high transition-colors">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-24 text-center">
                        <span class="material-symbols-outlined text-5xl text-outline-variant">search_off</span>
                        <h2 class="mt-4 text-xl font-bold text-primary">No products available</h2>
                        <p class="text-on-surface-variant">Try adjusting your search or filters.</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>

        <footer class="mt-auto border-t border-outline-variant/30 py-8 text-center text-xs text-on-surface-variant">
            © 2026 Rems. AI-Driven Real Estate Curation.
        </footer>

    </body>
</html>