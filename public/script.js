/* =========================================================
   MAIN WEBSITE JAVASCRIPT
========================================================= */

/* ACTIVE NAVIGATION - derived from the Laravel route path, never a file name */
const navigationLinks = document.querySelectorAll(".nav-links a, .mobile-nav > a, .nav-cta");
const navPathRules = [
    { key: "tours", pattern: /^\/(?:tours|destinations)(?:\/|$)/ },
    { key: "hotels", pattern: /^\/(?:hotel|hotels)(?:\/|$)/ },
    { key: "car-rental", pattern: /^\/transport(?:\/|$)/ },
    { key: "about", pattern: /^\/about(?:\/|$)/ },
    { key: "contact", pattern: /^\/contact(?:\/|$)/ },
    { key: "home", pattern: /^\/?$/ }
];
const bookingTypeToNavKey = { hotel: "hotels", vehicle: "car-rental", tour: "tours" };

function getPathname(value) {
    try {
        return new URL(value, window.location.href).pathname.replace(/\/+$/, "") || "/";
    } catch (error) { return "/"; }
}

function getNavKey(value) {
    const pathname = getPathname(value);
    const rule = navPathRules.find(function (entry) { return entry.pattern.test(pathname); });
    return rule ? rule.key : null;
}

/* /book is the search for one booking type, so it highlights the same navbar
   entry as the listing that type belongs to. Without ?type= it has no single
   home, so it stays unhighlighted rather than guessing. */
function getActiveNavPage() {
    const pathname = getPathname(window.location.href);

    if (pathname === "/book") {
        const type = new URLSearchParams(window.location.search).get("type");
        return type ? bookingTypeToNavKey[type] || null : null;
    }

    if (/^\/bookings(?:\/|$)/.test(pathname)) {
        const type = new URLSearchParams(window.location.search).get("type");
        return bookingTypeToNavKey[type] || "booking";
    }

    return getNavKey(window.location.href);
}

function updateActiveNavigation() {
    const activePage = getActiveNavPage();
    navigationLinks.forEach(function (link) {
        const isActive = getNavKey(link.href) === activePage;
        link.classList.toggle("active", isActive);
        if (isActive) link.setAttribute("aria-current", "page");
        else link.removeAttribute("aria-current");
    });
}
updateActiveNavigation();

document.addEventListener("DOMContentLoaded", function () {

    /* ================= NAVBAR ================= */

    const navbar = document.getElementById("mainNavbar");
    const navToggle = document.getElementById("navToggle");
    const mobileNav = document.getElementById("mobileNav");

    if (navbar) {
        function handleNavbarScroll() {
            navbar.classList.toggle(
                "scrolled",
                window.scrollY > 50
            );
        }

        window.addEventListener(
            "scroll",
            handleNavbarScroll
        );

        handleNavbarScroll();
    }

    function closeMobileNav() {
        if (navToggle) {
            navToggle.classList.remove("active");
        }

        if (mobileNav) {
            mobileNav.classList.remove("active");
        }

        document.body.style.overflow = "";
    }

    if (navToggle && mobileNav) {
        navToggle.addEventListener("click", function () {
            navToggle.classList.toggle("active");
            mobileNav.classList.toggle("active");

            document.body.style.overflow =
                mobileNav.classList.contains("active")
                    ? "hidden"
                    : "";
        });

        mobileNav
            .querySelectorAll("a")
            .forEach(function (link) {
                link.addEventListener(
                    "click",
                    closeMobileNav
                );
            });
    }

    window.addEventListener("resize", function () {
        if (
            window.innerWidth > 991 &&
            mobileNav &&
            mobileNav.classList.contains("active")
        ) {
            closeMobileNav();
        }
    });












    /* ================= DESTINATION SLIDER ================= */

    const destinations = [
        {
            id: 0,
            title: "Package Tours",
            location: "Api Nampa",
            sublocation: "Sudurpashchim, Nepal",
            description:
                "Explore amazing destinations with our carefully designed package tours, created to make your journey comfortable, exciting, and hassle-free.",
            image:
                "https://media.travelhimalayanepal.com/news/api-himal-base-camp-trek-guide-far-west-2026.webp",
            thumb:
                "https://media.travelhimalayanepal.com/news/api-himal-base-camp-trek-guide-far-west-2026.webp"
        },
        {
            id: 1,
            title: "Car Rent",
            location: "All Over Nepal",
            sublocation: "All Over Nepal",
            description:
                "Enjoy safe, comfortable, and reliable car rental services for local and long-distance travel.",
            image:
                "https://i.gaw.to/content/photos/67/86/678633-une-collection-de-42-ferrari-et-voitures-exotiques-bientot-a-l-encan.jpg",
            thumb:
                "https://i.gaw.to/content/photos/67/86/678633-une-collection-de-42-ferrari-et-voitures-exotiques-bientot-a-l-encan.jpg"
        },
        {
            id: 2,
            title: "Hotel Booking",
            location: "All Over Nepal",
            sublocation: "Hotels and Resorts",
            description:
                "Find comfortable accommodation with our reliable hotel booking service for vacations, business trips and tours.",
            image:
                "https://cdn.confident-group.com/wp-content/uploads/2025/01/09175739/villa-features-scaled.jpg",
            thumb:
                "https://cdn.confident-group.com/wp-content/uploads/2025/01/09175739/villa-features-scaled.jpg"
        },
        {
            id: 3,
            title: "Air Ticket Booking",
            location: "Domestic and International",
            sublocation: "Flight Booking",
            description:
                "Book domestic and international air tickets with convenient flight options at competitive prices.",
            image:
                "https://media.istockphoto.com/id/498240641/photo/business-man-traveling.jpg?s=612x612&w=0&k=20&c=rFiGXOhvTA5C63MJvPocXZq1PU7LTaAoQ8_qPnS4ae4=",
            thumb:
                "https://media.istockphoto.com/id/498240641/photo/business-man-traveling.jpg?s=612x612&w=0&k=20&c=rFiGXOhvTA5C63MJvPocXZq1PU7LTaAoQ8_qPnS4ae4="
        },
        {
            id: 4,
            title: "Airport Drop & Pickup",
            location: "All Over Nepal",
            sublocation: "Airport Transfer",
            description:
                "Enjoy safe, punctual and comfortable airport pickup and drop-off services for a stress-free journey.",
            image:
                "https://media.tacdn.com/media/attractions-splice-spp-674x446/12/02/91/3c.jpg",
            thumb:
                "https://media.tacdn.com/media/attractions-splice-spp-674x446/12/02/91/3c.jpg"
        }
    ];

    let heroIndex = 0;
    let heroAutoRotate = null;
    const heroRotateDelay = 6000;

    function renderThumbnails() {
        const container =
            document.getElementById("thumbnailsContainer");

        if (!container) return;

        container.innerHTML = destinations
            .map(function (destination, index) {
                return `
                    <div
                        class="thumb-item ${index === heroIndex
                        ? "active"
                        : ""
                    }"
                        data-index="${index}"
                    >
                        <div class="thumb-info">
                            <h5>${destination.title}</h5>
                            <p>${destination.location}</p>
                        </div>

                        <div class="thumb-img">
                            <img
                                src="${destination.thumb}"
                                alt="${destination.title}"
                            >
                        </div>
                    </div>
                `;
            })
            .join("");

        container
            .querySelectorAll(".thumb-item")
            .forEach(function (item) {
                item.addEventListener(
                    "click",
                    function () {
                        changeDestination(
                            Number(item.dataset.index)
                        );
                    }
                );
            });
    }

    function renderDots() {
        const container =
            document.getElementById("paginationDots");

        if (!container) return;

        container.innerHTML = destinations
            .map(function (_, index) {
                return `
                    <div
                        class="dot ${index === heroIndex
                        ? "active"
                        : ""
                    }"
                        data-index="${index}"
                    ></div>
                `;
            })
            .join("");

        container
            .querySelectorAll(".dot")
            .forEach(function (dot) {
                dot.addEventListener(
                    "click",
                    function () {
                        changeDestination(
                            Number(dot.dataset.index)
                        );
                    }
                );
            });
    }

    function changeDestination(index) {
        if (
            index < 0 ||
            index >= destinations.length ||
            index === heroIndex
        ) {
            return;
        }

        heroIndex = index;

        const destination = destinations[index];

        const titleElement =
            document.getElementById("heroTitle");

        const descriptionElement =
            document.getElementById(
                "heroDescription"
            );

        const backgroundImage =
            document.getElementById("heroBgImage");

        if (
            !titleElement ||
            !descriptionElement ||
            !backgroundImage
        ) {
            return;
        }

        titleElement.style.opacity = "0";
        descriptionElement.style.opacity = "0";
        backgroundImage.style.opacity = "0";

        setTimeout(function () {
            backgroundImage.src =
                destination.image;

            titleElement.textContent =
                destination.title;

            descriptionElement.textContent =
                destination.description;

            function showDestination() {
                backgroundImage.style.opacity = "1";
                titleElement.style.opacity = "1";
                descriptionElement.style.opacity =
                    "1";

                backgroundImage.removeEventListener(
                    "load",
                    showDestination
                );
            }

            backgroundImage.addEventListener(
                "load",
                showDestination
            );

            if (
                backgroundImage.complete &&
                backgroundImage.naturalWidth !== 0
            ) {
                showDestination();
            }
        }, 400);

        document
            .querySelectorAll(".thumb-item")
            .forEach(function (item, itemIndex) {
                item.classList.toggle(
                    "active",
                    itemIndex === index
                );
            });

        document
            .querySelectorAll(".dot")
            .forEach(function (dot, dotIndex) {
                dot.classList.toggle(
                    "active",
                    dotIndex === index
                );
            });
    }

    function stopHeroAutoRotate() {
        if (heroAutoRotate) {
            clearInterval(heroAutoRotate);
            heroAutoRotate = null;
        }
    }

    function startHeroAutoRotate() {
        stopHeroAutoRotate();

        heroAutoRotate = setInterval(
            function () {
                const nextIndex =
                    (heroIndex + 1) %
                    destinations.length;

                changeDestination(nextIndex);
            },
            heroRotateDelay
        );
    }

    if (
        document.getElementById(
            "thumbnailsContainer"
        ) ||
        document.getElementById(
            "paginationDots"
        )
    ) {
        renderThumbnails();
        renderDots();
        startHeroAutoRotate();
    }

    const heroSection =
        document.querySelector(".hero-section");

    if (heroSection) {
        heroSection.addEventListener(
            "click",
            function () {
                stopHeroAutoRotate();

                setTimeout(
                    startHeroAutoRotate,
                    12000
                );
            }
        );
    }













    /* ================= VISTA DESTINATION SLIDER ================= */

    const vistaSection = document.querySelector(".vista-showcase");

    if (vistaSection) {
        const vistaDeck =
            vistaSection.querySelector(".vista-deck");

        const vistaCards = Array.from(
            vistaSection.querySelectorAll(".vista-card")
        );

        const vistaBackdrop =
            vistaSection.querySelector(".vista-backdrop");

        const vistaBackground =
            vistaSection.querySelector("#vista-bg-img");

        const vistaContent =
            vistaSection.querySelector(".vista-main");

        const vistaLocation =
            vistaSection.querySelector("#vista-loc");

        const vistaHeadline =
            vistaSection.querySelector("#vista-headline");

        const vistaSummary =
            vistaSection.querySelector("#vista-summary");

        const vistaDiscoverLink =
            vistaSection.querySelector("#vista-link");

        const vistaCount =
            vistaSection.querySelector("#vista-count");

        const vistaProgress =
            vistaSection.querySelector("#vista-track-fill");

        let vistaCurrentIndex = 0;
        let vistaAutoTimer = null;
        let vistaImageTimer = null;

        const vistaDelay = 5000;

        /*
         * Move exactly one card for each slide.
         */
        function moveVistaCardRow(index) {
            const deckStyles =
                window.getComputedStyle(vistaDeck);

            const cardGap =
                parseFloat(
                    deckStyles.columnGap ||
                    deckStyles.gap
                ) || 0;

            const oneCardStep =
                vistaCards[0].offsetWidth + cardGap;

            const maximumScroll =
                vistaDeck.scrollWidth -
                vistaDeck.clientWidth;

            const targetScroll = Math.min(
                index * oneCardStep,
                maximumScroll
            );

            vistaDeck.scrollTo({
                left: targetScroll,
                behavior: "smooth"
            });
        }

        /*
         * Change the large background image.
         */
        function changeVistaBackground(selectedCard) {
            clearTimeout(vistaImageTimer);

            vistaBackdrop.classList.add("changing");

            vistaImageTimer = setTimeout(function () {
                vistaBackground.src =
                    selectedCard.dataset.background;

                vistaBackground.alt =
                    selectedCard.dataset.location ||
                    "Destination";

                vistaBackdrop.classList.remove("changing");
            }, 250);
        }

        /*
         * Select and display a destination.
         */
        function selectVistaCard(index, moveCards = true) {
            index =
                (index + vistaCards.length) %
                vistaCards.length;

            vistaCurrentIndex = index;

            /*
             * Highlight only the selected card.
             */
            vistaCards.forEach(function (
                card,
                cardIndex
            ) {
                const isSelected =
                    cardIndex === vistaCurrentIndex;

                card.classList.toggle(
                    "active",
                    isSelected
                );

                card.setAttribute(
                    "aria-selected",
                    String(isSelected)
                );
            });

            const selectedCard =
                vistaCards[vistaCurrentIndex];

            /*
             * Update left content.
             */
            vistaLocation.textContent =
                selectedCard.dataset.location || "";

            vistaHeadline.innerHTML =
                (
                    selectedCard.dataset.title || ""
                ).replace("|", "<br>");

            vistaSummary.textContent =
                selectedCard.dataset.description || "";

            vistaDiscoverLink.href =
                selectedCard.dataset.link || "#";

            /*
             * Restart content animation.
             */
            vistaContent.classList.remove("animate");

            void vistaContent.offsetWidth;

            vistaContent.classList.add("animate");

            /*
             * Update the background.
             */
            changeVistaBackground(selectedCard);

            /*
             * Update counter.
             */
            vistaCount.textContent =
                String(vistaCurrentIndex + 1).padStart(
                    2,
                    "0"
                ) +
                " / " +
                String(vistaCards.length).padStart(
                    2,
                    "0"
                );

            /*
             * Update progress bar.
             */
            vistaProgress.style.width =
                (
                    (vistaCurrentIndex + 1) /
                    vistaCards.length
                ) *
                100 +
                "%";

            /*
             * Move the row only after selection.
             */
            if (moveCards) {
                moveVistaCardRow(vistaCurrentIndex);
            }
        }

        /*
         * Automatic sequential slider.
         */
        function startVistaAutomaticSlider() {
            clearTimeout(vistaAutoTimer);

            vistaAutoTimer = setTimeout(
                function nextVistaSlide() {
                    selectVistaCard(
                        vistaCurrentIndex + 1,
                        true
                    );

                    vistaAutoTimer = setTimeout(
                        nextVistaSlide,
                        vistaDelay
                    );
                },
                vistaDelay
            );
        }

        /*
         * Card click and keyboard selection.
         */
        vistaCards.forEach(function (card, index) {
            card.addEventListener(
                "click",
                function () {
                    selectVistaCard(index, true);
                    startVistaAutomaticSlider();
                }
            );

            card.addEventListener(
                "keydown",
                function (event) {
                    if (
                        event.key === "Enter" ||
                        event.key === " "
                    ) {
                        event.preventDefault();

                        selectVistaCard(index, true);
                        startVistaAutomaticSlider();
                    }
                }
            );
        });

        /*
         * Mouse-wheel movement scrolls cards only.
         * It does not select or change the background.
         */
        vistaDeck.addEventListener(
            "wheel",
            function (event) {
                const scrollAmount =
                    Math.abs(event.deltaX) >
                        Math.abs(event.deltaY)
                        ? event.deltaX
                        : event.deltaY;

                const maximumScroll =
                    vistaDeck.scrollWidth -
                    vistaDeck.clientWidth;

                const canScroll =
                    (
                        scrollAmount > 0 &&
                        vistaDeck.scrollLeft <
                        maximumScroll
                    ) ||
                    (
                        scrollAmount < 0 &&
                        vistaDeck.scrollLeft > 0
                    );

                if (canScroll) {
                    event.preventDefault();

                    vistaDeck.scrollBy({
                        left: scrollAmount,
                        behavior: "smooth"
                    });
                }
            },
            {
                passive: false
            }
        );

        /*
         * Pause when browser tab is hidden.
         */
        document.addEventListener(
            "visibilitychange",
            function () {
                if (document.hidden) {
                    clearTimeout(vistaAutoTimer);
                } else {
                    startVistaAutomaticSlider();
                }
            }
        );

        /*
         * Initial selected card.
         */
        vistaCards.forEach(function (card, index) {
            const isFirstCard = index === 0;

            card.classList.toggle(
                "active",
                isFirstCard
            );

            card.setAttribute(
                "aria-selected",
                String(isFirstCard)
            );
        });

        vistaDeck.scrollLeft = 0;

        startVistaAutomaticSlider();
    }














    /* ================= SERVICES COUNTER ================= */

    const statNumbers =
        document.querySelectorAll(
            ".trvl-stat-num"
        );

    if (
        statNumbers.length &&
        "IntersectionObserver" in window
    ) {
        const counterObserver =
            new IntersectionObserver(
                function (entries, observer) {
                    entries.forEach(
                        function (entry) {
                            if (
                                !entry.isIntersecting
                            ) {
                                return;
                            }

                            const counter =
                                entry.target;

                            const target =
                                parseInt(
                                    counter.dataset
                                        .target,
                                    10
                                );

                            const suffix =
                                counter.dataset
                                    .suffix || "";

                            if (isNaN(target)) {
                                observer.unobserve(
                                    counter
                                );

                                return;
                            }

                            let currentValue = 0;
                            const duration = 2000;

                            const increment =
                                target /
                                (duration / 16);

                            function updateCounter() {
                                currentValue +=
                                    increment;

                                if (
                                    currentValue <
                                    target
                                ) {
                                    let value;

                                    if (
                                        target >= 1000
                                    ) {
                                        value =
                                            (
                                                currentValue /
                                                1000
                                            ).toFixed(1) +
                                            "K";
                                    } else {
                                        value =
                                            Math.floor(
                                                currentValue
                                            );
                                    }

                                    counter.textContent =
                                        value + suffix;

                                    requestAnimationFrame(
                                        updateCounter
                                    );
                                } else {
                                    const finalValue =
                                        target >= 1000
                                            ? (
                                                target /
                                                1000
                                            ).toFixed(
                                                1
                                            ) + "K"
                                            : target;

                                    counter.textContent =
                                        finalValue +
                                        suffix;
                                }
                            }

                            updateCounter();

                            observer.unobserve(
                                counter
                            );
                        }
                    );
                },
                {
                    threshold: 0.5
                }
            );

        statNumbers.forEach(
            function (counter) {
                counterObserver.observe(
                    counter
                );
            }
        );
    }


    /* ================= SEE MORE SERVICES ================= */

    const seeMoreServices =
        document.getElementById(
            "seeMoreServices"
        );

    if (seeMoreServices) {
        seeMoreServices.addEventListener(
            "click",
            function () {
                const moreServices =
                    document.querySelectorAll(
                        ".more-service"
                    );

                const isShowing =
                    this.dataset.expanded ===
                    "true";

                moreServices.forEach(
                    function (service) {
                        service.style.display =
                            isShowing
                                ? "none"
                                : "block";
                    }
                );

                this.dataset.expanded =
                    isShowing
                        ? "false"
                        : "true";

                this.innerHTML = isShowing
                    ? `See More Services
                       <i class="bi bi-arrow-down"></i>`
                    : `Show Less Services
                       <i class="bi bi-arrow-up"></i>`;
            }
        );
    }


    /* ================= CONNECTOR ANIMATION ================= */

    const connectorSection =
        document.querySelector(".ody-flow");

    const connectorPath =
        document.querySelector(
            ".ody-connector path"
        );

    if (
        connectorSection &&
        connectorPath &&
        "IntersectionObserver" in window
    ) {
        const pathLength =
            connectorPath.getTotalLength();

        connectorPath.style.strokeDasharray =
            pathLength;

        connectorPath.style.strokeDashoffset =
            pathLength;

        const connectorObserver =
            new IntersectionObserver(
                function (entries, observer) {
                    entries.forEach(
                        function (entry) {
                            if (
                                entry.isIntersecting
                            ) {
                                connectorPath.style.transition =
                                    "stroke-dashoffset 4s linear";

                                connectorPath.style.strokeDashoffset =
                                    "0";

                                observer.unobserve(
                                    connectorSection
                                );
                            }
                        }
                    );
                },
                {
                    threshold: 0.1
                }
            );

        connectorObserver.observe(
            connectorSection
        );
    }
});









/* =====================================================
   TRANSPORT JAVASCRIPT
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    /* ===== SCROLL ANIMATIONS ===== */

    const amcFadeElements =
        document.querySelectorAll(".amc-fadeup");

    if ("IntersectionObserver" in window) {
        const amcObserver =
            new IntersectionObserver(
                function (entries) {
                    entries.forEach(
                        function (entry) {
                            if (
                                entry.isIntersecting
                            ) {
                                entry.target.classList.add(
                                    "visible"
                                );

                                amcObserver.unobserve(
                                    entry.target
                                );
                            }
                        }
                    );
                },
                {
                    threshold: 0.1,
                    rootMargin:
                        "0px 0px -50px 0px"
                }
            );

        amcFadeElements.forEach(function (element) {
            amcObserver.observe(element);
        });
    } else {
        amcFadeElements.forEach(function (element) {
            element.classList.add("visible");
        });
    }


    /* ===== VEHICLE CARD ENTRANCE ===== */

    const amcVehicleItems =
        document.querySelectorAll(".amc-vehicleitem");

    amcVehicleItems.forEach(
        function (vehicle, index) {
            vehicle.style.opacity = "0";
            vehicle.style.transform =
                "translateY(30px)";

            vehicle.style.transition =
                "all 0.5s ease";

            setTimeout(function () {
                vehicle.style.opacity = "1";
                vehicle.style.transform =
                    "translateY(0)";
            }, index * 150);
        }
    );

});









/* =====================================================
   HOTEL JAVASCRIPT
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    /* ===== SCROLL ANIMATIONS ===== */

    const nestFadeElements =
        document.querySelectorAll(
            ".nest-fadeup"
        );

    if ("IntersectionObserver" in window) {
        const nestObserver =
            new IntersectionObserver(
                function (entries) {
                    entries.forEach(
                        function (entry) {
                            if (
                                entry.isIntersecting
                            ) {
                                entry.target.classList.add(
                                    "visible"
                                );

                                nestObserver.unobserve(
                                    entry.target
                                );
                            }
                        }
                    );
                },
                {
                    threshold: 0.1,
                    rootMargin:
                        "0px 0px -50px 0px"
                }
            );

        nestFadeElements.forEach(
            function (element) {
                nestObserver.observe(element);
            }
        );
    } else {
        nestFadeElements.forEach(
            function (element) {
                element.classList.add(
                    "visible"
                );
            }
        );
    }


    /* ===== HOTEL CARD ENTRANCE ===== */

    const nestPropertyItems =
        document.querySelectorAll(".nest-propertyitem");

    nestPropertyItems.forEach(
        function (property, index) {
            property.style.opacity = "0";

            property.style.transform =
                "translateY(30px)";

            property.style.transition =
                "all 0.5s ease";

            setTimeout(function () {
                property.style.opacity = "1";

                property.style.transform =
                    "translateY(0)";
            }, index * 150);
        }
    );

});









/* =====================================================
   BOOKING JAVASCRIPT
===================================================== */

/*
    JavaScript only controls the interface.
    Flight, hotel, car and tour data remain in HTML/Blade
    so they can later come from the Laravel backend.
*/


//    Tour Details Js 


document.addEventListener("DOMContentLoaded", function () {
    // Only the tour detail page carries this button, and this file is loaded by
    // every page on the site, so it is looked up rather than assumed.
    const backTopButton = document.getElementById("tripBackTop");

    if (!backTopButton) {
        return;
    }

    function updateBackTopButton() {
        backTopButton.classList.toggle("show", window.scrollY > 350);
    }

    window.addEventListener("scroll", updateBackTopButton, { passive: true });

    backTopButton.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    updateBackTopButton();
});


//  End  Tour Details Js 










/* Testimonials JS */


document.addEventListener('DOMContentLoaded', function () {
    const slides = [...document.querySelectorAll('.jsp-slider-item')];
    const dots = [...document.querySelectorAll('.jsp-dot')];
    let current = 0;
    let timer;

    if (!slides.length) return;

    function showSlide(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('jsp-active', i === current));
        dots.forEach((dot, i) => dot.classList.toggle('jsp-active', i === current));
    }

    function startAutoPlay() {
        clearInterval(timer);
        timer = setInterval(() => showSlide(current + 1), 5500);
    }

    const prevButton = document.getElementById('jspPrev');
    const nextButton = document.getElementById('jspNext');

    if (prevButton) {
        prevButton.addEventListener('click', () => { showSlide(current - 1); startAutoPlay(); });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => { showSlide(current + 1); startAutoPlay(); });
    }

    dots.forEach((dot, i) => dot.addEventListener('click', () => { showSlide(i); startAutoPlay(); }));
    startAutoPlay();
});

/*END Testimonials JS */










/* CAR DETAILS JS */




/* =====================================================
   IMAGE GALLERY
===================================================== */

const crdMainCarImage =
    document.getElementById("crd-main-car-image");

const crdThumbnails =
    document.querySelectorAll(".crd-thumbnail");


crdThumbnails.forEach(function (crdThumbnail) {

    crdThumbnail.addEventListener("click", function () {

        const crdImage =
            crdThumbnail.getAttribute("data-crd-image");


        if (!crdMainCarImage || !crdImage) {
            return;
        }


        crdMainCarImage.style.opacity = "0";


        setTimeout(function () {

            crdMainCarImage.src = crdImage;

            crdMainCarImage.style.opacity = "1";

        }, 180);


        crdThumbnails.forEach(function (thumbnail) {

            thumbnail.classList.remove(
                "crd-thumbnail-active"
            );

        });


        crdThumbnail.classList.add(
            "crd-thumbnail-active"
        );

    });

});


/* =====================================================
   RENTAL CALCULATION
===================================================== */

const crdPickupDate =
    document.getElementById("crd-pickup-date");

const crdReturnDate =
    document.getElementById("crd-return-date");

const crdRentalDays =
    document.getElementById("crd-rental-days");

const crdRentalSubtotal =
    document.getElementById("crd-rental-subtotal");

const crdRentalTotal =
    document.getElementById("crd-rental-total");


const crdPricePerDay = 8500;

const crdServiceFee = 500;


/* =====================================================
   PRICE FUNCTION
===================================================== */

function crdCalculateRentalPrice() {

    if (
        !crdPickupDate ||
        !crdReturnDate ||
        !crdPickupDate.value ||
        !crdReturnDate.value
    ) {
        return;
    }


    const crdPickup =
        new Date(crdPickupDate.value + "T00:00:00");

    const crdReturn =
        new Date(crdReturnDate.value + "T00:00:00");


    const crdDifference =
        crdReturn.getTime() -
        crdPickup.getTime();


    let crdDays =
        Math.ceil(
            crdDifference /
            (1000 * 60 * 60 * 24)
        );


    if (crdDays < 1) {

        crdDays = 1;

    }


    const crdSubtotal =
        crdDays * crdPricePerDay;


    const crdTotal =
        crdSubtotal + crdServiceFee;


    crdRentalDays.textContent =
        crdDays;


    crdRentalSubtotal.textContent =
        "Rs. " +
        crdSubtotal.toLocaleString();


    crdRentalTotal.textContent =
        "Rs. " +
        crdTotal.toLocaleString();

}


/* =====================================================
   DATE EVENTS
===================================================== */

if (crdPickupDate) {

    crdPickupDate.addEventListener(
        "change",
        crdCalculateRentalPrice
    );

}


if (crdReturnDate) {

    crdReturnDate.addEventListener(
        "change",
        crdCalculateRentalPrice
    );

}


/* =====================================================
   PREVENT DEMO FORM REFRESH
===================================================== */

const crdBookingForm =
    document.getElementById("crd-booking-form");


if (crdBookingForm) {

    crdBookingForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();

        }
    );

}



/*END CAR DETAILS JS */










/*HOTEL DETAILS JS */


        const jspHotelDetailMainImage =
            document.getElementById("jsp-hotel-detail-main-image");

        const jspHotelDetailThumbnails =
            document.querySelectorAll(".jsp-hotel-detail-thumbnail");


        jspHotelDetailThumbnails.forEach(function (thumbnail) {

            thumbnail.addEventListener("click", function () {

                const newImage =
                    thumbnail.getAttribute("data-jsp-hotel-image");

                if (!jspHotelDetailMainImage || !newImage) {
                    return;
                }

                jspHotelDetailMainImage.style.opacity = "0";

                setTimeout(function () {
                    jspHotelDetailMainImage.src = newImage;
                    jspHotelDetailMainImage.style.opacity = "1";
                }, 180);

                jspHotelDetailThumbnails.forEach(function (item) {
                    item.classList.remove(
                        "jsp-hotel-detail-thumbnail-active"
                    );
                });

                thumbnail.classList.add(
                    "jsp-hotel-detail-thumbnail-active"
                );
            });
        });


        const jspHotelDetailForm =
            document.getElementById("jsp-hotel-detail-booking-form");

        if (jspHotelDetailForm) {
            jspHotelDetailForm.addEventListener("submit", function (event) {
                event.preventDefault();
            });
        }
    



/*END HOTEL DETAILS JS */



/* ===== BOOKING SEARCH + CONFIRMATION ===== */
/* Two pages share one set of controls and one set of rules:

   - the search page (/book) holds one form per booking type and filters the
     database server-side;
   - the confirmation page (/bookings/create) holds exactly one service and the
     details bookings.store validates.

   Neither page decides a price or an availability answer: both ask the server,
   and bookings.store validates everything again before writing anything. */
(function () {
    "use strict";

    const searchForms = Array.prototype.slice.call(document.querySelectorAll(".fh-search-form"));
    const dateInputs = Array.prototype.slice.call(document.querySelectorAll(".fh-date-input"));
    const confirmForm = document.getElementById("bookingForm");

    if (searchForms.length === 0 && dateInputs.length === 0 && !confirmForm) {
        return;
    }

    const byId = (id) => document.getElementById(id);

    const setText = (id, value) => {
        const node = byId(id);

        if (node) {
            node.textContent = value;
        }
    };

    const showRow = (id, visible) => {
        const node = byId(id);

        if (node) {
            node.classList.toggle("d-none", !visible);
        }
    };

    const formatMoney = (value, currency) => {
        const amount = Number(value || 0);

        try {
            return new Intl.NumberFormat(undefined, {
                style: "currency",
                currency: currency || "NPR",
                maximumFractionDigits: 2,
            }).format(amount);
        } catch (error) {
            return amount.toFixed(2);
        }
    };

    const shiftIsoDate = (isoDate, days) => {
        if (!isoDate) {
            return "";
        }

        const parts = String(isoDate).split("-");

        if (parts.length !== 3) {
            return isoDate;
        }

        // UTC throughout: a local-time Date would shift the day either way for
        // anyone east or west of UTC.
        const shifted = new Date(
            Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
        );

        shifted.setUTCDate(shifted.getUTCDate() + days);

        return shifted.toISOString().slice(0, 10);
    };

    /* The floor is the day *after* the start date, not the start date itself,
       because that is what BookingRulesService enforces server-side for a stay,
       a rental and a tour alike. A picker offering the start date would let the
       customer choose something the server is guaranteed to reject. */
    const dayAfter = (isoDate) => (isoDate ? shiftIsoDate(isoDate, 1) : "");

    const daysBetween = (startIso, endIso) => {
        const start = Date.parse(startIso + "T00:00:00Z");
        const end = Date.parse(endIso + "T00:00:00Z");

        if (Number.isNaN(start) || Number.isNaN(end) || end <= start) {
            return 0;
        }

        return Math.round((end - start) / 86400000);
    };

    const setPeriodError = (scope, name, message) => {
        const node = scope.querySelector('[data-period-error="' + name + '"]');

        if (!node) {
            return;
        }

        node.textContent = message;
        node.classList.toggle("is-visible", Boolean(message));
    };

    /* Keep both dates freely editable. Raising the check-in moves the floor for
       the check-out, but an end date that is now behind the floor is kept rather
       than wiped: clearing it loses an answer the customer did not mean to lose
       and reads as a field stuck on the check-in. The conflict is reported next
       to the field instead, and the search or submission is blocked until it is
       resolved.

       Checking and publishing are separate. Checking is maintenance - the floor
       has to be right whether or not anyone is looking - so it always runs.
       Publishing is a claim about the customer, and it is only true once they have
       actually been asked, so a page that has not asked stays silent. That is what
       keeps an untouched form from opening by telling the customer they left
       something empty.

       Returns true when the pair is usable. */
    function validatePeriod(scope, startInput, endInput, endDateRequired, options) {
        const settings = options || {};
        const floor = startInput && startInput.value ? dayAfter(startInput.value) : "";
        const todayFloor = endInput.dataset.todayFloor || "";

        /* todayFloor is the min the server rendered, captured before any of this
           ran, so raising the floor for a picked start date can never become the
           only remembered minimum. It comes from the markup rather than from
           today() in here, so the browser cannot disagree with the server about
           which day is today. */
        endInput.min = floor && floor > todayFloor ? floor : todayFloor;

        const startError = startInput && startInput.value && startInput.value < todayFloor
            ? "This date has already passed."
            : (!startInput || !startInput.value
                ? (settings.missingStart || "A start date is required.")
                : "");

        const endError = endInput.value && floor && endInput.value < floor
            ? (settings.orderError || "")
            : (endDateRequired && !endInput.value
                ? (settings.missingEnd || "An end date is required.")
                : "");

        if (settings.publish !== false) {
            setPeriodError(scope, "start_date", startError);
            setPeriodError(scope, "end_date", endError);
        }

        return !startError && !endError;
    }

    /* "Any duration" imposes no restriction. Choosing a length is a shortcut for
       the drop-off date it describes, and the control then follows the dates, so
       the two can never quietly disagree. A length that is not one of the offered
       presets is simply "any duration": nothing is restricting it. */
    function syncRentalDuration(scope) {
        const select = scope.querySelector("[data-rental-duration]");
        const startInput = scope.querySelector("[data-period-start]");
        const endInput = scope.querySelector("[data-period-end]");

        if (!select || !startInput || !endInput) {
            return;
        }

        const days = daysBetween(startInput.value, endInput.value);

        if (!days) {
            select.value = "";
            return;
        }

        const offered = Array.prototype.some.call(
            select.options,
            (option) => option.value === String(days)
        );

        select.value = offered ? String(days) : "";
    }

    function applyRentalDuration(scope) {
        const select = scope.querySelector("[data-rental-duration]");
        const startInput = scope.querySelector("[data-period-start]");
        const endInput = scope.querySelector("[data-period-end]");

        if (!select || !select.value || !startInput || !startInput.value || !endInput) {
            return;
        }

        endInput.value = shiftIsoDate(startInput.value, Number(select.value));

        announceDateChange(endInput);
    }

    /* The party size is a real, editable field in every scope. It is clamped to
       what the control itself allows rather than pinned to a default, so a
       four-seater car is never offered to a party of six. */
    function syncPartyInput(scope) {
        const input = scope.querySelector("[data-party-input]");

        if (!input) {
            return null;
        }

        const min = Number(input.min || 1);
        const max = Number(input.max || min);
        const value = Number(input.value);

        if (!Number.isFinite(value) || value < min) {
            input.value = min;
        } else if (max >= min && value > max) {
            input.value = max;
        }

        return input;
    }

    /* The date cards.

       A date field is presented the way a traveller reads one - a small uppercase
       label over a formatted day, "Fri, 22 Mar" - while the control itself stays
       a real <input type="date"> stretched invisibly across the whole card.
       Nothing here replaces the browser calendar: a click anywhere on the card
       focuses that real input and asks it to show its own picker, and the ISO
       value it holds is what the search, the quote and the booking all carry on.
       Only the printed day is ours; the date itself never is. */
    const DATE_DISPLAY_PLACEHOLDER = "Select date";

    function formatDateForDisplay(isoDate) {
        const parts = String(isoDate || "").split("-");

        if (parts.length !== 3) {
            return "";
        }

        // UTC for the same reason shiftIsoDate is: a local-time Date would print
        // the wrong day, or the wrong weekday, either side of UTC.
        const parsed = new Date(Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])));

        if (Number.isNaN(parsed.getTime())) {
            return "";
        }

        try {
            const printed = new Intl.DateTimeFormat("en-GB", {
                weekday: "short",
                day: "2-digit",
                month: "short",
                timeZone: "UTC",
            }).formatToParts(parsed);

            const piece = (type) => {
                const found = printed.find((part) => part.type === type);

                return found ? found.value : "";
            };

            // Joined here rather than left to the locale, because a locale decides
            // its own separator and not every one of them puts a comma there. The
            // server renders the same "Fri, 22 Mar", and a first paint that says
            // something else would be a visible jump.
            return [piece("weekday"), piece("day"), piece("month")].filter(Boolean).join(", ");
        } catch (error) {
            return "";
        }
    }

    /* The browser's own calendar, opened by the input itself. Focus comes first
       because a click that does not focus never counts as the user gesture
       showPicker() needs, and a browser that refuses the call leaves the focused
       input to open it by itself. A failure here must never break the field. */
    function openNativeDatePicker(input) {
        input.focus();

        if (typeof input.showPicker !== "function") {
            return;
        }

        try {
            input.showPicker();
        } catch (error) {
            return;
        }
    }

    function enhanceDateCard(input) {
        const card = input.closest("[data-date-field]");
        const display = card ? card.querySelector("[data-date-display]") : null;

        if (!display) {
            return;
        }

        const paint = () => {
            const formatted = formatDateForDisplay(input.value);

            display.textContent = formatted || display.dataset.datePlaceholder || DATE_DISPLAY_PLACEHOLDER;
            card.classList.toggle("is-empty", !formatted);
        };

        // "input" so the day changes the moment the calendar commits it, "change"
        // so a value arrived any other way still repaints.
        input.addEventListener("input", paint);
        input.addEventListener("change", paint);

        /* The invisible input already sits over the whole card, so a click almost
           always lands on it directly. This covers what does not: the label, and
           a browser that will not open the picker from a click on the input. */
        card.addEventListener("click", function () {
            openNativeDatePicker(input);
        });

        paint();
    }

    /* A date written by the rental-length shortcut was never picked, and a value
       assigned in script fires no event of its own. Announcing it repaints the card
       and re-checks the period exactly as if the customer had chosen the day. */
    function announceDateChange(input) {
        if (!input) {
            return;
        }

        input.dispatchEvent(new Event("change", { bubbles: true }));
    }

    /* The booking summary popup on the search page.

       Wired here, ahead of the confirmation page's own block, because that block
       is about #bookingForm and the search page - where this popup lives - has no
       booking form at all. Nothing is lost by saying so in one place: the two
       pages share the date cards and the helpers above, and each takes only the
       wiring it has the markup for.

       Every number and date it shows comes from the server's own summary of the
       result that was clicked, carried in the page's JSON payload. Nothing is
       recalculated here, and bookings.store re-validates all of it before
       anything is written. */
    const summaryModal = document.getElementById("fh-bookingSummaryModal");
    const summaryForm = document.getElementById("fh-bookingSummaryForm");
    const summaryPayload = document.getElementById("fh-bookingSummaries");

    if (summaryModal && summaryForm && summaryPayload) {
        let summaries = {};

        try {
            summaries = JSON.parse(summaryPayload.textContent) || {};
        } catch (error) {
            summaries = {};
        }

        const summaryPart = (name) => summaryModal.querySelector("[data-summary-" + name + "]");
        const summaryRows = summaryPart("rows");
        const summarySubmit = summaryPart("submit");
        const summarySubmitLabel = summaryPart("submit-label");
        const summaryNotice = summaryPart("notice");

        const writeSummaryField = (name, value) => {
            const field = summaryForm.querySelector('[data-summary-field="' + name + '"]');

            if (field) {
                field.value = value === null || value === undefined ? "" : String(value);
            }
        };

        const renderSummaryRows = (rows) => {
            summaryRows.textContent = "";

            (rows || []).forEach(function (row) {
                const line = document.createElement("div");
                line.className = "fh-quote-row";

                const label = document.createElement("span");
                label.textContent = row.label;

                const value = document.createElement("span");
                value.textContent = row.value;

                line.appendChild(label);
                line.appendChild(value);
                summaryRows.appendChild(line);
            });
        };

        summaryModal.addEventListener("show.bs.modal", function (event) {
            const trigger = event.relatedTarget;
            const summary = trigger ? summaries[trigger.getAttribute("data-booking-summary")] : null;

            /* Without a summary there is nothing to confirm, so the result's own
               link is the way through. The show is cancelled rather than closed
               afterwards: this event fires while the popup is still being opened,
               and Bootstrap cannot hide a modal that has not finished being shown,
               so a late close left the empty popup on screen until the next page had
               finished loading. Cancelling stops it being drawn at all, and the
               navigation below is ours to make because the modal trigger already
               cancelled the link. */
            if (!summary) {
                event.preventDefault();

                if (trigger && trigger.getAttribute("href")) {
                    window.location.assign(trigger.href);
                }

                return;
            }

            summaryPart("heading").textContent = summary.popupTitle;
            summaryPart("service").textContent = summary.serviceTitle;

            renderSummaryRows(summary.rows);

            summaryPart("unit").textContent = summary.unit;
            summaryPart("subtotal").textContent = summary.subtotal;
            summaryPart("note").textContent = summary.note;
            summaryPart("discount-label").textContent = summary.discountLabel || "Discount";
            summaryPart("discount").textContent = "\u2212" + (summary.discount || "");
            summaryPart("tax-label").textContent = summary.taxLabel;
            summaryPart("tax").textContent = summary.tax;
            summaryPart("charge-label").textContent = summary.chargeLabel;
            summaryPart("charge").textContent = summary.charge;
            summaryPart("total").textContent = summary.total;

            summaryPart("discount-row").classList.toggle("d-none", !summary.hasDiscount);
            summaryPart("tax-row").classList.toggle("d-none", !summary.hasTax);
            summaryPart("charge-row").classList.toggle("d-none", !summary.hasCharge);

            writeSummaryField("bookingType", summary.bookingType);
            writeSummaryField("serviceId", summary.serviceId);
            writeSummaryField("startDate", summary.startDate);
            writeSummaryField("endDate", summary.endDate);
            writeSummaryField("travelers", summary.travelers);
            writeSummaryField("submissionToken", summary.submissionToken);

            summaryNotice.classList.toggle("d-none", summary.available);
            summaryNotice.textContent = summary.available ? "" : summary.message;

            summarySubmit.disabled = !summary.available;
            summarySubmitLabel.textContent = summary.available ? summarySubmitLabel.dataset.defaultLabel : "Unavailable";
        });

        summaryForm.addEventListener("submit", function () {
            // A second click cannot reach the server: the button is disabled on
            // the first one, and the submission token makes any replay resolve back to
            // the booking that token already created.
            summarySubmit.disabled = true;
            summarySubmitLabel.textContent = "Processing...";
        });

        summaryModal.addEventListener("hidden.bs.modal", function () {
            summarySubmit.disabled = false;
            summarySubmitLabel.textContent = summarySubmitLabel.dataset.defaultLabel;
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        // Before anything reads a date, so the cards are already telling the truth.
        dateInputs.forEach(enhanceDateCard);

        searchForms.forEach(function (form) {
            const startInput = form.querySelector("[data-period-start]");
            const endInput = form.querySelector("[data-period-end]");
            const panel = form.closest("[data-booking-type]");
            const endDateRequired = panel ? panel.getAttribute("data-booking-type") !== "tour" : true;

            /* The wording the server would answer a period with, named by the
               tab, so nothing here has to phrase the rule a second way. */
            const orderError = panel ? (panel.getAttribute("data-end-date-order-error") || "") : "";

            if (endInput) {
                endInput.dataset.todayFloor = endInput.min || "";
            }

            const revalidate = function (publish) {
                if (startInput && endInput) {
                    validatePeriod(form, startInput, endInput, endDateRequired, {
                        publish: publish,
                        missingStart: "A start date is required.",
                        missingEnd: "An end date is required.",
                        orderError: orderError,
                    });
                }

                syncRentalDuration(form);
                syncPartyInput(form);
            };

            /* The opening pass keeps the cards honest - the floor still has to be
               right before anyone reads a day - but reports nothing. An empty search
               form has not been answered wrongly, it has not been answered yet, and
               greeting a first-time visitor with a required-date error describes the
               page rather than anything they did. */
            revalidate(false);

            [startInput, endInput].forEach(function (input) {
                if (!input) {
                    return;
                }

                input.addEventListener("change", function () {
                    // The rental length describes the pair, so recomputing it last
                    // means picking a duration then a pick-up date always agrees.
                    if (input === startInput) {
                        syncRentalDuration(form);
                    }

                    revalidate(true);
                });
            });

            const durationSelect = form.querySelector("[data-rental-duration]");

            if (durationSelect) {
                durationSelect.addEventListener("change", function () {
                    applyRentalDuration(form);
                    revalidate(true);
                });
            }

            const partyInput = form.querySelector("[data-party-input]");

            if (partyInput) {
                partyInput.addEventListener("change", function () {
                    syncPartyInput(form);
                });
            }

            /* An unusable period is stopped here rather than round-tripped to a
               server error, but the server still validates it: this only saves a
               pointless request. Submitting is the customer asking, so this is
               where they are told. */
            form.addEventListener("submit", function (event) {
                if (!startInput || !endInput) {
                    return;
                }

                if (!validatePeriod(form, startInput, endInput, endDateRequired, {
                    missingStart: "A start date is required.",
                    missingEnd: "An end date is required.",
                    orderError: orderError,
                })) {
                    event.preventDefault();
                }
            });
        });

        /* The confirmation page from here down: its form, its live quote and its
           review popup. The search page shares the date cards and the popup above
           but has none of these, so this is where the script stops for it. */
        if (!confirmForm) {
            return;
        }

        const typeInput = byId("bookingType");
        const serviceInput = byId("serviceId");
        const submitButton = byId("bookingSubmit");
        const reviewButton = byId("reviewBookingButton");
        const policyCheckbox = byId("policy_accepted");
        const confirmModal = byId("fh-confirmBookingModal");
        const blocker = byId("fh-confirmBlocker");
        const visibleStart = confirmForm.querySelector("[data-period-start]");
        const visibleEnd = confirmForm.querySelector("[data-period-end]");
        const partyInput = confirmForm.querySelector("[data-party-input]");
        const rentalDuration = confirmForm.querySelector("[data-rental-duration]");
        const endDateRequired = typeInput ? typeInput.value !== "tour" : true;

        /* Whether the last quote the server gave us described a bookingable
           combination of dates and party size. Both the Review button and Confirm
           Booking are gated on it, so the two can never disagree about whether this
           booking can go ahead. */
        let quoteReady = false;

        /* Whether this period has been asked about yet.

           The server answers it for a page that arrived with dates or with errors
           from a failed submit; from here on, touching a date control counts. It
           is the line between "waiting to be asked" and "asked and unanswered",
           and therefore the only thing that decides whether a missing date is a
           fault worth reporting or simply the next question. */
        let periodAttempted = confirmForm.getAttribute("data-dates-attempted") === "true";

        /* Named per booking type by the server, so the script asks a stay for a
           check-in and a package for a departure without hardcoding either. */
        const missingDates = {
            start: confirmForm.getAttribute("data-missing-start") || "",
            end: confirmForm.getAttribute("data-missing-end") || "",
        };

        /* The same reasoning for the end-date rule: the page is told what the
           server calls it, so a conflict on the page is worded exactly as the
           submission that follows would have worded it. */
        const orderError = confirmForm.getAttribute("data-end-date-order-error") || "";

        if (visibleEnd) {
            visibleEnd.dataset.todayFloor = visibleEnd.min || "";
        }

        const scope = {
            querySelector: (selector) => confirmForm.querySelector(selector),
        };

        function mirrorPeriod() {
            const canonicalStart = byId("start_date");
            const canonicalEnd = byId("end_date");

            if (canonicalStart && visibleStart) {
                canonicalStart.value = visibleStart.value;
            }

            if (canonicalEnd && visibleEnd) {
                canonicalEnd.value = visibleEnd.value;
            }

            // The popup reads the dates back to the customer, so the same mirror
            // that feeds the server also feeds the review.
            if (visibleStart) {
                setText("fh-periodStart", formatDateForDisplay(visibleStart.value) || DATE_DISPLAY_PLACEHOLDER);
            }

            if (visibleEnd) {
                setText("fh-periodEnd", formatDateForDisplay(visibleEnd.value) || DATE_DISPLAY_PLACEHOLDER);
            }
        }

        function mirrorParty() {
            const canonical = byId("travelers");
            const input = syncPartyInput(scope);

            if (canonical && input) {
                canonical.value = input.value;
            }

            if (input) {
                setText("fh-periodParty", input.value);
            }
        }

        function clearQuote(message) {
            setText("fh-quoteNote", message);
            setText("fh-quoteUnit", "");
            setText("fh-quoteSubtotal", "");
            setText("fh-quoteTotal", "");
            ["fh-quoteDiscountRow", "fh-quoteTaxRow", "fh-quoteChargeRow", "fh-quoteTotalRow"].forEach((id) => showRow(id, false));
            setBlocker(message);
        }

        function renderQuote(quote) {
            const currency = quote.currency;
            const unit = formatMoney(quote.unit_price, currency);
            const priced = Number(quote.quantity) > 0;

            setText("fh-quoteUnit", priced ? unit + " " + (quote.unit_label || "") : "");
            setText("fh-quoteSubtotal", priced ? formatMoney(quote.subtotal, currency) : "");
            setText("fh-quoteNote", quote.recalc_note || "");
            setText(
                "fh-quoteDiscountLabel",
                quote.discount_description
                    ? "Discount (" + quote.discount_description + ")"
                    : "Discount"
            );
            setText("fh-quoteDiscount", "\u2212" + formatMoney(quote.discount, currency));
            setText("fh-quoteTaxLabel", "Tax (" + Number(quote.tax_rate).toFixed(2) + "%)");
            setText("fh-quoteTax", formatMoney(quote.tax_amount, currency));
            setText(
                "fh-quoteChargeLabel",
                "Service charge (" + Number(quote.service_charge_rate).toFixed(2) + "%)"
            );
            setText("fh-quoteCharge", formatMoney(quote.service_charge, currency));
            setText("fh-quoteTotal", formatMoney(quote.total, currency));

            showRow("fh-quoteDiscountRow", !!quote.discount_applied);
            showRow("fh-quoteTaxRow", Number(quote.tax_rate) > 0);
            showRow("fh-quoteChargeRow", Number(quote.service_charge_rate) > 0);
            showRow("fh-quoteTotalRow", true);
            setBlocker("");
        }

        function setBlocker(message) {
            if (blocker) {
                blocker.textContent = message || "";
                blocker.classList.toggle("d-none", !message);
            }
        }

        /* The two gates on the booking, in one place: the dates and party size have
           to describe something bookable, and the policy has to be accepted. The
           Review button only needs the first, because opening the popup is how the
           customer reads the second. */
        function syncConfirmState() {
            /* Review is the question, so it stays pressable until it has been asked
               and refused. Gating it on a quote it cannot have yet would leave a
               form with no way to find out what it wants. */
            if (reviewButton) {
                reviewButton.disabled = periodAttempted && !quoteReady;
            }

            if (submitButton) {
                submitButton.disabled = !quoteReady || !(policyCheckbox && policyCheckbox.checked);
            }
        }

        function setSubmitState(enabled, message) {
            quoteReady = enabled;
            syncConfirmState();

            setText("bookingPeriodStatus", message || "");
        }

        let quoteTimer = null;

        async function runQuote() {
            if (!typeInput || !serviceInput) {
                return;
            }

            if (visibleStart && visibleEnd) {
                const periodUsable = validatePeriod(scope, visibleStart, visibleEnd, endDateRequired, {
                    publish: periodAttempted,
                    missingStart: missingDates.start,
                    missingEnd: missingDates.end,
                    orderError: orderError,
                });

                if (!periodUsable) {
                    mirrorPeriod();

                    /* Said only once the customer has actually been asked. Before
                       that the dates are simply not chosen yet, which is the normal
                       condition of a fresh form and not an answer to be corrected.
                       The reason is left off the button as well: the field already
                       says which date is the problem, and a second sentence about it
                       beside the button only repeats the field. */
                    if (periodAttempted) {
                        setSubmitState(false, "");
                        clearQuote("These dates cannot be booked yet.");
                    } else {
                        syncConfirmState();
                    }

                    return;
                }
            }

            mirrorPeriod();
            mirrorParty();

            const tokenField = confirmForm.querySelector('input[name="_token"]');
            const metaToken = document.querySelector('meta[name="csrf-token"]');

            try {
                const response = await fetch(confirmForm.getAttribute("data-quote-url"), {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": (metaToken && metaToken.content) || (tokenField && tokenField.value) || "",
                    },
                    body: JSON.stringify({
                        booking_type: typeInput.value,
                        service_id: serviceInput.value,
                        start_date: byId("start_date") ? byId("start_date").value : null,
                        end_date: byId("end_date") ? byId("end_date").value : null,
                        travelers: byId("travelers") ? byId("travelers").value : null,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.available || !data.quote) {
                    const reason =
                        data.reason ||
                        (data.errors ? Object.values(data.errors).flat()[0] : null) ||
                        "That service is not available for the selected dates.";

                    clearQuote(reason);
                    setSubmitState(false, reason);
                    return;
                }

                renderQuote(data.quote);
                setSubmitState(true, "");
            } catch (error) {
                clearQuote("We could not refresh the price just now. Please try again.");
                setSubmitState(false, "We could not confirm availability just now. Please try again.");
            }
        }

        function scheduleQuote() {
            window.clearTimeout(quoteTimer);
            quoteTimer = window.setTimeout(runQuote, 300);
        }

        [visibleStart, visibleEnd].forEach(function (input) {
            if (!input) {
                return;
            }

            input.addEventListener("change", function () {
                /* Reaching for a date is answering the question, so from here on the
                   period is one the customer is expected to have filled in. */
                periodAttempted = true;

                if (input === visibleStart) {
                    syncRentalDuration(scope);
                }

                scheduleQuote();
            });
        });

        if (rentalDuration) {
            rentalDuration.addEventListener("change", function () {
                periodAttempted = true;
                applyRentalDuration(scope);
                scheduleQuote();
            });
        }

        if (partyInput) {
            partyInput.addEventListener("change", scheduleQuote);
        }

        if (policyCheckbox) {
            policyCheckbox.addEventListener("change", syncConfirmState);
        }

        /* Opening the popup is the review, so the figures it shows must be the ones the
           server gives right now - not the ones left over from the last edit the
           customer made. Re-quoting on every open keeps the popup honest even when
           someone else took the last room in between. */
        if (confirmModal) {
            confirmModal.addEventListener("show.bs.modal", runQuote);

            /* A dismissed attempt re-arms Confirm Booking, because the popup
               closing is the customer changing their mind and the next open has to
               earn the button again from a fresh quote. Their typed details stay. */
            confirmModal.addEventListener("hidden.bs.modal", function () {
                if (submitButton) {
                    submitButton.disabled = true;
                }
            });
        }

        /* Review is the moment the period gets asked for, so the check belongs on
           the press rather than on a timer. Pressing it either opens the review of
           a period that holds up, or explains itself beside the date that does
           not - and either way the customer learns it by asking. */
        if (reviewButton) {
            reviewButton.addEventListener("click", function (event) {
                periodAttempted = true;

                if (!visibleStart || !visibleEnd) {
                    return;
                }

                const periodUsable = validatePeriod(scope, visibleStart, visibleEnd, endDateRequired, {
                    missingStart: missingDates.start,
                    missingEnd: missingDates.end,
                    orderError: orderError,
                });

                if (periodUsable) {
                    scheduleQuote();
                    return;
                }

                /* Refusing the default keeps the popup shut. A review of dates that
                   cannot be booked would be a review of nothing. */
                event.preventDefault();
                mirrorPeriod();
                setSubmitState(false, "");
                clearQuote("These dates cannot be booked yet.");

                /* Focus goes to the first date still missing an answer, so the next
                   thing they do is open the calendar that needs opening. */
                const firstUnanswered = !visibleStart.value
                    ? visibleStart
                    : (endDateRequired && !visibleEnd.value ? visibleEnd : null);

                if (firstUnanswered) {
                    firstUnanswered.focus();
                }
            });
        }

        confirmForm.addEventListener("submit", function (event) {
            if (visibleStart && visibleEnd && !validatePeriod(scope, visibleStart, visibleEnd, endDateRequired, {
                missingStart: missingDates.start,
                missingEnd: missingDates.end,
                orderError: orderError,
            })) {
                event.preventDefault();
            }
        });

        mirrorPeriod();
        mirrorParty();
        syncConfirmState();

        /* Nothing is quoted until there are dates to quote. Asking the server about
           a period the customer has not chosen cannot succeed, and the failure it
           answers with is exactly what used to greet them on arrival. */
        if (visibleStart && visibleStart.value) {
            scheduleQuote();
        }
    });
})();
