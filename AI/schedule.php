<?php
require_once '../conn.php';

$db = new Database();
$conn = $db->getConnection();

$productID = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($productID)) {
    die("Invalid Product ID.");
}

// 1. Fetch Product and Company Data using Prepared Statements
$prodSql = "SELECT p.ProductTitle, p.ProductID, g.CompanyID, g.CompanyName, g.PageLink, 
                   g.ContactEmail, g.ContactNumber AS Number1 
            FROM products p 
            JOIN groups g ON p.CompanyID = g.CompanyID 
            WHERE p.ProductID = ? LIMIT 1";

$stmt = $conn->prepare($prodSql);
$stmt->bind_param("s", $productID);
$stmt->execute();
$prodResult = $stmt->get_result();

if (!$prodResult || $prodResult->num_rows === 0) {
    die("Product or Company data not found.");
}

$productData = $prodResult->fetch_assoc();
$companyID = $productData['CompanyID'];

// 2. Fetch Representatives
$repSql = "SELECT 
                u.FirstName, 
                u.LastName, 
                m.Role, 
                g.ContactEmail, 
                g.ContactNumber AS Number1
           FROM members m 
           JOIN userinfo u ON m.UserID = u.UserID 
           JOIN groups g ON m.CompanyID = g.CompanyID
           WHERE m.CompanyID = ?";

$stmtRep = $conn->prepare($repSql);
$stmtRep->bind_param("s", $companyID);
$stmtRep->execute();
$repResult = $stmtRep->get_result();

$representatives = [];
while ($row = $repResult->fetch_assoc()) {
    $representatives[] = $row;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Schedule Visit | <?= htmlspecialchars($productData['ProductTitle']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />

    <style>
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

<body class="bg-surface text-on-surface font-[Inter] min-h-screen pt-16">

    <div class="mx-auto max-w-4xl px-6 py-12">
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-black text-primary">Schedule a Visit</h1>
            <p class="mt-2 text-on-surface-variant">For: <span class="font-bold"><?= htmlspecialchars($productData['ProductTitle']) ?></span></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Side: Info -->
            <div class="space-y-6">
                <div class="p-6 rounded-3xl glass-card shadow-sm">
                    <h3 class="text-xs font-bold text-on-surface-variant uppercase mb-4 tracking-wider">Company Information</h3>
                    <h2 class="text-2xl font-extrabold text-primary mb-2"><?= htmlspecialchars($productData['CompanyName']) ?></h2>
                    <a href="<?= htmlspecialchars($productData['PageLink']) ?>" target="_blank" class="text-sm font-semibold text-secondary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">language</span> Visit Official Website
                    </a>
                </div>

                <div class="p-6 rounded-3xl glass-card shadow-sm">
                    <h3 class="text-xs font-bold text-on-surface-variant uppercase mb-4 tracking-wider">Representatives</h3>
                    <?php if (!empty($representatives)): ?>
                        <div class="space-y-4">
                            <?php foreach ($representatives as $rep): ?>
                                <div class="border-l-4 border-secondary pl-4 py-1">
                                    <p class="font-bold text-primary text-lg">
                                        <?= htmlspecialchars($rep['FirstName'] . ' ' . $rep['LastName']) ?>
                                        <span class="text-xs text-on-surface-variant font-medium">(<?= htmlspecialchars($rep['Role']) ?>)</span>
                                    </p>
                                    <p class="text-sm text-on-surface-variant flex items-center gap-2 mt-1">
                                        <span class="material-symbols-outlined text-[16px]">mail</span> <?= htmlspecialchars($rep['ContactEmail']) ?>
                                    </p>
                                    <p class="text-sm text-on-surface-variant flex items-center gap-2 mt-1">
                                        <span class="material-symbols-outlined text-[16px]">call</span> <?= htmlspecialchars($rep['Number1']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-on-surface-variant">No representatives listed.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side: Form -->
            <div class="p-8 rounded-3xl bg-white shadow-xl border border-outline-variant/30">
                <form id="scheduleForm" class="space-y-5">
                    <input type="hidden" id="productID" value="<?= htmlspecialchars($productData['ProductID']) ?>">
                    <input type="hidden" id="productTitle" value="<?= htmlspecialchars($productData['ProductTitle']) ?>">
                    <input type="hidden" id="companyName" value="<?= htmlspecialchars($productData['CompanyName']) ?>">

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Your Name</label>
                        <input type="text" id="userName" required class="w-full rounded-xl border border-outline-variant/50 bg-surface px-4 py-3 text-sm focus:border-secondary focus:outline-none focus:ring-1 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Email Address</label>
                        <input type="email" id="userEmail" required class="w-full rounded-xl border border-outline-variant/50 bg-surface px-4 py-3 text-sm focus:border-secondary focus:outline-none focus:ring-1 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Phone Number</label>
                        <input type="tel" id="userPhone" required class="w-full rounded-xl border border-outline-variant/50 bg-surface px-4 py-3 text-sm focus:border-secondary focus:outline-none focus:ring-1 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">City / Area</label>
                        <input type="text" id="userCity" required class="w-full rounded-xl border border-outline-variant/50 bg-surface px-4 py-3 text-sm focus:border-secondary focus:outline-none focus:ring-1 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Select Date & Time</label>
                        <input type="datetime-local" id="scheduleTime" required class="w-full rounded-xl border border-outline-variant/50 bg-surface px-4 py-3 text-sm focus:border-secondary focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                    </div>

                    <button type="submit" id="submitBtn" class="mt-4 w-full rounded-xl bg-primary py-3.5 text-sm font-bold text-white transition-colors hover:bg-secondary flex justify-center items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span> Confirm Schedule
                    </button>

                    <div id="statusMessage" class="hidden text-center text-sm font-bold mt-4 p-3 rounded-xl"></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('scheduleForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const statusMsg = document.getElementById('statusMessage');

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">autorenew</span> Processing...';

            // IMPORTANT: If accessing from a different device, use your IP address instead of localhost
            const WEBHOOK_URL = 'http://localhost:5678/webhook-test/scheduling';

            const payload = {
                productID: document.getElementById('productID').value,
                productTitle: document.getElementById('productTitle').value,
                companyName: document.getElementById('companyName').value,
                userName: document.getElementById('userName').value,
                userEmail: document.getElementById('userEmail').value,
                userPhone: document.getElementById('userPhone').value,
                userCity: document.getElementById('userCity').value,
                scheduleTime: document.getElementById('scheduleTime').value
            };

            fetch(WEBHOOK_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                mode: 'cors', 
                body: JSON.stringify(payload)
            })
            .then(async response => {
                if (response.ok) {
                    statusMsg.className = 'text-center text-sm font-bold mt-4 p-3 rounded-xl bg-green-100 text-green-800';
                    statusMsg.innerText = 'Visit scheduled successfully!';
                    statusMsg.classList.remove('hidden');
                    document.getElementById('scheduleForm').reset();
                } else {
                    const text = await response.text();
                    throw new Error(text || 'Webhook submission failed');
                }
            })
            .catch(error => {
                statusMsg.className = 'text-center text-sm font-bold mt-4 p-3 rounded-xl bg-red-100 text-red-700';
                statusMsg.innerText = 'Error: Check if n8n is active and CORS is allowed.';
                statusMsg.classList.remove('hidden');
                console.error('Fetch error:', error);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">send</span> Confirm Schedule';
            });
        });
    </script>
</body>
</html>