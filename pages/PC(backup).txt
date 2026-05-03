<?php
require_once '../conn.php';
session_start();

$db = new Database();
$conn = $db->getConnection();

$sid = $_GET['sid'] ?? $_SESSION['SessionID'] ?? "";
if (empty($sid)) {
    header("Location: ../login.php");
    exit;
}

// 1. Core Logic & Data Initialization
$uid = "";
$firstName = "";
$companyData = null;
$existingProducts = [];
$canManage = false;
$successMsg = "";
$errorMsg = "";

// Fetch UserID and update lastpage
$stmt = $conn->prepare("SELECT UserID FROM session WHERE SessionID = ?");
$stmt->bind_param("s", $sid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$uid = $res['UserID'] ?? "";

if ($uid) {
    // Update tracking
    $uStmt = $conn->prepare("UPDATE session SET LastPageLink = 'pages/providers.php' WHERE SessionID = ?");

    if ($uStmt === false) {
        die("SQL Prepare Error: " . $conn->error);
    }

    $uStmt->bind_param("s", $sid);
    $uStmt->execute();

    // Fetch User Info
    $uInfo = $conn->prepare("SELECT FirstName FROM userinfo WHERE UserID = ?");
    $uInfo->bind_param("s", $uid);
    $uInfo->execute();
    $firstName = $uInfo->get_result()->fetch_assoc()['FirstName'] ?? "User";

    // Fetch Membership & Company (Groups)
    $memStmt = $conn->prepare("
        SELECT m.Permissions, m.Allegations, g.* 
        FROM members m 
        JOIN groups g ON m.CompanyID = g.CompanyID 
        WHERE m.UserID = ?
    ");
    $memStmt->bind_param("s", $uid);
    $memStmt->execute();
    $companyData = $memStmt->get_result()->fetch_assoc();

    if ($companyData) {
        $canManage = ($companyData['Permissions'] === 'MANAGE');
        $cid = $companyData['CompanyID'];

        // --- PUBLISH PRODUCT LOGIC ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_product']) && $canManage) {
            $pTitle = $_POST['p_title'];
            $pCat = $_POST['p_cat'];
            $pSize = !empty($_POST['p_size']) ? intval($_POST['p_size']) : null;
            $pDesc = $_POST['p_desc'];
            $pPrice = $_POST['p_price'] ?? null;
            $pUnit = $_POST['p_unit'] ?? null;
            $pPriceType = !empty($_POST['p_price_type']) ? $_POST['p_price_type'] : 'Fixed';
            $pStatus = $_POST['p_status'] ?? 'Available';

            // Generate ProductID
            $pID = "PROD-" . strtoupper(substr(md5(uniqid()), 0, 8));

            // Image Handling
            $targetDir = "../assets/images/" . $cid . "/" . $pID . "/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $imagePaths = [null, null, null, null, null];
            $uploadedCount = 0;

            for ($i = 1; $i <= 5; $i++) {
                if (!empty($_FILES["img$i"]["name"])) {
                    $ext = pathinfo($_FILES["img$i"]["name"], PATHINFO_EXTENSION);
                    $fileName = "image$i." . $ext;
                    $targetFilePath = $targetDir . $fileName;

                    if (move_uploaded_file($_FILES["img$i"]["tmp_name"], $targetFilePath)) {
                        $imagePaths[$i - 1] = "assets/images/" . $cid . "/" . $pID . "/" . $fileName;
                        $uploadedCount++;
                    }
                }
            }

            if ($uploadedCount === 0) {
                $errorMsg = "At least one image is required.";
            } else {
                // Updated SQL to match new schema: Added ProductSize and separated PriceUnit
                $sql = "INSERT INTO products (ProductID, UserID, CompanyID, ProductTitle, ProductCategory, ProductSize, ProductDescription, 
                        ProductImage1, ProductImage2, ProductImage3, ProductImage4, ProductImage5, ProductPrice, PriceType, PriceUnit, ProductStatus) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insStmt = $conn->prepare($sql);
                $insStmt->bind_param(
                    "sssssissssssssss",
                    $pID,
                    $uid,
                    $cid,
                    $pTitle,
                    $pCat,
                    $pSize,
                    $pDesc,
                    $imagePaths[0],
                    $imagePaths[1],
                    $imagePaths[2],
                    $imagePaths[3],
                    $imagePaths[4],
                    $pPrice,
                    $pPriceType,
                    $pUnit,
                    $pStatus
                );

                if ($insStmt->execute()) {
                    $successMsg = "Listing published successfully!";
                } else {
                    $errorMsg = "Database error: " . $conn->error;
                }
            }
        }

        // Fetch Products for this company
        $pStmt = $conn->prepare("SELECT * FROM products WHERE CompanyID = ? ORDER BY ProductID DESC");
        $pStmt->bind_param("s", $cid);
        $pStmt->execute();
        $existingProducts = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

function buildUrl($url, $sid)
{
    if (empty($sid))
        return $url;
    $sep = (strpos($url, '?') === false) ? '?' : '&';
    return $url . $sep . 'sid=' . urlencode($sid);
}
?>
<!doctype html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rems | Provider Portal</title>
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
                        tertiary: "#00261d",
                        background: "#fcf8ff",
                        surface: "#fcf8ff",
                        "surface-low": "#f5f2ff",
                        "surface-high": "#e8e6ff",
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
    <style>
        .expand-content {
            max-height: 0;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
        }

        .expand-content.active {
            max-height: 3000px;
            opacity: 1;
            margin-top: 1.5rem;
        }

        .glass-nav {
            background: rgba(252, 248, 255, 0.8);
            backdrop-filter: blur(12px);
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-[Inter]">
    <header class="fixed top-0 z-50 w-full glass-nav shadow-[0_32px_32px_-12px_rgba(23,24,55,0.04)]">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8">
            <a href="<?= buildUrl('../index.php', $sid) ?>"
                class="text-xl font-semibold tracking-tight text-primary">Rems</a>

            <nav id="desktop-nav" class="hidden items-center gap-8 text-sm font-medium tracking-tight md:flex">
                <div class="flex items-center gap-2 px-3 py-1 bg-surface-low rounded-full">
                    <span
                        class="material-symbols-outlined text-sm <?= ($companyData['Allegations'] ?? 0) > 0 ? 'text-red-500 animate-pulse' : 'text-emerald-500' ?>">
                        <?= ($companyData['Allegations'] ?? 0) > 0 ? 'warning' : 'verified_user' ?>
                    </span>
                    <span class="text-[11px] font-bold uppercase">
                        <?= ($companyData['Allegations'] ?? 0) > 0 ? $companyData['Allegations'] . ' Allegations' : 'Phew, you are safe :>' ?>
                    </span>
                </div>
            </nav>

            <div class="hidden items-center gap-4 md:flex">
                <span class="text-xs font-semibold text-on-surface-variant">Hello,
                    <?= htmlspecialchars($firstName) ?></span>
                <a href="<?= buildUrl('settings.php', $sid) ?>"
                    class="rounded-lg bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white">
                    Settings
                </a>
            </div>
        </div>
    </header>

    <main class="pt-24 pb-16">
        <div class="mx-auto max-w-7xl px-6 md:px-8">

            <!-- Success/Error Toasts -->
            <?php if ($successMsg): ?>
                <div
                    class="mb-6 p-4 bg-emerald-100 text-emerald-800 rounded-2xl border border-emerald-200 text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span> <?= $successMsg ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div
                    class="mb-6 p-4 bg-red-100 text-red-800 rounded-2xl border border-red-200 text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">error</span> <?= $errorMsg ?>
                </div>
            <?php endif; ?>

            <!-- Company Branding Header -->
            <?php if ($companyData): ?>
                <div class="relative mb-12 overflow-hidden rounded-[2.5rem] bg-primary-container h-56 flex items-end">
                    <img src="<?= $companyData['SecondaryImage'] ?>"
                        class="absolute inset-0 w-full h-full object-cover opacity-40" alt="Background">
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-primary-container via-primary-container/20 to-transparent">
                    </div>
                    <div class="relative z-10 p-10 flex items-center gap-8 w-full">
                        <img src="<?= $companyData['LogoAddress'] ?>"
                            class="w-28 h-28 rounded-3xl border-4 border-white object-cover bg-white shadow-2xl">
                        <div class="flex-1">
                            <h1 class="text-4xl font-black text-white tracking-tighter mb-2">
                                <?= $companyData['CompanyName'] ?></h1>
                            <div class="flex flex-wrap gap-5 text-xs font-medium text-blue-100">
                                <span class="flex items-center gap-1.5"><span
                                        class="material-symbols-outlined text-sm">location_on</span>
                                    <?= $companyData['Office'] ?></span>
                                <a href="<?= $companyData['PageLink'] ?>"
                                    class="flex items-center gap-1.5 hover:text-white transition-colors underline underline-offset-4 decoration-white/30"><span
                                        class="material-symbols-outlined text-sm">link</span> Visit Site</a>
                            </div>
                        </div>
                        <?php if ($canManage): ?>
                            <button
                                class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 px-5 py-3 text-white text-xs font-bold flex items-center gap-2 hover:bg-white/20 transition-all">
                                <span class="material-symbols-outlined text-lg">edit</span> Edit Profile
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">

                <!-- Card 1: Publish Listing -->
                <div
                    class="group rounded-[2rem] bg-white p-8 shadow-sm border border-outline-variant/30 hover:shadow-xl transition-all h-fit">
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-secondary-container text-secondary">
                        <span class="material-symbols-outlined text-3xl">add_business</span>
                    </div>
                    <h3 class="text-xl font-bold text-primary mb-2">Publish Listing</h3>
                    <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">List a new property, land, or
                        material with high-res images and specific pricing units.</p>

                    <button onclick="toggleSection('publish-sec')"
                        class="flex items-center gap-2 font-bold text-secondary <?= !$canManage ? 'opacity-30 cursor-not-allowed' : '' ?>"
                        <?= !$canManage ? 'disabled' : '' ?>>
                        <?= $canManage ? 'Get Started' : 'Manage Permission Required' ?>
                        <span class="material-symbols-outlined arrow-icon transition-transform">expand_more</span>
                    </button>

                    <div id="publish-sec" class="expand-content">
                        <form method="POST" enctype="multipart/form-data"
                            class="space-y-5 pt-6 border-t border-outline-variant/20">
                            <div class="space-y-3">
                                <input type="text" name="p_title" required placeholder="Listing Title"
                                    class="w-full rounded-xl border-outline-variant bg-surface-low text-sm">
                                
                                <div class="flex gap-2">
                                    <select name="p_cat" required
                                        class="flex-1 rounded-xl border-outline-variant bg-surface-low text-sm font-medium">
                                        <option value="" disabled selected>Category</option>
                                        <option>Property</option>
                                        <option>Raw Material</option>
                                        <option>Rental</option>
                                        <option>Services</option>
                                        <option>Land</option>
                                    </select>
                                    <input type="number" name="p_size" placeholder="Size (e.g. 1200)"
                                        class="w-1/3 rounded-xl border-outline-variant bg-surface-low text-sm">
                                </div>

                                <textarea name="p_desc" rows="3" required placeholder="Describe the listing..."
                                    class="w-full rounded-xl border-outline-variant bg-surface-low text-sm"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2 flex gap-2">
                                    <input type="text" name="p_price" placeholder="Price"
                                        class="flex-1 rounded-xl border-outline-variant bg-surface-low text-sm">
                                    <select name="p_unit"
                                        class="w-1/3 rounded-xl border-outline-variant bg-surface-low text-xs font-bold">
                                        <option value="">No Unit</option>
                                        <option value="Sq.ft">Sq.ft</option>
                                        <option value="Acre">Acre</option>
                                        <option value="Bigha">Bigha</option>
                                    </select>
                                </div>
                                <select name="p_price_type"
                                    class="rounded-xl border-outline-variant bg-surface-low text-[11px] font-bold">
                                    <option value="Fixed">Fixed</option>
                                    <option value="Negotiable">Negotiable</option>
                                </select>
                                <select name="p_status"
                                    class="rounded-xl border-outline-variant bg-surface-low text-[11px] font-bold">
                                    <option value="Available">Available</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Upcoming">Upcoming</option>
                                    <option value="Sold">Sold</option>
                                </select>
                            </div>

                            <div class="space-y-3">
                                <label
                                    class="text-[10px] font-black uppercase text-on-surface-variant tracking-widest">Post
                                    Images (Up to 5)</label>
                                <div class="grid grid-cols-5 gap-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <label
                                            class="relative flex flex-col items-center justify-center aspect-square bg-surface-high rounded-xl border-2 border-dashed border-outline-variant hover:border-secondary cursor-pointer transition-all overflow-hidden group/img">
                                            <span
                                                class="material-symbols-outlined text-outline-variant text-lg group-hover/img:text-secondary">add_a_photo</span>
                                            <input type="file" name="img<?= $i ?>" class="hidden" accept="image/*"
                                                onchange="preview(this, <?= $i ?>)">
                                            <img id="prev<?= $i ?>"
                                                class="absolute inset-0 w-full h-full object-cover hidden">
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <button type="submit" name="publish_product"
                                class="w-full rounded-2xl bg-gradient-to-r from-secondary to-tertiary py-4 text-white text-xs font-black uppercase tracking-widest shadow-lg hover:brightness-110 transition-all">
                                Finalize Listing
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Card 2: Existing List -->
                <div
                    class="group rounded-[2rem] bg-white p-8 shadow-sm border border-outline-variant/30 hover:shadow-xl transition-all h-fit">
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-high text-primary">
                        <span class="material-symbols-outlined text-3xl">inventory_2</span>
                    </div>
                    <h3 class="text-xl font-bold text-primary mb-2">My Inventory</h3>
                    <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">Manage and track your
                        <?= count($existingProducts) ?> active marketplace items.</p>
                    <button onclick="toggleSection('list-sec')" class="flex items-center gap-2 font-bold text-primary">
                        View Portfolio <span class="material-symbols-outlined transition-transform">expand_more</span>
                    </button>

                    <div id="list-sec" class="expand-content">
                        <div class="space-y-3 pt-6 border-t border-outline-variant/20 max-h-96 overflow-y-auto pr-2">
                            <?php if (empty($existingProducts)): ?>
                                <p class="text-center text-xs text-slate-400 py-4 italic">No items listed yet.</p>
                            <?php endif; ?>
                            <?php foreach ($existingProducts as $p): ?>
                                <div
                                    class="flex items-center gap-4 p-3 bg-surface-low rounded-2xl border border-transparent hover:border-outline-variant hover:bg-white transition-all group/item">
                                    <img src="../<?= $p['ProductImage1'] ?>"
                                        class="w-12 h-12 rounded-xl object-cover bg-surface-high">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-black truncate text-primary"><?= $p['ProductTitle'] ?></p>
                                        <p class="text-[10px] text-on-surface-variant font-medium">
                                            <?= $p['ProductCategory'] ?> • <?= $p['ProductSize'] ?? 'N/A' ?> units</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span
                                            class="text-[9px] px-2 py-0.5 bg-white text-secondary rounded-full border border-secondary/20 font-bold uppercase tracking-tighter"><?= $p['ProductStatus'] ?></span>
                                        <?php if ($canManage): ?>
                                            <button
                                                class="material-symbols-outlined text-sm text-slate-300 hover:text-secondary">edit_square</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Support & Contact -->
                <div
                    class="group rounded-[2rem] bg-white p-8 shadow-sm border border-outline-variant/30 hover:shadow-xl transition-all h-fit">
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-tertiary-fixed text-tertiary">
                        <span class="material-symbols-outlined text-3xl">contact_phone</span>
                    </div>
                    <h3 class="text-xl font-bold text-primary mb-2">Support Info</h3>
                    <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">Direct lines for partners and legal
                        verified inquiries.</p>
                    <div class="space-y-4">
                        <div class="p-4 bg-surface-low rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Business
                                    Email</span>
                                <span class="text-xs font-bold text-primary"><?= $companyData['ContactEmail'] ?? 'N/A' ?></span>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-white">
                                <span
                                    class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Phone</span>
                                <span class="text-xs font-bold text-primary"><?= $companyData['ContactNumber'] ?? 'N/A' ?></span>
                            </div>
                        </div>
                        <a href="mailto:support@Rems.bd"
                            class="block w-full py-3 rounded-2xl border-2 border-dashed border-outline-variant text-center text-[10px] font-black text-on-surface-variant uppercase hover:border-secondary hover:text-secondary transition-all">Contact
                            Developer Hub</a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="bg-surface-low text-on-surface mt-auto">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-2 border-t border-outline-variant/30 px-6 py-10 text-sm text-on-surface-variant md:flex-row md:items-center md:justify-between md:px-8">
            <span class="font-medium text-xs">© 2026 Rems. AI-Driven Provider Ecosystem.</span>
            <div class="flex gap-6 text-xs font-bold uppercase tracking-tighter">
                <a href="#" class="hover:text-secondary">Security Policy</a>
                <a href="#" class="hover:text-secondary">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script>
        function toggleSection(id) {
            const sec = document.getElementById(id);
            const isActive = sec.classList.contains('active');

            sec.classList.toggle('active');

            const btn = sec.previousElementSibling;
            const icon = btn.querySelector('.arrow-icon');
            if (icon) icon.style.transform = !isActive ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        function preview(input, n) {
            const img = document.getElementById('prev' + n);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.querySelector('form').addEventListener('submit', function (e) {
            const img1 = document.querySelector('input[name="img1"]').files.length;
            if (img1 === 0) {
                alert("A primary image (Slot 1) is required to publish.");
                e.preventDefault();
            }
        });
    </script>
</body>

</html>