<?php
require_once '../conn.php';
session_start();

$db = new Database();
$conn = $db->getConnection();

$sid = $_GET['sid'] ?? $_SESSION['SessionID'] ?? "";
if (empty($sid)) { header("Location: ../login.php"); exit; }

// 1. Data & Permission Logic
$uid = "";
$firstName = "";
$companyData = null;
$existingProducts = [];
$canManage = false;

// Fetch UserID and update lastpage
$stmt = $conn->prepare("SELECT UserID FROM session WHERE SessionID = ?");
$stmt->bind_param("s", $sid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$uid = $res['UserID'] ?? "";

if ($uid) {
    // Update tracking
    $uStmt = $conn->prepare("UPDATE session SET LastPageLink = 'pages/providers.php' WHERE SessionID = ?");
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

        // Fetch Products for this company
        $pStmt = $conn->prepare("SELECT * FROM products WHERE CompanyID = ?");
        $pStmt->bind_param("s", $cid);
        $pStmt->execute();
        $existingProducts = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

function buildUrl($url, $sid) {
    if (empty($sid)) return $url;
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
        <style>
            .expand-content { max-height: 0; overflow: hidden; transition: all 0.5s ease-in-out; opacity: 0; }
            .expand-content.active { max-height: 2000px; opacity: 1; margin-top: 1.5rem; }
            .glass-nav { background: rgba(252, 248, 255, 0.8); backdrop-filter: blur(12px); }
        </style>
    </head>
    <body class="bg-surface text-on-surface font-[Inter]">
        <header class="fixed top-0 z-50 w-full glass-nav shadow-[0_32px_32px_-12px_rgba(23,24,55,0.04)]">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8">
                <a href="<?= buildUrl('../index.php', $sid) ?>" class="text-xl font-semibold tracking-tight text-primary">Rems</a>

                <nav id="desktop-nav" class="hidden items-center gap-8 text-sm font-medium tracking-tight md:flex">
                    <div class="flex items-center gap-2 px-3 py-1 bg-surface-low rounded-full">
                        <span class="material-symbols-outlined text-sm <?= ($companyData['Allegations'] ?? 0) > 0 ? 'text-red-500' : 'text-emerald-500' ?>">
                            <?= ($companyData['Allegations'] ?? 0) > 0 ? 'warning' : 'verified_user' ?>
                        </span>
                        <span class="text-[11px] font-bold uppercase">
                            <?= ($companyData['Allegations'] ?? 0) > 0 ? $companyData['Allegations'].' Allegations' : 'Phew, you are safe :>' ?>
                        </span>
                    </div>
                </nav>

                <div class="hidden items-center gap-4 md:flex">
                    <span class="text-xs font-semibold text-on-surface-variant">Hello, <?= htmlspecialchars($firstName) ?></span>
                    <a href="<?= buildUrl('settings.php', $sid) ?>" class="rounded-lg bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white">
                        Settings
                    </a>
                </div>

                <button id="menu-toggle" class="md:hidden rounded-lg p-2 text-primary">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </header>

        <main class="pt-24 pb-16">
            <div class="mx-auto max-w-7xl px-6 md:px-8">
                
                <!-- Company Branding Header -->
                <?php if ($companyData): ?>
                <div class="relative mb-12 overflow-hidden rounded-[2rem] bg-primary-container h-48 flex items-end">
                    <img src="<?= $companyData['SecondaryImage'] ?>" class="absolute inset-0 w-full h-full object-cover opacity-30" alt="Background">
                    <div class="absolute inset-0 bg-gradient-to-t from-primary-container to-transparent"></div>
                    <div class="relative z-10 p-8 flex items-center gap-6 w-full">
                        <img src="<?= $companyData['LogoAddress'] ?>" class="w-24 h-24 rounded-2xl border-4 border-white object-cover bg-white shadow-xl">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-white mb-1"><?= $companyData['CompanyName'] ?></h1>
                            <div class="flex flex-wrap gap-4 text-xs text-blue-100">
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">location_on</span> <?= $companyData['Office'] ?></span>
                                <a href="<?= $companyData['PageLink'] ?>" class="flex items-center gap-1 underline underline-offset-4"><span class="material-symbols-outlined text-xs">link</span> Official Page</a>
                            </div>
                        </div>
                        <?php if($canManage): ?>
                            <button class="rounded-xl bg-white/10 border border-white/20 p-3 text-white hover:bg-white/20 transition-all">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-primary mb-8">Provider Dashboard</h1>
                
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    
                    <!-- Card 1: Publish -->
                    <div class="group rounded-3xl bg-white p-8 shadow-sm border border-outline-variant/30 hover:shadow-xl transition-all">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-secondary-container text-secondary">
                            <span class="material-symbols-outlined text-3xl">add_business</span>
                        </div>
                        <h3 class="text-xl font-bold text-primary mb-2">Publish Listing</h3>
                        <p class="text-sm text-on-surface-variant mb-6">Create a new property or service listing for the marketplace.</p>
                        <button onclick="toggleSection('publish-sec')" class="flex items-center gap-2 font-bold text-secondary <?= !$canManage ? 'opacity-30 cursor-not-allowed' : '' ?>" <?= !$canManage ? 'disabled' : '' ?>>
                            <?= $canManage ? 'Get Started' : 'Manage Restricted' ?> 
                            <span class="material-symbols-outlined">expand_more</span>
                        </button>

                        <div id="publish-sec" class="expand-content">
                            <form class="space-y-3 pt-4 border-t border-outline-variant/20">
                                <input type="text" placeholder="Product Title" class="w-full rounded-xl border-outline-variant bg-surface-low text-sm">
                                <select class="w-full rounded-xl border-outline-variant bg-surface-low text-sm">
                                    <option>Property</option>
                                    <option>Raw Material</option>
                                    <option>Services</option>
                                </select>
                                <button class="w-full rounded-xl bg-primary py-3 text-white text-xs font-bold uppercase">Submit for Review</button>
                            </form>
                        </div>
                    </div>

                    <!-- Card 2: Existing List -->
                    <div class="group rounded-3xl bg-white p-8 shadow-sm border border-outline-variant/30 hover:shadow-xl transition-all">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-high text-primary">
                            <span class="material-symbols-outlined text-3xl">list_alt</span>
                        </div>
                        <h3 class="text-xl font-bold text-primary mb-2">Check Existing</h3>
                        <p class="text-sm text-on-surface-variant mb-6">View and manage your currently active <?= count($existingProducts) ?> items.</p>
                        <button onclick="toggleSection('list-sec')" class="flex items-center gap-2 font-bold text-primary">
                            View Portfolio <span class="material-symbols-outlined">expand_more</span>
                        </button>

                        <div id="list-sec" class="expand-content">
                            <div class="space-y-2 pt-4 border-t border-outline-variant/20">
                                <?php foreach($existingProducts as $p): ?>
                                <div class="flex items-center justify-between p-3 bg-surface-low rounded-xl">
                                    <span class="text-xs font-semibold truncate max-w-[120px]"><?= $p['ProductTitle'] ?></span>
                                    <span class="text-[10px] px-2 py-1 bg-white rounded-md text-secondary font-bold uppercase"><?= $p['ProductStatus'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Contact Info (Editable) -->
                    <div class="group rounded-3xl bg-white p-8 shadow-sm border border-outline-variant/30 hover:shadow-xl transition-all">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-tertiary-fixed text-tertiary">
                            <span class="material-symbols-outlined text-3xl">contact_phone</span>
                        </div>
                        <h3 class="text-xl font-bold text-primary mb-2">Support & Info</h3>
                        <p class="text-sm text-on-surface-variant mb-6">Update your company contact numbers and emails.</p>
                        <div class="space-y-4 text-xs">
                            <div class="flex items-center justify-between border-b border-outline-variant/20 pb-2">
                                <span class="text-on-surface-variant">Email:</span>
                                <span class="font-bold"><?= $companyData['ContactEmail'] ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-on-surface-variant">Phone:</span>
                                <span class="font-bold"><?= $companyData['ContactNumber'] ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>

        <footer class="bg-surface-low text-on-surface mt-auto">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 py-14 md:grid-cols-4 md:px-8">
                <div>
                    <span class="mb-4 block text-2xl font-bold text-primary">Rems</span>
                    <p class="text-sm leading-relaxed text-on-surface-variant">Redefining real estate with intelligent curation.</p>
                </div>
                <div>
                    <h4 class="mb-4 font-bold text-primary">Company</h4>
                    <ul class="space-y-3 text-sm text-on-surface-variant">
                        <li><a href="#" class="hover:text-secondary">About Us</a></li>
                        <li><a href="mailto:partners@Rems.bd" class="hover:text-secondary">Developer Portal</a></li>
                    </ul>
                </div>
                <div></div> <!-- Spacer -->
                <div>
                    <h4 class="mb-4 font-bold text-primary">Support</h4>
                    <ul class="space-y-3 text-sm text-on-surface-variant">
                        <li><a href="mailto:support@Rems.bd" class="hover:text-secondary">Help Center</a></li>
                    </ul>
                </div>
            </div>
            <div class="mx-auto flex max-w-7xl flex-col gap-2 border-t border-outline-variant/30 px-6 py-6 text-sm text-on-surface-variant md:flex-row md:items-center md:justify-between md:px-8">
                <span>© 2026 Rems. Provider Console.</span>
                <div class="flex gap-6"><a href="#" class="hover:text-secondary">Security</a></div>
            </div>
        </footer>

        <script>
            function toggleSection(id) {
                const sec = document.getElementById(id);
                const isActive = sec.classList.contains('active');
                
                // Optional: Close others
                document.querySelectorAll('.expand-content').forEach(el => el.classList.remove('active'));
                
                if(!isActive) {
                    sec.classList.add('active');
                }
            }
        </script>
    </body>
</html>