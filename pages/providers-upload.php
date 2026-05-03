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
    $uStmt = $conn->prepare("UPDATE session SET LastPageLink = 'pages/providers-upload.php' WHERE SessionID = ?");
    if ($uStmt) {
        $uStmt->bind_param("s", $sid);
        $uStmt->execute();
    }

    // Fetch User Info
    $uInfo = $conn->prepare("SELECT FirstName FROM userinfo WHERE UserID = ?");
    $uInfo->bind_param("s", $uid);
    $uInfo->execute();
    $firstName = $uInfo->get_result()->fetch_assoc()['FirstName'] ?? "User";

    // Fetch Membership & Company
    $memStmt = $conn->prepare("
        SELECT m.Permissions, g.* 
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

        if (!$canManage) {
            header("Location: providers.php" . (!empty($sid) ? "?sid=" . urlencode($sid) : ""));
            exit;
        }

        // --- PUBLISH PRODUCT LOGIC ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_product']) && $canManage) {
            $pTitle = $_POST['p_title'];
            $pCat = $_POST['p_cat'];
            $pSize = !empty($_POST['p_size']) ? intval($_POST['p_size']) : null;
            $pDesc = $_POST['p_desc'];
            $pPrice = $_POST['p_price'] ?? null;
            $pUnit = $_POST['p_unit'] ?? null;
            $pPriceType = !empty($_POST['p_price_type']) ? $_POST['p_price_type'] : 'Fixed';
            
            // Defaulting to 'Pending' so the n8n Smart Verify system can process it
            $pStatus = 'Pending'; 

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
                        // Storing path relative to root for easier frontend access
                        $imagePaths[$i - 1] = "assets/images/" . $cid . "/" . $pID . "/" . $fileName;
                        $uploadedCount++;
                    }
                }
            }

            if ($uploadedCount === 0) {
                $errorMsg = "At least one primary image is required.";
            } else {
                $sql = "INSERT INTO products (ProductID, UserID, CompanyID, ProductTitle, ProductCategory, ProductSize, ProductDescription, 
                        ProductImage1, ProductImage2, ProductImage3, ProductImage4, ProductImage5, ProductPrice, PriceType, PriceUnit, ProductStatus) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insStmt = $conn->prepare($sql);
                $insStmt->bind_param(
                    "sssssissssssssss",
                    $pID, $uid, $cid, $pTitle, $pCat, $pSize, $pDesc,
                    $imagePaths[0], $imagePaths[1], $imagePaths[2], $imagePaths[3], $imagePaths[4],
                    $pPrice, $pPriceType, $pUnit, $pStatus
                );

                if ($insStmt->execute()) {
                    // --- TRIGGER N8N SMART VERIFY WORKFLOW ---
                    $n8nWebhookUrl = 'http://localhost:5678/webhook-test/smart-verify'; // Update with your Docker n8n URL
                    $payload = [
                        'product_id' => $pID,
                        'title' => $pTitle,
                        'category' => $pCat,
                        'description' => $pDesc,
                        'price' => $pPrice,
                        'price_unit' => $pUnit,
                        'images' => array_filter($imagePaths)
                    ];

                    $ch = curl_init($n8nWebhookUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Don't hang the UI if n8n is slow
                    curl_exec($ch);
                    curl_close($ch);

                    $successMsg = "Listing submitted! Our Smart Verify system is auditing your post.";
                } else {
                    $errorMsg = "Database error: " . $conn->error;
                }
            }
        }
    }
}

function buildUrl($url, $sid) {
    if (empty($sid)) return $url;
    $sep = (strpos($url, '?') === false) ? '?' : '&';
    return $url . $sep . 'sid=' . urlencode($sid);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Upload Listing | Rems Smart Portal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#002045",
                        secondary: "#13696a",
                        surface: "#fcf8ff",
                        "surface-low": "#f5f2ff",
                        "outline-variant": "#c4c6cf",
                    },
                },
            },
        };
    </script>
    <style>
        .glass-nav { background: rgba(252, 248, 255, 0.8); backdrop-filter: blur(12px); }
        .ai-badge { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    </style>
</head>

<body class="bg-surface text-on-surface font-[Inter]">
    <header class="fixed top-0 z-50 w-full glass-nav shadow-sm">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <a href="<?= buildUrl('providers.php', $sid) ?>" class="flex items-center gap-2 text-primary font-bold">
                <span class="material-symbols-outlined">arrow_back</span>
                <span>Back to Dashboard</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="ai-badge flex h-2 w-2 rounded-full bg-secondary"></span>
                <span class="text-[10px] font-black uppercase tracking-widest text-secondary">AI Agent Active</span>
            </div>
        </div>
    </header>

    <main class="pt-24 pb-16">
        <div class="mx-auto max-w-3xl px-6">

            <?php if ($successMsg): ?>
                <div class="mb-6 p-4 bg-emerald-100 text-emerald-800 rounded-2xl border border-emerald-200 text-sm font-bold flex items-center justify-between">
                    <div class="flex items-center gap-2"><span class="material-symbols-outlined">verified</span> <?= $successMsg ?></div>
                    <a href="<?= buildUrl('providers.php', $sid) ?>" class="underline">View Inventory</a>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMsg): ?>
                <div class="mb-6 p-4 bg-red-100 text-red-800 rounded-2xl border border-red-200 text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">report</span> <?= $errorMsg ?>
                </div>
            <?php endif; ?>

            <div class="rounded-[2.5rem] bg-white p-10 shadow-sm border border-outline-variant/30">
                <h3 class="text-3xl font-black text-primary tracking-tighter mb-2">New Submission</h3>
                <p class="text-sm text-slate-500 mb-8">Our AI agent will review your submission for quality, pricing accuracy, and relevance.</p>

                <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="space-y-4">
                        <label class="block">
                            <span class="text-[11px] font-bold uppercase ml-1 text-primary">Listing Title</span>
                            <input type="text" name="p_title" id="p_title" required placeholder="e.g., Luxury 3-Bedroom Apartment in Bashundhara"
                                class="w-full mt-1 rounded-xl border-outline-variant bg-surface-low text-sm p-3 focus:ring-secondary focus:border-secondary transition-all">
                        </label>
                        
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <span class="text-[11px] font-bold uppercase ml-1 text-primary">Category</span>
                                <select name="p_cat" id="p_cat" required class="w-full mt-1 rounded-xl border-outline-variant bg-surface-low text-sm p-3">
                                    <option value="" disabled selected>Select Type</option>
                                    <option>Property</option>
                                    <option>Raw Material</option>
                                    <option>Land</option>
                                </select>
                            </div>
                            <div class="w-1/3">
                                <span class="text-[11px] font-bold uppercase ml-1 text-primary">Size</span>
                                <input type="number" name="p_size" placeholder="Total Units" class="w-full mt-1 rounded-xl border-outline-variant bg-surface-low text-sm p-3">
                            </div>
                        </div>

                        <div class="relative">
                            <div class="flex justify-between items-end mb-1">
                                <span class="text-[11px] font-bold uppercase ml-1 text-primary">Description</span>
                                <div id="ai-status" class="text-[9px] font-bold text-secondary hidden italic">AI is drafting...</div>
                            </div>
                            <textarea name="p_desc" id="p_desc" rows="6" required placeholder="Describe the item..."
                                class="w-full rounded-xl border-outline-variant bg-surface-low text-sm p-3 focus:ring-secondary focus:border-secondary transition-all"></textarea>
                            
                            <!-- AI Suggestion Box -->
                            <div id="suggestion-container" class="hidden mt-2 p-4 bg-secondary/5 border border-secondary/20 rounded-xl">
                                <p class="text-[10px] font-black text-secondary uppercase mb-2">AI Suggestion:</p>
                                <p id="suggestion-text" class="text-xs text-slate-600 leading-relaxed mb-3 italic"></p>
                                <button type="button" id="apply-ai" class="text-[10px] font-bold bg-secondary text-white px-3 py-1.5 rounded-lg hover:bg-primary transition-all">Apply Description</button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-outline-variant/20">
                        <div class="col-span-2 flex gap-4">
                            <div class="flex-1">
                                <span class="text-[11px] font-bold uppercase ml-1 text-primary">Price</span>
                                <input type="text" name="p_price" placeholder="Amount" class="w-full mt-1 rounded-xl border-outline-variant bg-surface-low text-sm p-3">
                            </div>
                            <div class="w-1/3">
                                <span class="text-[11px] font-bold uppercase ml-1 text-primary">Unit</span>
                                <select name="p_unit" class="w-full mt-1 rounded-xl border-outline-variant bg-surface-low text-xs font-bold p-3">
                                    <option value="">None</option>
                                    <option value="Sq.ft">Sq.ft</option>
                                    <option value="Acre">Acre</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase ml-1 text-primary">Pricing Model</span>
                            <select name="p_price_type" class="w-full mt-1 rounded-xl border-outline-variant bg-surface-low text-sm p-3">
                                <option value="Fixed">Fixed</option>
                                <option value="Negotiable">Negotiable</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <span class="text-[11px] font-black uppercase text-primary tracking-widest">Visual Assets (Primary first)</span>
                        <div class="grid grid-cols-5 gap-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="relative flex flex-col items-center justify-center aspect-square bg-surface-low rounded-2xl border-2 border-dashed border-outline-variant hover:border-secondary cursor-pointer transition-all overflow-hidden group">
                                    <span class="material-symbols-outlined text-slate-400 group-hover:text-secondary">add_a_photo</span>
                                    <input type="file" name="img<?= $i ?>" class="hidden" accept="image/*" onchange="preview(this, <?= $i ?>)">
                                    <img id="prev<?= $i ?>" class="absolute inset-0 w-full h-full object-cover hidden">
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <button type="submit" name="publish_product"
                        class="w-full mt-6 rounded-2xl bg-primary py-4 text-white text-xs font-black uppercase tracking-widest shadow-xl hover:bg-secondary transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">rocket_launch</span>
                        Submit for Verification
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // --- PREVIEW IMAGES ---
        function preview(input, n) {
            const img = document.getElementById('prev' + n);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- AI SUGGESTION LOGIC ---
        let debounceTimer;
        const titleInput = document.getElementById('p_title');
        const descInput = document.getElementById('p_desc');
        const suggestionBox = document.getElementById('suggestion-container');
        const suggestionText = document.getElementById('suggestion-text');
        const aiStatus = document.getElementById('ai-status');

        async function getAISuggestion() {
            const title = titleInput.value;
            const currentDesc = descInput.value;

            if (title.length < 10) return;

            aiStatus.classList.remove('hidden');

            try {
                // Point to your n8n Docker Webhook for suggestions
                const response = await fetch('http://localhost:5678/webhook/get-suggestion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title: title, current_desc: currentDesc })
                });
                const data = await response.json();
                
                if(data.suggestion) {
                    suggestionText.innerText = data.suggestion;
                    suggestionBox.classList.remove('hidden');
                }
            } catch (err) {
                console.error("n8n Assistant unreachable");
            } finally {
                aiStatus.classList.add('hidden');
            }
        }

        descInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            // Wait for 2 seconds of inactivity before asking AI to avoid excessive API calls
            debounceTimer = setTimeout(getAISuggestion, 2000);
        });

        document.getElementById('apply-ai').addEventListener('click', () => {
            descInput.value = suggestionText.innerText;
            suggestionBox.classList.add('hidden');
        });

        // --- FORM VALIDATION ---
        document.getElementById('uploadForm').addEventListener('submit', function (e) {
            const img1 = document.querySelector('input[name="img1"]').files.length;
            if (img1 === 0) {
                alert("Please provide at least a primary image.");
                e.preventDefault();
            }
        });
    </script>
</body>
</html>