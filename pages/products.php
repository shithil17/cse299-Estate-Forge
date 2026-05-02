<?php
require_once '../conn.php';

$db = new Database();
$conn = $db->getConnection();

// -----------------------------
// Pagination Configuration
// -----------------------------
$productsPerPage = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $productsPerPage;

// -----------------------------
// Search Handling
// -----------------------------
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

$whereClause = "";
$params = [];
$types = "";

if (!empty($searchTerm)) {
    $whereClause = " WHERE ProductTitle LIKE ? OR ProductCategory LIKE ?";
    $searchLike = "%" . $searchTerm . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "ss";
}

// -----------------------------
// Get Total Count
// -----------------------------
$countSql = "SELECT COUNT(*) as total FROM products" . $whereClause;
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalProducts = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = max(1, ceil($totalProducts / $productsPerPage));

// -----------------------------
// Fetch Products
// -----------------------------
$sql = "SELECT * FROM products" . $whereClause . " ORDER BY ProductID DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $typesWithLimit = $types . "ii";
    $bindParams = array_merge($params, [$offset, $productsPerPage]);
    $stmt->bind_param($typesWithLimit, ...$bindParams);
} else {
    $stmt->bind_param("ii", $offset, $productsPerPage);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rems | Properties</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />

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

    <!-- Header -->
    <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md shadow-sm">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8">
            <a href="../index.php" class="text-xl font-semibold tracking-tight text-primary">Rems</a>

            <form method="GET" action="products.php" class="relative">
                <input
                    type="text"
                    name="search"
                    placeholder="Search..."
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    class="rounded-full border border-outline-variant bg-white px-4 py-2 pl-10 text-sm outline-none focus:ring-2 focus:ring-secondary"
                />
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-sm text-on-surface-variant">
                    search
                </span>
            </form>
        </div>
    </header>

    <!-- Main -->
    <main class="pt-24 pb-16">
        <div class="mx-auto max-w-7xl px-6 md:px-8">

            <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
                <h1 class="text-3xl font-bold text-primary">Properties</h1>
                <p class="text-sm text-on-surface-variant">
                    Showing <?= $result->num_rows ?> of <?= $totalProducts ?> results
                </p>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">

                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $imagePath = '../' . ltrim(str_replace('\\', '/', $row['ProductImage1']), '/');
                        ?>

                        <a href="view-product.php?id=<?= urlencode($row['ProductID']) ?>"
                           class="group block overflow-hidden rounded-2xl border border-outline-variant/30 bg-white transition-all hover:shadow-lg">

                            <div class="relative aspect-square overflow-hidden bg-slate-100">
                                <img
                                    src="<?= htmlspecialchars($imagePath) ?>"
                                    alt="Property Image"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    onerror="this.src='https://placehold.co/600x600?text=Image+Missing';"
                                />

                                <div class="absolute top-3 left-3 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-md bg-primary/90 px-2 py-1 text-[10px] font-bold uppercase text-white">
                                        <?= htmlspecialchars($row['ProductStatus'] ?? 'Available') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-secondary">
                                        <?= htmlspecialchars($row['ProductCategory']) ?>
                                    </span>
                                    <span class="text-[10px] font-bold uppercase text-on-surface-variant">
                                        <?= htmlspecialchars($row['PriceType'] ?? 'Fixed') ?>
                                    </span>
                                </div>

                                <h3 class="mb-2 truncate font-bold text-primary">
                                    <?= htmlspecialchars($row['ProductTitle']) ?>
                                </h3>

                                <div class="text-lg font-extrabold text-primary">
                                    BDT <?= number_format((float) $row['ProductPrice']) ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-12 flex flex-wrap items-center justify-center gap-2">

                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>"
                               class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant hover:bg-gray-100">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"
                               class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold <?= $i === $page ? 'bg-primary text-white' : 'border border-outline-variant hover:bg-gray-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>"
                               class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant hover:bg-gray-100">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </a>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <span class="material-symbols-outlined text-5xl text-outline-variant">search_off</span>
                    <h2 class="mt-4 text-xl font-bold text-primary">No products available</h2>
                    <p class="text-on-surface-variant">Try adjusting your search.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-outline-variant/30 py-8 text-center text-xs text-on-surface-variant">
        © 2026 Rems. AI-Driven Real Estate Curation.
    </footer>

</body>
</html>