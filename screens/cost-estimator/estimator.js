const menuToggle = document.getElementById("menu-toggle");
const mobileNav = document.getElementById("mobile-nav");
const mobileLinks = document.querySelectorAll(".mobile-link");

menuToggle?.addEventListener("click", () => {
    const expanded = menuToggle.getAttribute("aria-expanded") === "true";
    menuToggle.setAttribute("aria-expanded", String(!expanded));
    mobileNav?.classList.toggle("hidden");
});

mobileLinks.forEach((link) => {
    link.addEventListener("click", () => {
        menuToggle?.setAttribute("aria-expanded", "false");
        mobileNav?.classList.add("hidden");
    });
});

const areaInput = document.getElementById("areaSqft");
const locationSelect = document.getElementById("locationCity");
const generateButton = document.getElementById("generateEstimate");

const typeButtons = Array.from(document.querySelectorAll(".type-option"));
const qualityButtons = Array.from(document.querySelectorAll(".quality-chip"));

const estimateTotal = document.getElementById("estimateTotal");
const marketShift = document.getElementById("marketShift");
const materialsCost = document.getElementById("materialsCost");
const laborCost = document.getElementById("laborCost");
const permitsCost = document.getElementById("permitsCost");
const materialsBar = document.getElementById("materialsBar");
const laborBar = document.getElementById("laborBar");
const permitsBar = document.getElementById("permitsBar");
const efficiencyValue = document.getElementById("efficiencyValue");
const savingsInsight = document.getElementById("savingsInsight");
const timelineDelta = document.getElementById("timelineDelta");

let selectedType = "residential";
let selectedQuality = "essential";

const TYPE_RATE = {
    residential: 3200,
    commercial: 4600,
};

const QUALITY_FACTOR = {
    essential: 1,
    premium: 1.18,
    ultra: 1.38,
};

const LOCATION_FACTOR = {
    Dhaka: 1.18,
    Chattogram: 1.1,
    Sylhet: 1.08,
    Khulna: 0.96,
    Rajshahi: 0.93,
};

const LOCATION_SHIFT = {
    Dhaka: "+8.2% Dhaka market shift",
    Chattogram: "+4.8% Chattogram market shift",
    Sylhet: "+3.9% Sylhet market shift",
    Khulna: "-1.1% Khulna market shift",
    Rajshahi: "-1.7% Rajshahi market shift",
};

function formatBDT(value) {
    return `BDT ${Math.round(value).toLocaleString("en-US")}`;
}

function styleTypeButtons() {
    typeButtons.forEach((button) => {
        const isActive = button.dataset.type === selectedType;
        button.classList.toggle("border-secondary", isActive);
        button.classList.toggle("text-on-secondary-container", isActive);
        button.classList.toggle("border-transparent", !isActive);
        button.classList.toggle("text-on-surface-variant", !isActive);
    });
}

function styleQualityButtons() {
    qualityButtons.forEach((button) => {
        const isActive = button.dataset.quality === selectedQuality;
        button.classList.toggle("bg-secondary-container", isActive);
        button.classList.toggle("text-on-secondary-container", isActive);
        button.classList.toggle("bg-surface-container-highest", !isActive);
        button.classList.toggle("text-on-surface-variant", !isActive);
    });
}

function updateEstimate() {
    const area = Number(areaInput?.value || 2500);
    const safeArea = Number.isFinite(area) && area > 0 ? area : 2500;
    const location = locationSelect?.value || "Dhaka";

    const baseRate = TYPE_RATE[selectedType] || TYPE_RATE.residential;
    const qualityFactor =
        QUALITY_FACTOR[selectedQuality] || QUALITY_FACTOR.essential;
    const locationFactor = LOCATION_FACTOR[location] || LOCATION_FACTOR.Dhaka;

    const total = safeArea * baseRate * qualityFactor * locationFactor;

    const materials = total * 0.52;
    const labor = total * 0.33;
    const permits = total * 0.09;

    estimateTotal.textContent = formatBDT(total);
    marketShift.textContent = LOCATION_SHIFT[location] || LOCATION_SHIFT.Dhaka;

    materialsCost.textContent = formatBDT(materials);
    laborCost.textContent = formatBDT(labor);
    permitsCost.textContent = formatBDT(permits);

    materialsBar.style.width = "52%";
    laborBar.style.width = "33%";
    permitsBar.style.width = "9%";

    const baseEfficiency = selectedType === "residential" ? 70 : 64;
    const qualityBoost =
        selectedQuality === "premium" ? 4 : selectedQuality === "ultra" ? 7 : 0;
    const cityPenalty =
        location === "Dhaka" ? 3 : location === "Chattogram" ? 1 : 0;
    const efficiency = Math.max(
        58,
        Math.min(84, baseEfficiency + qualityBoost - cityPenalty),
    );
    efficiencyValue.textContent = `${efficiency}%`;

    const savingsValue =
        total * (selectedType === "residential" ? 0.035 : 0.028);
    savingsInsight.textContent = `Using local suppliers and staged procurement can reduce projected cost by about ${formatBDT(savingsValue)}.`;

    const timelineMonths =
        Math.ceil(safeArea / (selectedType === "commercial" ? 210 : 260)) +
        (selectedQuality === "ultra" ? 2 : 0);
    timelineDelta.textContent = `Estimated completion based on current Bangladesh supply lead times: ${timelineMonths} months.`;
}

typeButtons.forEach((button) => {
    button.addEventListener("click", () => {
        selectedType = button.dataset.type || "residential";
        styleTypeButtons();
        updateEstimate();
    });
});

qualityButtons.forEach((button) => {
    button.addEventListener("click", () => {
        selectedQuality = button.dataset.quality || "essential";
        styleQualityButtons();
        updateEstimate();
    });
});

locationSelect?.addEventListener("change", updateEstimate);
areaInput?.addEventListener("input", updateEstimate);
generateButton?.addEventListener("click", updateEstimate);

styleTypeButtons();
styleQualityButtons();
updateEstimate();
