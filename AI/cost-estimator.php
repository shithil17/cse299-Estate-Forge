<?php
// Get session ID for navigation consistency
$sid = $_GET['sid'] ?? "";
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Rems | AI Cost Estimator</title>
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
                            "on-surface": "#171837",
                            "on-surface-variant": "#43474e",
                            "outline-variant": "#c4c6cf",
                            "primary-container": "#1a365d",
                        },
                    },
                },
            };
        </script>
        <style>
            .glass-nav { background: rgba(252, 248, 255, 0.8); backdrop-filter: blur(12px); }
            .option-card.active { border-color: #13696a; background-color: #f5f2ff; ring: 2px solid #13696a; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            .animate-spin { animation: spin 1s linear infinite; }
            #aiResponseContent { font-family: 'Inter', sans-serif; white-space: pre-wrap; }
            .preview-glow { box-shadow: 0 0 15px rgba(19, 105, 106, 0.1); }
        </style>
    </head>
    <body class="bg-surface text-on-surface font-[Inter]">
        <header class="fixed top-0 z-50 w-full glass-nav shadow-sm">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <a href="../index.php?sid=<?= $sid ?>" class="text-xl font-bold text-primary">Rems</a>
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
                    <a href="../index.php?sid=<?= $sid ?>" class="text-on-surface-variant hover:text-secondary">Home</a>
                    <a href="products.php?sid=<?= $sid ?>" class="text-on-surface-variant hover:text-secondary">Properties</a>
                    <a href="#" class="text-secondary font-bold border-b-2 border-secondary pb-1">Cost Estimator</a>
                </nav>
                <div class="flex items-center gap-4">
                    <a href="settings.php?sid=<?= $sid ?>" class="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Settings</a>
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16">
            <div class="mx-auto max-w-4xl px-6">
                <div class="text-center mb-10">
                    <h1 class="text-3xl font-black text-primary mb-2">AI Cost Estimator</h1>
                    <p class="text-on-surface-variant text-sm">Select a category and provide details for a precise Dhaka-market real estate estimation.</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
                    <button type="button" onclick="selectType('Construction Cost')" class="option-card active border-2 rounded-2xl p-4 text-center transition-all group">
                        <span class="material-symbols-outlined text-secondary group-hover:scale-110 transition-transform mb-2">engineering</span>
                        <p class="text-xs font-bold">Construction</p>
                    </button>
                    <button type="button" onclick="selectType('Rent')" class="option-card border-2 rounded-2xl p-4 text-center transition-all hover:border-outline-variant group">
                        <span class="material-symbols-outlined text-on-surface-variant group-hover:scale-110 transition-transform mb-2">payments</span>
                        <p class="text-xs font-bold">Rent</p>
                    </button>
                    <button type="button" onclick="selectType('Land')" class="option-card border-2 rounded-2xl p-4 text-center transition-all hover:border-outline-variant group">
                        <span class="material-symbols-outlined text-on-surface-variant group-hover:scale-110 transition-transform mb-2">landscape</span>
                        <p class="text-xs font-bold">Land</p>
                    </button>
                    <button type="button" onclick="selectType('Apartment')" class="option-card border-2 rounded-2xl p-4 text-center transition-all hover:border-outline-variant group">
                        <span class="material-symbols-outlined text-on-surface-variant group-hover:scale-110 transition-transform mb-2">apartment</span>
                        <p class="text-xs font-bold">Apartment</p>
                    </button>
                </div>

                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-outline-variant/30 relative overflow-hidden">
                    <div id="livePreview" class="absolute top-0 right-0 bg-secondary text-white px-6 py-2 rounded-bl-2xl text-[10px] font-bold tracking-widest uppercase flex items-center gap-2 opacity-0 transition-opacity">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        Live Preview: <span id="previewValue">0</span> sq.ft
                    </div>

                    <form id="estimatorForm" class="space-y-6 mt-4">
                        <input type="hidden" id="estimateType" name="type" value="Construction Cost">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold mb-2">Land Size</label>
                                <div class="flex">
                                    <input type="number" id="landSize" required step="0.01" oninput="updateLivePreview()" class="flex-1 rounded-l-xl border-outline-variant text-sm focus:ring-secondary focus:border-secondary" placeholder="e.g. 5">
                                    <select id="unit" onchange="updateLivePreview()" class="rounded-r-xl border-l-0 border-outline-variant text-sm bg-surface-low font-bold text-secondary">
                                        <option value="sq.ft">sq.ft</option>
                                        <option value="Katha">Katha</option>
                                        <option value="Bigha">Bigha</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-2">Total Floors</label>
                                <input type="number" id="floors" required min="1" oninput="updateLivePreview()" class="w-full rounded-xl border-outline-variant text-sm focus:ring-secondary focus:border-secondary" placeholder="e.g. 6">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold mb-2">Primary Area (Dhaka City)</label>
                                <select id="location" required class="w-full rounded-xl border-outline-variant text-sm focus:ring-secondary focus:border-secondary">
                                    <option value="">Choose your location</option>
                                    <optgroup label="Prime Residential">
                                        <option>Gulshan</option>
                                        <option>Banani</option>
                                        <option>Dhanmondi</option>
                                        <option>Baridhara</option>
                                        <option>Bashundhara R/A</option>
                                    </optgroup>
                                    <optgroup label="Developing / Affordable">
                                        <option>Uttara</option>
                                        <option>Mirpur</option>
                                        <option>Mohammadpur</option>
                                        <option>Badda</option>
                                        <option>Purbachal</option>
                                        <option>Aftabnagar</option>
                                        <option>Banasree</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold mb-2">Additional Description</label>
                                <textarea id="description" rows="3" class="w-full rounded-xl border-outline-variant text-sm focus:ring-secondary focus:border-secondary" placeholder="Mention material quality, finishing types, or specific requirements..."></textarea>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-secondary text-white py-4 rounded-2xl font-bold text-sm shadow-lg hover:bg-opacity-90 hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined" id="btnIcon">analytics</span>
                            <span id="btnText">Generate AI Estimate</span>
                        </button>
                    </form>
                </div>

                <div id="responseContainer" class="hidden mt-10 p-8 rounded-[2rem] bg-primary text-white border border-primary-container shadow-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary">verified_user</span>
                            <h3 class="text-lg font-bold">AI Detailed Report</h3>
                        </div>
                        <span class="text-[10px] bg-secondary/20 text-secondary px-3 py-1 rounded-full font-bold uppercase">Dhaka Market Data</span>
                    </div>
                    
                    <div id="aiResponseContent" class="text-sm leading-relaxed text-blue-100 border-l-2 border-secondary/30 pl-6 italic"></div>
                    
                    <div class="mt-8 flex gap-4">
                        <button onclick="window.print()" class="flex-1 bg-white/10 hover:bg-white/20 text-white py-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">download</span> Save Report
                        </button>
                        <button onclick="location.reload()" class="flex-1 border border-white/20 hover:bg-white/5 text-white py-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">refresh</span> New Quote
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <script>
            function updateLivePreview() {
                const size = parseFloat(document.getElementById('landSize').value) || 0;
                const unit = document.getElementById('unit').value;
                const floors = parseInt(document.getElementById('floors').value) || 0;
                const previewEl = document.getElementById('livePreview');
                const valEl = document.getElementById('previewValue');

                let sqft = size;
                if (unit === 'Katha') sqft = size * 720;
                if (unit === 'Bigha') sqft = size * 14400;

                if (size > 0) {
                    previewEl.classList.replace('opacity-0', 'opacity-100');
                    valEl.innerText = Math.round(sqft * (floors || 1)).toLocaleString();
                } else {
                    previewEl.classList.replace('opacity-100', 'opacity-0');
                }
            }

            function selectType(type) {
                document.getElementById('estimateType').value = type;
                const buttons = document.querySelectorAll('.option-card');
                buttons.forEach(btn => {
                    btn.classList.remove('active');
                    const text = btn.querySelector('p').innerText;
                    if(type.toLowerCase().includes(text.toLowerCase())) btn.classList.add('active');
                });
            }

            const form = document.getElementById('estimatorForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');
            const responseContainer = document.getElementById('responseContainer');
            const responseContent = document.getElementById('aiResponseContent');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                submitBtn.disabled = true;
                btnIcon.classList.add('animate-spin');
                btnIcon.innerText = 'sync';
                btnText.innerText = 'Analyzing Dhaka Market...';
                
                const currentData = {
                    location: document.getElementById('location').value,
                    landSize: document.getElementById('landSize').value,
                    unit: document.getElementById('unit').value,
                    floors: document.getElementById('floors').value
                };

                const payload = {
                    requestType: document.getElementById('estimateType').value,
                    ...currentData,
                    description: document.getElementById('description').value,
                    timestamp: new Date().toLocaleString('en-BD', { timeZone: 'Asia/Dhaka' })
                };

                try {
                    const response = await fetch('http://localhost:5678/webhook-test/costestrems', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (response.ok) {
                        let rawText = await response.text();
                        console.log("Raw Response:", rawText);

                        // Clean potential n8n leading '=' or whitespace
                        rawText = rawText.trim().replace(/^=/, ''); 

                        let data = JSON.parse(rawText);
                        
                        // Handle if n8n returns an array
                        if (Array.isArray(data)) data = data[0];

                        // Target the 'estimate' object inside the response
                        const est = data.estimate;
                        let finalDisplay = "";

                        if (est && typeof est === 'object') {
                            finalDisplay = `🏗️ CONSTRUCTION ESTIMATE SUMMARY\n`;
                            finalDisplay += `------------------------------------------\n`;
                            finalDisplay += `📍 Location: ${currentData.location}\n`;
                            finalDisplay += `📏 Project: ${currentData.landSize} ${currentData.unit} (${currentData.floors} Floors)\n\n`;
                            
                            finalDisplay += `MARKET RATES (Current):\n`;
                            finalDisplay += `• Rod: ${est.rod?.brand || "N/A"} (৳${Number(est.rod?.price || 0).toLocaleString()} ${est.rod?.unit || "BDT/Ton"})\n`;
                            finalDisplay += `• Cement: ${est.cement?.brand || "N/A"} (৳${Number(est.cement?.price || 0).toLocaleString()} ${est.cement?.unit || "BDT/Bag"})\n`;
                            finalDisplay += `• Bricks: ৳${est.brickPrice || 0}/unit\n\n`;

                            finalDisplay += `REQUIRED QUANTITIES (Est):\n`;
                            finalDisplay += `• Bricks: ${Number(est.quantities?.bricks || 0).toLocaleString()} pcs\n`;
                            finalDisplay += `• Cement: ${Number(est.quantities?.cementBags || 0).toLocaleString()} bags\n`;
                            finalDisplay += `• Rod: ${est.quantities?.rodTons || 0} Tons\n\n`;

                            finalDisplay += `💰 TOTAL PROJECT ESTIMATE: ৳ ${Number(est.totalCost || 0).toLocaleString()} BDT\n\n`;
                            finalDisplay += `📝 AI Analysis: ${est.notes || "No additional notes."}`;
                        } else {
                            // Fallback if 'estimate' isn't formatted correctly
                            finalDisplay = "Received response, but estimate details were missing. Please verify n8n output.";
                        }

                        responseContent.innerText = finalDisplay;
                        responseContainer.classList.remove('hidden');
                        responseContainer.scrollIntoView({ behavior: 'smooth' });
                    } else {
                        alert("The server returned an error. Status: " + response.status);
                    }
                } catch (error) {
                    console.error("Fetch/Parse Error:", error);
                    alert("AI format error. Check console for details.");
                } finally {
                    submitBtn.disabled = false;
                    btnIcon.classList.remove('animate-spin');
                    btnIcon.innerText = 'analytics';
                    btnText.innerText = 'Generate AI Estimate';
                }
            });
        </script>
    </body>
</html>