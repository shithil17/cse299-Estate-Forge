const menuToggle = document.getElementById("menu-toggle");
const mobileNav = document.getElementById("mobile-nav");
const mobileLinks = document.querySelectorAll(".mobile-link");
const searchForm = document.getElementById("search-form");
const revealTargets = document.querySelectorAll(".reveal");

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

searchForm?.addEventListener("submit", (event) => {
    event.preventDefault();

    const location = document.getElementById("location")?.value || "Bangladesh";
    const propertyType =
        document.getElementById("propertyType")?.value || "Property";
    const price = document.getElementById("price")?.value || "Any budget";

    const query = encodeURIComponent(
        `${propertyType} for sale in ${location} ${price}`,
    );
    window.open(`https://www.google.com/maps/search/${query}`, "_blank");
});

const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.15 },
);

revealTargets.forEach((target) => observer.observe(target));
