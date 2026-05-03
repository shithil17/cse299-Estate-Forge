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
            /* Loading Spinner Animation */
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            .animate-spin { animation: spin 1s linear infinite; }
        </style>
    </head>
    <body class="bg-surface text-on-surface font-[Inter]">
        <header class="fixed top-0 z-50 w-full glass-nav shadow-sm">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <a href="index.php?sid=<?= $sid ?>" class="text-xl font-bold text-primary">Rems</a>
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
                    <a href="index.php?sid=<?= $sid ?>" class="text-on-surface-variant hover:text-secondary">Home</a>
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

                <!-- 1. Category Selection -->
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

                <!-- 2. Form Inputs -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-outline-variant/30">
                    <form id="estimatorForm" class="space-y-6">
                        <input type="hidden" id="estimateType" name="type" value="Construction Cost">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Land Size & Unit -->
                            <div>
                                <label class="block text-xs font-bold mb-2">Land Size</label>
                                <div class="flex">
                                    <input type="number" id="landSize" required step="0.01" class="flex-1 rounded-l-xl border-outline-variant text-sm" placeholder="e.g. 5">
                                    <select id="unit" class="rounded-r-xl border-l-0 border-outline-variant text-sm bg-surface-low">
                                        <option value="sq.ft">sq.ft</option>
                                        <option value="Katha">Katha</option>
                                        <option value="Bigha">Bigha</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Floors -->
                            <div>
                                <label class="block text-xs font-bold mb-2">Total Floors</label>
                                <input type="number" id="floors" required min="1" class="w-full rounded-xl border-outline-variant text-sm" placeholder="e.g. 6">
                            </div>

                            <!-- Dhaka Area Selection -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold mb-2">Primary Area (Dhaka City)</label>
                                <select id="location" required class="w-full rounded-xl border-outline-variant text-sm">
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

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold mb-2">Additional Description</label>
                                <textarea id="description" rows="3" class="w-full rounded-xl border-outline-variant text-sm" placeholder="Mention material quality, finishing types, or specific requirements..."></textarea>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-secondary text-white py-4 rounded-2xl font-bold text-sm shadow-lg hover:opacity-95 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined" id="btnIcon">analytics</span>
                            <span id="btnText">Generate AI Estimate</span>
                        </button>
                    </form>
                </div>

                <!-- 3. AI Response Placeholder -->
                <div id="responseContainer" class="hidden mt-10 p-8 rounded-[2rem] bg-primary text-white border border-primary-container shadow-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary">verified_user</span>
                            <h3 class="text-lg font-bold">AI Detailed Report</h3>
                        </div>
                        <span class="text-[10px] bg-secondary/20 text-secondary px-3 py-1 rounded-full font-bold uppercase">Dhaka Market Data</span>
                    </div>
                    
                    <div id="aiResponseContent" class="text-sm leading-relaxed whitespace-pre-line text-blue-100 border-l-2 border-secondary/30 pl-6 italic">
                        <!-- Response will be injected here via JS -->
                    </div>
                    
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
            // Handle Category Selection UI
            function selectType(type) {
                document.getElementById('estimateType').value = type;
                const buttons = document.querySelectorAll('.option-card');
                buttons.forEach(btn => {
                    btn.classList.remove('active');
                    const text = btn.querySelector('p').innerText;
                    // Check if clicked type contains the button's text (e.g., 'Construction' in 'Construction Cost')
                    if(type.toLowerCase().includes(text.toLowerCase())) {
                        btn.classList.add('active');
                    }
                });
            }

            // n8n Webhook Integration
            const form = document.getElementById('estimatorForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');
            const responseContainer = document.getElementById('responseContainer');
            const responseContent = document.getElementById('aiResponseContent');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // UI Loading State
                submitBtn.disabled = true;
                btnIcon.classList.add('animate-spin');
                btnIcon.innerText = 'sync';
                btnText.innerText = 'Analyzing Dhaka Market...';
                
                // Collect Form Data
                const payload = {
                    requestType: document.getElementById('estimateType').value,
                    landSize: document.getElementById('landSize').value,
                    unit: document.getElementById('unit').value,
                    floors: document.getElementById('floors').value,
                    location: document.getElementById('location').value,
                    description: document.getElementById('description').value,
                    timestamp: new Date().toLocaleString('en-BD', { timeZone: 'Asia/Dhaka' })
                };

                try {
                    /** 
                     * REPLACE THE URL BELOW with your actual n8n Production Webhook URL 
                     * Ensure your n8n Webhook node is set to 'POST' and 'JSON'
                     */
                    const WEBHOOK_URL = 'https://n8n.yourdomain.com/webhook/rems-cost-estimator';

                    const response = await fetch(WEBHOOK_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (response.ok) {
                        const data = await response.json();
                        
                        /** 
                         * Assuming your n8n workflow ends with a 'Respond to Webhook' node 
                         * or an HTTP node that returns a JSON object like { "estimate": "text here" }
                         */
                        responseContent.innerText = data.estimate || "Error: No AI response was generated. Check n8n workflow output.";
                        responseContainer.classList.remove('hidden');
                        responseContainer.scrollIntoView({ behavior: 'smooth' });
                    } else {
                        throw new Error(`Server responded with ${response.status}`);
                    }
                } catch (error) {
                    console.error("n8n Error:", error);
                    alert("The AI service is currently busy. Please verify your n8n webhook URL and try again.");
                } finally {
                    // Reset UI
                    submitBtn.disabled = false;
                    btnIcon.classList.remove('animate-spin');
                    btnIcon.innerText = 'analytics';
                    btnText.innerText = 'Generate AI Estimate';
                }
            });
        </script>
    </body>
</html>