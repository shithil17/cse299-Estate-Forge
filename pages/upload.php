<!doctype html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Rems | Dashboard</title>
        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        />
        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        />
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
        <link rel="stylesheet" href="styles.css" />
    </head>
    <body class="bg-surface text-on-surface font-[Inter]">
        <header class="fixed top-0 z-50 w-full glass-nav shadow-[0_32px_32px_-12px_rgba(23,24,55,0.04)]">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8">
                <a href="index.html" class="text-xl font-semibold tracking-tight text-primary">Rems</a>

                <nav id="desktop-nav" class="hidden items-center gap-8 text-sm font-medium tracking-tight md:flex">
                    <a href="index.html" class="text-on-surface-variant transition-colors hover:text-secondary">Home</a>
                    <a href="products.php" class="border-b-2 border-secondary pb-1 font-semibold text-secondary">Properties</a>
                    <a href="#ecosystem" class="text-on-surface-variant transition-colors hover:text-secondary">AI Tools</a>
                    <a href="screens/cost-estimator/index.html" class="text-on-surface-variant transition-colors hover:text-secondary">Cost Estimator</a>
                </nav>

                <div class="hidden items-center gap-4 md:flex">
                    <a href="login.php" class="rounded-lg px-4 py-2 text-sm font-medium text-on-surface-variant transition-all hover:bg-surface-low">
                        Login
                    </a>
                    <a href="settings.php" class="rounded-lg bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white">
                        Settings
                    </a>
                </div>

                <button id="menu-toggle" class="md:hidden rounded-lg p-2 text-primary" aria-label="Toggle menu">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <div id="mobile-nav" class="hidden border-t border-outline-variant/30 bg-white/95 px-6 py-4 md:hidden">
                <div class="flex flex-col gap-3 text-sm font-medium">
                    <a href="index.html" class="mobile-link">Home</a>
                    <a href="products.php" class="mobile-link">Properties</a>
                    <a href="screens/cost-estimator/index.html" class="mobile-link">Cost Estimator</a>
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16">
            <div class="mx-auto max-w-7xl px-6 md:px-8">
                <h1 class="text-3xl font-bold text-primary mb-8">Property Dashboard</h1>
                
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    </div>
            </div>
        </main>

        <footer id="contact" class="bg-surface-low text-on-surface mt-auto">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 py-14 md:grid-cols-4 md:px-8">
                <div>
                    <span class="mb-4 block text-2xl font-bold text-primary">Rems</span>
                    <p class="text-sm leading-relaxed text-on-surface-variant">
                        Redefining real estate with intelligent curation and verified security.
                    </p>
                </div>
                <div>
                    <h4 class="mb-4 font-bold text-primary">Platform</h4>
                    <ul class="space-y-3 text-sm text-on-surface-variant">
                        <li><a href="#" class="hover:text-secondary">Search Map</a></li>
                        <li><a href="screens/cost-estimator/index.html" class="hover:text-secondary">AI Estimator</a></li>
                        <li><a href="mailto:partners@Rems.bd" class="hover:text-secondary">Developer Portal</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 font-bold text-primary">Company</h4>
                    <ul class="space-y-3 text-sm text-on-surface-variant">
                        <li><a href="#" class="hover:text-secondary">About Us</a></li>
                        <li><a href="mailto:legal@Rems.bd" class="hover:text-secondary">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 font-bold text-primary">Support</h4>
                    <ul class="space-y-3 text-sm text-on-surface-variant">
                        <li><a href="mailto:support@Rems.bd" class="hover:text-secondary">Help Center</a></li>
                        <li><a href="mailto:api@Rems.bd" class="hover:text-secondary">API Docs</a></li>
                    </ul>
                </div>
            </div>
            <div class="mx-auto flex max-w-7xl flex-col gap-2 border-t border-outline-variant/30 px-6 py-6 text-sm text-on-surface-variant md:flex-row md:items-center md:justify-between md:px-8">
                <span>© 2026 Rems. AI-Driven Real Estate Curation.</span>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-secondary">System Status</a>
                    <a href="#" class="hover:text-secondary">Security</a>
                </div>
            </div>
        </footer>

        <script src="script.js"></script>
    </body>
</html>