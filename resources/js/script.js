/* =========================================================
   MAIN WEBSITE JAVASCRIPT
========================================================= */

/* ACTIVE NAVIGATION - always follows the real page URL */
const navigationLinks = document.querySelectorAll(".nav-links a, .mobile-nav > a, .nav-cta");
function getFileName(value) {
    if (!value) return "index.html";
    try {
        const url = new URL(value, window.location.href);
        const fileName = decodeURIComponent(url.pathname.split("/").pop() || "index.html");
        return fileName.trim().replace(/\s+/g, " ").toLowerCase();
    } catch (error) { return "index.html"; }
}
function getActiveNavPage() {
    const currentPage = getFileName(window.location.href);
    const service = new URLSearchParams(window.location.search).get("service");
    if (currentPage === "hotel-detail.html") return "hotel.html";
    if (currentPage === "tour-detail.html") return "destinations.html";
    if (currentPage === "booking.html") {
        if (service === "hotel") return "hotel.html";
        if (service === "car") return "transport .html";
        if (service === "tour") return "destinations.html";
        return "booking.html";
    }
    return currentPage;
}
function updateActiveNavigation() {
    const activePage = getActiveNavPage();
    navigationLinks.forEach(function (link) {
        const isActive = getFileName(link.href) === activePage;
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

    /* ===== INVENTORY FILTER ===== */

    const amcFilterButtons =
        document.querySelectorAll("[data-amc-filter]");

    const amcVehicleItems =
        document.querySelectorAll(".amc-vehicleitem");

    amcFilterButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const filterValue =
                this.getAttribute("data-amc-filter");

            amcFilterButtons.forEach(function (item) {
                item.classList.remove("active");
            });

            this.classList.add("active");

            amcVehicleItems.forEach(function (vehicle) {
                const vehicleType =
                    vehicle.getAttribute("data-amc-type");

                if (
                    filterValue === "all" ||
                    vehicleType === filterValue
                ) {
                    vehicle.style.display = "block";

                    setTimeout(function () {
                        vehicle.style.opacity = "1";
                        vehicle.style.transform =
                            "translateY(0)";
                    }, 50);
                } else {
                    vehicle.style.opacity = "0";
                    vehicle.style.transform =
                        "translateY(20px)";

                    setTimeout(function () {
                        vehicle.style.display = "none";
                    }, 300);
                }
            });
        });
    });


    /* ===== SEARCH FILTER ===== */

    const amcSearchBtn =
        document.getElementById("amcSearchBtn");

    const amcFilterMake =
        document.getElementById("amcFilterMake");

    const amcFilterModel =
        document.getElementById("amcFilterModel");

    const amcFilterBody =
        document.getElementById("amcFilterBody");

    const amcFilterPrice =
        document.getElementById("amcFilterPrice");

    function amcApplySearch() {
        if (
            !amcFilterMake ||
            !amcFilterModel ||
            !amcFilterBody ||
            !amcFilterPrice
        ) {
            return;
        }

        const makeValue =
            amcFilterMake.value.toLowerCase();

        const modelValue =
            amcFilterModel.value.toLowerCase();

        const bodyValue =
            amcFilterBody.value.toLowerCase();

        const priceValue =
            parseInt(amcFilterPrice.value) ||
            Infinity;

        amcVehicleItems.forEach(function (vehicle) {
            const vehicleMake =
                (
                    vehicle.getAttribute(
                        "data-amc-make"
                    ) || ""
                ).toLowerCase();

            const vehicleModel =
                (
                    vehicle.getAttribute(
                        "data-amc-model"
                    ) || ""
                ).toLowerCase();

            const vehicleType =
                (
                    vehicle.getAttribute(
                        "data-amc-type"
                    ) || ""
                ).toLowerCase();

            const vehiclePrice =
                parseInt(
                    vehicle.getAttribute(
                        "data-amc-price"
                    )
                ) || 0;

            const makeMatches =
                !makeValue ||
                vehicleMake === makeValue;

            const modelMatches =
                !modelValue ||
                vehicleModel === modelValue;

            const bodyMatches =
                !bodyValue ||
                vehicleType === bodyValue;

            const priceMatches =
                vehiclePrice <= priceValue;

            if (
                makeMatches &&
                modelMatches &&
                bodyMatches &&
                priceMatches
            ) {
                vehicle.style.display = "block";

                setTimeout(function () {
                    vehicle.style.opacity = "1";
                    vehicle.style.transform =
                        "translateY(0)";
                }, 50);
            } else {
                vehicle.style.opacity = "0";
                vehicle.style.transform =
                    "translateY(20px)";

                setTimeout(function () {
                    vehicle.style.display = "none";
                }, 300);
            }
        });

        const inventorySection =
            document.getElementById(
                "amcInventory"
            );

        if (inventorySection) {
            inventorySection.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        }
    }

    if (amcSearchBtn) {
        amcSearchBtn.addEventListener(
            "click",
            amcApplySearch
        );
    }

    [
        amcFilterMake,
        amcFilterModel,
        amcFilterBody,
        amcFilterPrice
    ].forEach(function (select) {
        if (select) {
            select.addEventListener(
                "change",
                amcApplySearch
            );
        }
    });


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

    /* ===== HOTEL SEARCH FILTER ===== */

    const nestSearchBtn =
        document.getElementById("nestSearchBtn");

    const nestFilterLoc =
        document.getElementById("nestFilterLoc");

    const nestFilterType =
        document.getElementById("nestFilterType");

    const nestFilterPrice =
        document.getElementById("nestFilterPrice");

    const nestPropertyItems =
        document.querySelectorAll(
            ".nest-propertyitem"
        );

    function nestApplySearch() {
        if (
            !nestFilterLoc ||
            !nestFilterType ||
            !nestFilterPrice
        ) {
            return;
        }

        const locationValue =
            nestFilterLoc.value.toLowerCase();

        const typeValue =
            nestFilterType.value.toLowerCase();

        const priceValue =
            parseInt(nestFilterPrice.value) ||
            Infinity;

        nestPropertyItems.forEach(
            function (property) {
                const propertyLocation =
                    (
                        property.getAttribute(
                            "data-nest-loc"
                        ) || ""
                    ).toLowerCase();

                const propertyType =
                    (
                        property.getAttribute(
                            "data-nest-type"
                        ) || ""
                    ).toLowerCase();

                const propertyPrice =
                    parseInt(
                        property.getAttribute(
                            "data-nest-price"
                        )
                    ) || 0;

                const locationMatches =
                    !locationValue ||
                    propertyLocation ===
                    locationValue;

                const typeMatches =
                    !typeValue ||
                    propertyType === typeValue;

                const priceMatches =
                    propertyPrice <= priceValue;

                if (
                    locationMatches &&
                    typeMatches &&
                    priceMatches
                ) {
                    property.style.display =
                        "block";

                    setTimeout(function () {
                        property.style.opacity =
                            "1";

                        property.style.transform =
                            "translateY(0)";
                    }, 50);
                } else {
                    property.style.opacity = "0";

                    property.style.transform =
                        "translateY(20px)";

                    setTimeout(function () {
                        property.style.display =
                            "none";
                    }, 300);
                }
            }
        );

        const propertySection =
            document.getElementById(
                "nestProperties"
            );

        if (propertySection) {
            propertySection.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        }
    }

    if (nestSearchBtn) {
        nestSearchBtn.addEventListener(
            "click",
            nestApplySearch
        );
    }

    [
        nestFilterLoc,
        nestFilterType,
        nestFilterPrice
    ].forEach(function (select) {
        if (select) {
            select.addEventListener(
                "change",
                nestApplySearch
            );
        }
    });


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


/* ===== BOOKING TAB SWITCHING ===== */
function activateBookingTab(tabName, updateUrl) {
    const allowedTabs = ["flight", "hotel", "car", "tour"];
    const selectedTabName = allowedTabs.includes(tabName) ? tabName : "flight";
    document.querySelectorAll(".fh-booking-tab").forEach(function (tab) {
        tab.classList.toggle("active", tab.dataset.tab === selectedTabName);
    });
    document.querySelectorAll(".fh-flight-form, .fh-hotel-form, .fh-car-form, .fh-tour-form").forEach(function (form) {
        form.classList.remove("active");
    });
    const selectedForm = document.getElementById("fh-" + selectedTabName + "Form");
    if (selectedForm) selectedForm.classList.add("active");
    if (updateUrl) {
        const url = new URL(window.location.href);
        url.searchParams.set("service", selectedTabName);
        window.history.replaceState({}, "", url);
        updateActiveNavigation();
    }
}
document.querySelectorAll(".fh-booking-tab").forEach(function (tab) {
    tab.addEventListener("click", function () { activateBookingTab(this.dataset.tab, true); });
});
if (document.querySelector(".fh-booking-tab")) {
    const requestedService = new URLSearchParams(window.location.search).get("service");
    activateBookingTab(requestedService || "flight", false);
}

/* ===== DROPDOWN CONTROL ===== */

function toggleDropdown(dropdownId) {
    const selectedDropdown =
        document.getElementById(dropdownId);

    if (!selectedDropdown) {
        return;
    }

    document
        .querySelectorAll(
            ".fh-passenger-dropdown"
        )
        .forEach(function (dropdown) {
            if (
                dropdown.id !== dropdownId
            ) {
                dropdown.classList.remove(
                    "show"
                );
            }
        });

    selectedDropdown.classList.toggle(
        "show"
    );
}

const bookingDropdowns = [
    [
        "fh-tripTypeBtn",
        "fh-tripDropdown"
    ],
    [
        "fh-passengerBtn",
        "fh-passengerDropdown"
    ],
    [
        "fh-classBtn",
        "fh-classDropdown"
    ],
    [
        "fh-hotelGuestsBtn",
        "fh-hotelGuestsDropdown"
    ],
    [
        "fh-carTypeBtn",
        "fh-carTypeDropdown"
    ],
    [
        "fh-tourTypeBtn",
        "fh-tourTypeDropdown"
    ]
];

bookingDropdowns.forEach(
    function (dropdownDetails) {
        const buttonId =
            dropdownDetails[0];

        const dropdownId =
            dropdownDetails[1];

        const button =
            document.getElementById(
                buttonId
            );

        if (button) {
            button.addEventListener(
                "click",
                function (event) {
                    event.stopPropagation();

                    toggleDropdown(
                        dropdownId
                    );
                }
            );
        }
    }
);

document.addEventListener(
    "click",
    function () {
        document
            .querySelectorAll(
                ".fh-passenger-dropdown"
            )
            .forEach(function (dropdown) {
                dropdown.classList.remove(
                    "show"
                );
            });
    }
);


/* ===== CHANGE DROPDOWN VALUE ===== */

function setDropdownValue(
    textId,
    dropdownId,
    value
) {
    const textElement =
        document.getElementById(textId);

    const dropdown =
        document.getElementById(
            dropdownId
        );

    if (textElement) {
        textElement.textContent = value;
    }

    if (dropdown) {
        dropdown.classList.remove(
            "show"
        );
    }
}

function setTripType(value) {
    setDropdownValue(
        "fh-tripTypeText",
        "fh-tripDropdown",
        value
    );
}

function setClass(value) {
    setDropdownValue(
        "fh-classText",
        "fh-classDropdown",
        value
    );
}

function setCarType(value) {
    setDropdownValue(
        "fh-carTypeText",
        "fh-carTypeDropdown",
        value
    );
}

function setTourType(value) {
    setDropdownValue(
        "fh-tourTypeText",
        "fh-tourTypeDropdown",
        value
    );
}


/* ===== FLIGHT PASSENGERS ===== */

let passengers = {
    adults: 2,
    children: 0,
    infants: 0
};

function updatePassenger(
    passengerType,
    change
) {
    if (
        !Object.prototype.hasOwnProperty.call(
            passengers,
            passengerType
        )
    ) {
        return;
    }

    const minimumValue =
        passengerType === "adults"
            ? 1
            : 0;

    passengers[passengerType] =
        Math.max(
            minimumValue,
            passengers[passengerType] +
            change
        );

    const countElement =
        document.getElementById(
            "fh-" +
            passengerType +
            "Count"
        );

    if (countElement) {
        countElement.textContent =
            passengers[passengerType];
    }

    const totalPassengers =
        passengers.adults +
        passengers.children +
        passengers.infants;

    const passengerText =
        document.getElementById(
            "fh-passengerText"
        );

    if (passengerText) {
        passengerText.textContent =
            String(totalPassengers).padStart(
                2,
                "0"
            ) + " Passengers";
    }
}


/* ===== HOTEL GUESTS AND ROOMS ===== */

let hotelGuests = 2;
let hotelRooms = 1;

function updateHotelSummary() {
    const guestCount =
        document.getElementById(
            "fh-hotelGuestCount"
        );

    const roomCount =
        document.getElementById(
            "fh-hotelRoomCount"
        );

    const summary =
        document.getElementById(
            "fh-hotelGuestsText"
        );

    if (guestCount) {
        guestCount.textContent =
            hotelGuests;
    }

    if (roomCount) {
        roomCount.textContent =
            hotelRooms;
    }

    if (summary) {
        summary.textContent =
            String(hotelGuests).padStart(
                2,
                "0"
            ) +
            " Guests, " +
            hotelRooms +
            " Room" +
            (hotelRooms > 1 ? "s" : "");
    }
}

function updateHotelGuests(change) {
    hotelGuests = Math.max(
        1,
        hotelGuests + change
    );

    updateHotelSummary();
}

function updateHotelRooms(change) {
    hotelRooms = Math.max(
        1,
        hotelRooms + change
    );

    updateHotelSummary();
}


/* ===== CLOSE BOOTSTRAP MODAL ===== */

function closeModal(modalId) {
    const modalElement =
        document.getElementById(modalId);

    if (
        !modalElement ||
        typeof bootstrap === "undefined"
    ) {
        return;
    }

    const modal =
        bootstrap.Modal.getInstance(
            modalElement
        );

    if (modal) {
        modal.hide();
    }
}


/* ===== LOCATION SELECTION ===== */

function updateLocation(
    cityElementId,
    detailElementId,
    city,
    detail,
    modalId
) {
    const cityElement =
        document.getElementById(
            cityElementId
        );

    const detailElement =
        document.getElementById(
            detailElementId
        );

    if (cityElement) {
        cityElement.textContent = city;
    }

    if (detailElement) {
        detailElement.textContent =
            detail;
    }

    closeModal(modalId);
}

function selectFrom(city, detail) {
    updateLocation(
        "fh-fromCity",
        "fh-fromDetail",
        city,
        detail,
        "fh-fromModal"
    );
}

function selectTo(city, detail) {
    updateLocation(
        "fh-toCity",
        "fh-toDetail",
        city,
        detail,
        "fh-toModal"
    );
}

function selectHotelDest(city, detail) {
    updateLocation(
        "fh-hotelCity",
        "fh-hotelDetail",
        city,
        detail,
        "fh-hotelDestinationModal"
    );
}

function selectPickup(city, detail) {
    updateLocation(
        "fh-pickupCity",
        "fh-pickupDetail",
        city,
        detail,
        "fh-pickupModal"
    );
}

function selectDropoff(city, detail) {
    updateLocation(
        "fh-dropoffCity",
        "fh-dropoffDetail",
        city,
        detail,
        "fh-dropoffModal"
    );
}


/* ===== SWAP FLIGHT LOCATIONS ===== */

function swapLocations() {
    const fromCity =
        document.getElementById(
            "fh-fromCity"
        );

    const fromDetail =
        document.getElementById(
            "fh-fromDetail"
        );

    const toCity =
        document.getElementById(
            "fh-toCity"
        );

    const toDetail =
        document.getElementById(
            "fh-toDetail"
        );

    if (
        !fromCity ||
        !fromDetail ||
        !toCity ||
        !toDetail
    ) {
        return;
    }

    const temporaryCity =
        fromCity.textContent;

    const temporaryDetail =
        fromDetail.textContent;

    fromCity.textContent =
        toCity.textContent;

    fromDetail.textContent =
        toDetail.textContent;

    toCity.textContent =
        temporaryCity;

    toDetail.textContent =
        temporaryDetail;
}


/* ===== SHOW STATIC RESULTS ===== */

function showStaticResults(resultId) {
    const resultsSection =
        document.getElementById(
            "fh-resultsSection"
        );

    const selectedResults =
        document.getElementById(
            resultId
        );

    if (
        !resultsSection ||
        !selectedResults
    ) {
        return;
    }

    document
        .querySelectorAll(
            ".fh-static-results"
        )
        .forEach(function (section) {
            section.style.display =
                "none";
        });

    resultsSection.style.display =
        "block";

    selectedResults.style.display =
        "block";

    resultsSection.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });
}









//    Tour Details Js 


document.addEventListener("DOMContentLoaded", function () {
    const backTopButton = document.getElementById("tripBackTop");

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

    function showSlide(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('jsp-active', i === current));
        dots.forEach((dot, i) => dot.classList.toggle('jsp-active', i === current));
    }

    function startAutoPlay() {
        clearInterval(timer);
        timer = setInterval(() => showSlide(current + 1), 5500);
    }

    document.getElementById('jspPrev').addEventListener('click', () => { showSlide(current - 1); startAutoPlay(); });
    document.getElementById('jspNext').addEventListener('click', () => { showSlide(current + 1); startAutoPlay(); });
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


