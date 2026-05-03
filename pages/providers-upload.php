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

$uid = "";
$firstName = "";
$companyData = null;
$canManage = false;
$pendingJobID = "";

// Fetch UserID
$stmt = $conn->prepare("SELECT UserID FROM session WHERE SessionID = ?");
$stmt->bind_param("s", $sid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$uid = $res['UserID'] ?? "";

if ($uid) {
    $uStmt = $conn->prepare("UPDATE session SET LastPageLink = 'pages/providers-upload.php' WHERE SessionID = ?");
    if ($uStmt) { $uStmt->bind_param("s", $sid); $uStmt->execute(); }

    $uInfo = $conn->prepare("SELECT FirstName FROM userinfo WHERE UserID = ?");
    $uInfo->bind_param("s", $uid);
    $uInfo->execute();
    $firstName = $uInfo->get_result()->fetch_assoc()['FirstName'] ?? "User";

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

        // --- SUBMIT TO N8N (no SQL insert here) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_product']) && $canManage) {
            $pTitle    = $_POST['p_title']      ?? '';
            $pCat      = $_POST['p_cat']        ?? '';
            $pSize     = !empty($_POST['p_size']) ? intval($_POST['p_size']) : null;
            $pDesc     = $_POST['p_desc']       ?? '';
            $pPrice    = $_POST['p_price']      ?? null;
            $pUnit     = $_POST['p_unit']       ?? null;
            $pPriceType = !empty($_POST['p_price_type']) ? $_POST['p_price_type'] : 'Fixed';
            $pStatus   = $_POST['p_status']     ?? 'Available';

            // Generate a temporary job ID so the frontend can poll for results
            $jobID = "JOB-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
            $pID   = "PROD-" . strtoupper(substr(md5(uniqid()), 0, 8));

            // Upload images to disk first so n8n can access them
            $targetDir = "../assets/images/" . $cid . "/" . $pID . "/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

            $imagePaths = [];
            for ($i = 1; $i <= 5; $i++) {
                if (!empty($_FILES["img$i"]["name"])) {
                    $ext = pathinfo($_FILES["img$i"]["name"], PATHINFO_EXTENSION);
                    $fileName = "image$i." . $ext;
                    $targetFilePath = $targetDir . $fileName;
                    if (move_uploaded_file($_FILES["img$i"]["tmp_name"], $targetFilePath)) {
                        $imagePaths[] = "assets/images/" . $cid . "/" . $pID . "/" . $fileName;
                    }
                }
            }

            if (empty($imagePaths)) {
                // Redirect back with an error flag
                header("Location: providers-upload.php?sid=" . urlencode($sid) . "&err=noimg");
                exit;
            }

            // Fire off to n8n — N8N does verification AND insertion
            $n8nWebhookUrl = 'http://host.docker.internal:5678/webhook-test/smart-verify';
            $payload = [
                'job_id'      => $jobID,
                'product_id'  => $pID,
                'user_id'     => $uid,
                'company_id'  => $cid,
                'title'       => $pTitle,
                'category'    => $pCat,
                'size'        => $pSize,
                'description' => $pDesc,
                'price'       => $pPrice,
                'price_type'  => $pPriceType,
                'unit'        => $pUnit,
                'status'      => $pStatus,
                'images'      => $imagePaths,
            ];

            $ch = curl_init($n8nWebhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            curl_close($ch);

            // Redirect to polling view
            header("Location: providers-upload.php?sid=" . urlencode($sid) . "&job=" . urlencode($jobID));
            exit;
        }
    }
}

// --- POLL ENDPOINT: called by JS every 3s ---
// N8N must POST back to:  pages/verify-callback.php?job=JOB-XXXX
// with JSON: { "status": "approved"|"rejected"|"suggestion", "message": "...", "suggested_description": "..." }
// The callback page writes a JSON file to /tmp/verify_<jobID>.json

$pendingJobID = $_GET['job'] ?? "";
$pollingMode  = !empty($pendingJobID);

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;900&family=Space+Mono:wght@400;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'sans-serif'], mono: ['Space Mono', 'monospace'] },
                    colors: {
                        primary: "#002045",
                        secondary: "#13696a",
                        surface: "#f7f8fc",
                        "surface-low": "#eef0f7",
                        "outline-variant": "#c4c6cf",
                    },
                },
            },
        };
    </script>
    <style>
        @keyframes spin-ring { to { transform: rotate(360deg); } }
        @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.7)} }
        @keyframes slide-up { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        @keyframes shimmer { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
        .spin-ring { animation: spin-ring 1.1s linear infinite; }
        .pulse-dot { animation: pulse-dot 1.4s ease-in-out infinite; }
        .slide-up { animation: slide-up .45s cubic-bezier(.22,1,.36,1) both; }
        .shimmer-bar {
            background: linear-gradient(90deg, #e2e5ef 25%, #ced2e4 50%, #e2e5ef 75%);
            background-size: 400px 100%;
            animation: shimmer 1.4s infinite linear;
            border-radius: 6px;
        }
    </style>
</head>

<body class="bg-surface text-primary font-sans min-h-screen">

    <!-- HEADER -->
    <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-xl border-b border-outline-variant/40">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-6">
            <a href="<?= buildUrl('providers.php', $sid) ?>" class="flex items-center gap-2 text-primary font-bold text-sm hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Back to Listings</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-secondary" style="animation: pulse-dot 1.4s ease-in-out infinite;"></span>
                <span class="font-mono text-[9px] font-bold uppercase tracking-[.2em] text-secondary">Smart Verify Active</span>
            </div>
        </div>
    </header>

    <main class="pt-28 pb-20 px-6">
        <div class="mx-auto max-w-3xl">

            <?php if (!empty($_GET['err']) && $_GET['err'] === 'noimg'): ?>
            <div class="mb-6 slide-up flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-2xl text-sm text-red-700 font-semibold">
                <span class="material-symbols-outlined text-red-500 mt-0.5">image_not_supported</span>
                At least one product image is required before submitting.
            </div>
            <?php endif; ?>

            <!-- ════════════════════════════════════════════
                 POLLING / LOADING VIEW
            ════════════════════════════════════════════ -->
            <?php if ($pollingMode): ?>
            <div id="verify-panel" class="slide-up rounded-[2rem] bg-white border border-outline-variant/30 shadow-sm overflow-hidden">

                <!-- Loading state (shown initially) -->
                <div id="state-loading" class="p-10 flex flex-col items-center gap-6 text-center">
                    <div class="relative w-16 h-16">
                        <svg class="spin-ring w-16 h-16 text-secondary" viewBox="0 0 64 64" fill="none">
                            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="5" stroke-dasharray="90 88" stroke-linecap="round" opacity=".25"/>
                            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="5" stroke-dasharray="55 123" stroke-linecap="round"/>
                        </svg>
                        <span class="material-symbols-outlined absolute inset-0 flex items-center justify-center text-secondary text-[22px]">smart_toy</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-primary tracking-tight">Smart Verify Running</h2>
                        <p class="text-sm text-slate-500 mt-1 max-w-sm">Your listing has been sent to the AI agent for verification. This usually takes 10–30 seconds.</p>
                    </div>
                    <div class="w-full space-y-2">
                        <div class="shimmer-bar h-3 w-3/4 mx-auto"></div>
                        <div class="shimmer-bar h-3 w-1/2 mx-auto"></div>
                        <div class="shimmer-bar h-3 w-5/6 mx-auto"></div>
                    </div>
                    <p class="font-mono text-[10px] text-slate-400 uppercase tracking-widest">Job ID: <?= htmlspecialchars($pendingJobID) ?></p>
                </div>

                <!-- Approved state (hidden until result) -->
                <div id="state-approved" class="hidden p-10 flex flex-col items-center gap-5 text-center">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center">
                        <span class="material-symbols-outlined text-emerald-600 text-[32px]" style="font-variation-settings:'FILL' 1">check_circle</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-primary tracking-tight">Listing Approved!</h2>
                        <p id="approved-msg" class="text-sm text-slate-500 mt-2 max-w-sm">Your listing has been verified and is now live.</p>
                    </div>
                    <a href="<?= buildUrl('providers.php', $sid) ?>" class="mt-2 inline-flex items-center gap-2 bg-primary text-white text-xs font-black uppercase tracking-widest px-6 py-3 rounded-xl hover:bg-secondary transition-all">
                        <span class="material-symbols-outlined text-[16px]">storefront</span>View Listings
                    </a>
                </div>

                <!-- Rejected state (hidden until result) -->
                <div id="state-rejected" class="hidden p-10 flex flex-col items-center gap-5 text-center">
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                        <span class="material-symbols-outlined text-red-500 text-[32px]" style="font-variation-settings:'FILL' 1">cancel</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-primary tracking-tight">Verification Failed</h2>
                        <p class="text-sm text-slate-400 mt-1">The agent found issues with your submission.</p>
                    </div>
                    <div id="rejected-reason" class="w-full text-left bg-red-50 border border-red-200 rounded-2xl p-5">
                        <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-red-400 mb-2">Reason</p>
                        <p id="rejected-msg" class="text-sm text-red-800 font-medium leading-relaxed"></p>
                    </div>
                    <a href="providers-upload.php?sid=<?= urlencode($sid) ?>" class="inline-flex items-center gap-2 border border-primary text-primary text-xs font-black uppercase tracking-widest px-6 py-3 rounded-xl hover:bg-primary hover:text-white transition-all">
                        <span class="material-symbols-outlined text-[16px]">edit</span>Edit & Resubmit
                    </a>
                </div>

                <!-- AI Suggestion state (hidden until result) -->
                <div id="state-suggestion" class="hidden p-10 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-secondary text-[20px]">auto_awesome</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-primary tracking-tight">Agent Suggestion</h2>
                            <p class="text-xs text-slate-400">The AI has a recommendation before publishing.</p>
                        </div>
                    </div>
                    <div id="suggestion-reason-box" class="hidden bg-amber-50 border border-amber-200 rounded-2xl p-4">
                        <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-amber-500 mb-1">Note</p>
                        <p id="suggestion-reason" class="text-sm text-amber-900 font-medium leading-relaxed"></p>
                    </div>
                    <div id="suggested-desc-box" class="hidden bg-secondary/5 border border-secondary/20 rounded-2xl p-5">
                        <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-secondary mb-2">Suggested Description</p>
                        <p id="suggested-desc-text" class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap"></p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button id="btn-accept-suggestion"
                            class="flex-1 bg-secondary text-white text-xs font-black uppercase tracking-widest py-3 rounded-xl hover:bg-primary transition-all">
                            Accept & Publish
                        </button>
                        <a href="providers-upload.php?sid=<?= urlencode($sid) ?>"
                            class="flex-1 text-center border border-outline-variant text-primary text-xs font-bold uppercase tracking-widest py-3 rounded-xl hover:bg-surface-low transition-all">
                            Edit Manually
                        </a>
                    </div>
                </div>

            </div><!-- /verify-panel -->

            <script>
            (function() {
                const jobID = <?= json_encode($pendingJobID) ?>;
                const sid   = <?= json_encode($sid) ?>;
                let   polls = 0;
                const MAX_POLLS = 40; // ~2 min at 3s intervals

                function show(stateId) {
                    ['state-loading','state-approved','state-rejected','state-suggestion']
                        .forEach(id => document.getElementById(id).classList.add('hidden'));
                    document.getElementById(stateId).classList.remove('hidden');
                }

                async function poll() {
                    if (polls++ > MAX_POLLS) {
                        show('state-rejected');
                        document.getElementById('rejected-msg').textContent =
                            'Verification timed out. The agent did not respond in time. Please try resubmitting.';
                        return;
                    }
                    try {
                        const res  = await fetch(`verify-status.php?job=${encodeURIComponent(jobID)}`);
                        const data = await res.json();

                        if (!data.ready) {
                            setTimeout(poll, 3000);
                            return;
                        }

                        if (data.status === 'approved') {
                            show('state-approved');
                            if (data.message) document.getElementById('approved-msg').textContent = data.message;

                        } else if (data.status === 'rejected') {
                            show('state-rejected');
                            document.getElementById('rejected-msg').textContent =
                                data.message || 'Your listing did not pass verification.';

                        } else if (data.status === 'suggestion') {
                            show('state-suggestion');
                            if (data.message) {
                                document.getElementById('suggestion-reason').textContent = data.message;
                                document.getElementById('suggestion-reason-box').classList.remove('hidden');
                            }
                            if (data.suggested_description) {
                                document.getElementById('suggested-desc-text').textContent = data.suggested_description;
                                document.getElementById('suggested-desc-box').classList.remove('hidden');
                            }
                            // Accept suggestion → tell n8n to proceed with insert
                            document.getElementById('btn-accept-suggestion').addEventListener('click', async () => {
                                document.getElementById('btn-accept-suggestion').textContent = 'Publishing…';
                                await fetch('verify-accept.php', {
                                    method: 'POST',
                                    headers: {'Content-Type':'application/json'},
                                    body: JSON.stringify({ job_id: jobID, sid: sid })
                                });
                                show('state-approved');
                                document.getElementById('approved-msg').textContent = 'Your listing has been accepted with the AI-suggested description and is now live.';
                            });
                        }
                    } catch(e) {
                        setTimeout(poll, 4000);
                    }
                }

                poll();
            })();
            </script>

            <?php else: ?>
            <!-- ════════════════════════════════════════════
                 UPLOAD FORM VIEW
            ════════════════════════════════════════════ -->
            <div class="slide-up">
                <div class="mb-8">
                    <p class="font-mono text-[10px] uppercase tracking-[.2em] text-secondary mb-1">New Submission</p>
                    <h1 class="text-4xl font-black text-primary tracking-tighter leading-none">Upload a Listing</h1>
                    <p class="text-sm text-slate-400 mt-2">Your listing will be reviewed by the Smart Verify AI agent before going live.</p>
                </div>

                <div class="rounded-[2rem] bg-white border border-outline-variant/30 shadow-sm p-8 md:p-10 space-y-7">
                    <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-7">

                        <!-- Title -->
                        <div>
                            <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-1.5">Listing Title</label>
                            <input type="text" name="p_title" id="p_title" required
                                class="w-full rounded-xl border border-outline-variant bg-surface text-sm p-3.5 focus:outline-none focus:ring-2 focus:ring-secondary/40 transition">
                        </div>

                        <!-- Category + Status -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-1.5">Category</label>
                                <select name="p_cat" required class="w-full rounded-xl border border-outline-variant bg-surface text-sm p-3.5 focus:outline-none focus:ring-2 focus:ring-secondary/40">
                                    <option>Property</option>
                                    <option>Raw Material</option>
                                    <option>Land</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-1.5">Current Status</label>
                                <select name="p_status" required class="w-full rounded-xl border border-outline-variant bg-surface text-sm p-3.5 font-semibold text-secondary focus:outline-none focus:ring-2 focus:ring-secondary/40">
                                    <option value="Available">Available</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Upcoming">Upcoming</option>
                                    <option value="Not Available">Not Available</option>
                                    <option value="Sold">Sold</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description (no AI suggestion polling) -->
                        <div>
                            <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-1.5">Description</label>
                            <textarea name="p_desc" id="p_desc" rows="5" required
                                class="w-full rounded-xl border border-outline-variant bg-surface text-sm p-3.5 focus:outline-none focus:ring-2 focus:ring-secondary/40 resize-none transition"
                                placeholder="Describe the listing…"></textarea>
                        </div>

                        <!-- Price / Unit / Type -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-1.5">Price</label>
                                <input type="text" name="p_price" class="w-full rounded-xl border border-outline-variant bg-surface text-sm p-3.5 focus:outline-none focus:ring-2 focus:ring-secondary/40">
                            </div>
                            <div>
                                <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-1.5">Unit</label>
                                <select name="p_unit" class="w-full rounded-xl border border-outline-variant bg-surface text-sm p-3.5 focus:outline-none focus:ring-2 focus:ring-secondary/40">
                                    <option value="">None</option>
                                    <option value="Sq.ft">Sq.ft</option>
                                    <option value="Acre">Acre</option>
                                    <option value="Bigha">Bigha</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-1.5">Price Type</label>
                                <select name="p_price_type" class="w-full rounded-xl border border-outline-variant bg-surface text-sm p-3.5 focus:outline-none focus:ring-2 focus:ring-secondary/40">
                                    <option value="Fixed">Fixed</option>
                                    <option value="Negotiable">Negotiable</option>
                                </select>
                            </div>
                        </div>

                        <!-- Photo Upload -->
                        <div>
                            <label class="text-[10px] font-mono font-bold uppercase tracking-[.15em] text-slate-400 block mb-3">Property Photos <span class="text-red-400">(min. 1 required)</span></label>
                            <div class="grid grid-cols-5 gap-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="relative flex flex-col items-center justify-center aspect-square bg-surface rounded-2xl border-2 border-dashed border-outline-variant cursor-pointer overflow-hidden hover:border-secondary transition group">
                                    <span class="material-symbols-outlined text-slate-300 group-hover:text-secondary transition text-[28px]">add_a_photo</span>
                                    <input type="file" name="img<?= $i ?>" class="hidden" accept="image/*" onchange="preview(this,<?= $i ?>)">
                                    <img id="prev<?= $i ?>" class="absolute inset-0 w-full h-full object-cover hidden rounded-2xl">
                                </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- AI notice -->
                        <div class="flex items-start gap-3 bg-secondary/5 border border-secondary/20 rounded-2xl p-4">
                            <span class="material-symbols-outlined text-secondary mt-0.5 text-[18px]">info</span>
                            <p class="text-xs text-secondary/80 leading-relaxed">
                                After submission, the <strong>Smart Verify AI agent</strong> will audit your listing for accuracy, completeness, and policy compliance before it goes live. Do <em>not</em> close this tab — you'll see the result here.
                            </p>
                        </div>

                        <button type="submit" name="publish_product"
                            class="w-full rounded-2xl bg-primary py-4 text-white text-xs font-black uppercase tracking-[.2em] shadow-lg hover:bg-secondary transition-all active:scale-[.98]">
                            Submit &amp; Smart Verify →
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <script>
    function preview(input, n) {
        const img = document.getElementById('prev' + n);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => { img.src = e.target.result; img.classList.remove('hidden'); };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>