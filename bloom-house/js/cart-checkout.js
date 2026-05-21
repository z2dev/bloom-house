// Bloom House cart + checkout JavaScript
// Put this file in: js/cart-checkout.js

(function () {
    "use strict";

    function formatMoney(value) {
        return Number(value || 0).toFixed(2);
    }

    function show(el, displayValue = "block") {
        if (el) el.style.display = displayValue;
    }

    function hide(el) {
        if (el) el.style.display = "none";
    }

    /* ===== Checkout: Delivery / Store Pickup ===== */

    function setupCheckoutDelivery() {
        const deliveryOptions = document.querySelectorAll('input[name="delivery_method"]');
        if (!deliveryOptions.length) return;

        const config = window.BLOOM_CHECKOUT || {};
        const subtotalAmount = Number(config.subtotalAmount || 0);
        const discountAmount = Number(config.discountAmount || 0);
        const freeShippingThreshold = Number(config.freeShippingThreshold || 150);
        const normalShipping = Number(config.normalShipping || 15);

        const pickupBox = document.getElementById("pickup-location-box");
        const shippingCardTitle = document.getElementById("shipping-card-title");
        const shippingFields = document.querySelectorAll(".shipping-field");

        const addressInput = document.getElementById("address");
        const cityInput = document.getElementById("city");
        const postalInput = document.getElementById("postal");

        const shippingRow = document.getElementById("shipping-row");
        const shippingDisplay = document.getElementById("shipping-display");
        const totalDisplay = document.getElementById("total-display");
        const tamaraAmount = document.getElementById("tamara-amount");
        const freeShippingMessage = document.getElementById("free-shipping-message");

        function getSelectedMethod() {
            const selected = document.querySelector('input[name="delivery_method"]:checked');
            return selected ? selected.value : "delivery";
        }

        function updateDeliveryMethod() {
            const selected = getSelectedMethod();
            const amountAfterDiscount = Math.max(subtotalAmount - discountAmount, 0);
            let shipping = 0;

            if (selected === "pickup") {
                show(pickupBox);
                if (shippingCardTitle) shippingCardTitle.textContent = "Contact Information";

                shippingFields.forEach(function (field) {
                    hide(field);
                });

                if (addressInput) addressInput.required = false;
                if (cityInput) cityInput.required = false;
                if (postalInput) postalInput.required = false;

                hide(shippingRow);
                hide(freeShippingMessage);

                if (freeShippingMessage) freeShippingMessage.innerHTML = "";

                shipping = 0;
            } else {
                hide(pickupBox);
                if (shippingCardTitle) shippingCardTitle.textContent = "Shipping Address";

                shippingFields.forEach(function (field) {
                    field.style.display = "";
                });

                if (addressInput) addressInput.required = true;
                if (cityInput) cityInput.required = true;
                if (postalInput) postalInput.required = false;

                shipping = amountAfterDiscount >= freeShippingThreshold ? 0 : normalShipping;

                show(shippingRow, "flex");

                if (shippingDisplay) {
                    shippingDisplay.innerHTML = shipping === 0
                        ? '<span class="shipping-free">FREE</span>'
                        : '<span>' + formatMoney(shipping) + '</span> <span class="currency"><img src="images/icons/sar.png" alt="SAR" class="sar-icon"></span>';
                }

                if (freeShippingMessage) {
                    if (amountAfterDiscount > 0 && amountAfterDiscount < freeShippingThreshold) {
                        show(freeShippingMessage);
                        freeShippingMessage.innerHTML =
                            "🚚 Add <strong>" +
                            formatMoney(freeShippingThreshold - amountAfterDiscount) +
                            " SAR</strong> more to get FREE shipping!";
                    } else if (amountAfterDiscount >= freeShippingThreshold) {
                        show(freeShippingMessage);
                        freeShippingMessage.innerHTML =
                            "🎉 You unlocked <strong>FREE shipping</strong>!";
                    } else {
                        hide(freeShippingMessage);
                        freeShippingMessage.innerHTML = "";
                    }
                }
            }

            const total = amountAfterDiscount + shipping;

            if (totalDisplay) totalDisplay.textContent = formatMoney(total);
            if (tamaraAmount) tamaraAmount.textContent = formatMoney(total / 4);
        }

        deliveryOptions.forEach(function (option) {
            option.addEventListener("change", updateDeliveryMethod);
            option.addEventListener("click", updateDeliveryMethod);
        });

        updateDeliveryMethod();

        window.updateDeliveryMethod = updateDeliveryMethod;
    }

    /* ===== Checkout: Payment Method ===== */

    function setupPaymentMethod() {
        const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
        if (!paymentOptions.length) return;

        const creditFields = document.querySelector(".credit-card-fields");
        const notes = document.querySelectorAll(".payment-extra");

        function updatePaymentMethod() {
            const selected = document.querySelector('input[name="payment_method"]:checked');
            const value = selected ? selected.value : "Credit Card";

            if (creditFields) {
                creditFields.style.display =
                    value === "Credit Card" ? "block" : "none";
            }

            notes.forEach(function (note) {
                note.style.display = "none";
            });

            if (value === "Cash on Delivery") {
                show(document.querySelector(".cod-note"));
            }

            if (value === "Apple Pay") {
                show(document.querySelector(".apple-note"));
            }

            if (value === "Tamara") {
                show(document.querySelector(".tamara-note"));
            }
        }

        paymentOptions.forEach(function (option) {
            option.addEventListener("change", updatePaymentMethod);
            option.addEventListener("click", updatePaymentMethod);
        });

        updatePaymentMethod();
    }

    /* ===== Cart Suggestions ===== */

    function randomizeSuggestions() {
        const container = document.querySelector(".suggestions-grid");
        if (!container) return;

        const items = Array.from(container.children);

        items.sort(function () {
            return Math.random() - 0.5;
        });

        items.forEach(function (item) {
            container.appendChild(item);
        });
    }

    /* ===== Location API / Manual Address ===== */

    function setupLocationChoice() {
        const useCurrentLocationBtn = document.getElementById("use-current-location");
        const enterManualAddressBtn = document.getElementById("enter-manual-address");

        if (!useCurrentLocationBtn || !enterManualAddressBtn) return;

        const selectedLocationCard = document.getElementById("selected-location-card");
        const selectedLocationLink = document.getElementById("selected-location-link");
        const selectedLocationMain = document.getElementById("selected-location-main");
        const selectedLocationDetails = document.getElementById("selected-location-details");
        const locationStatus = document.getElementById("location-status");
        const manualAddressFields = document.getElementById("manual-address-fields");

        const addressInput = document.getElementById("address");
        const cityInput = document.getElementById("city");
        const postalInput = document.getElementById("postal");

        const latInput = document.getElementById("location_lat");
        const lngInput = document.getElementById("location_lng");
        const mapLinkInput = document.getElementById("location_map_link");

        function setLocationButtons(mode) {
            if (mode === "current") {
                useCurrentLocationBtn.classList.remove("inactive");
                enterManualAddressBtn.classList.remove("active");
            } else {
                useCurrentLocationBtn.classList.add("inactive");
                enterManualAddressBtn.classList.add("active");
            }
        }

        function showManualAddress() {
            setLocationButtons("manual");

            show(manualAddressFields);

            if (addressInput) {
                addressInput.required = true;
                addressInput.value = "";
            }

            if (cityInput) {
                cityInput.required = true;
                cityInput.value = "";
            }

            if (postalInput) postalInput.value = "";
            if (latInput) latInput.value = "";
            if (lngInput) lngInput.value = "";
            if (mapLinkInput) mapLinkInput.value = "";

            hide(selectedLocationCard);

            if (locationStatus) {
                locationStatus.textContent =
                    "You can type your delivery address manually below.";
            }
        }

        function getCityFromAddress(addressParts) {
            return addressParts.city ||
                   addressParts.town ||
                   addressParts.village ||
                   addressParts.suburb ||
                   addressParts.county ||
                   addressParts.state ||
                   "";
        }

        async function reverseGeocode(lat, lng) {
            const url =
                "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=" +
                encodeURIComponent(lat) +
                "&lon=" +
                encodeURIComponent(lng);

            const response = await fetch(url, {
                headers: {
                    "Accept": "application/json"
                }
            });

            if (!response.ok) {
                throw new Error("Reverse geocoding failed");
            }

            return response.json();
        }

        async function useMapLocation(lat, lng) {
            const mapLink = "https://www.google.com/maps?q=" + lat + "," + lng;

            setLocationButtons("current");

            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;
            if (mapLinkInput) mapLinkInput.value = mapLink;

            if (locationStatus) {
                locationStatus.textContent =
                    "Converting your location to address text...";
            }

            try {
                const data = await reverseGeocode(lat, lng);

                const readableAddress =
                    data.display_name || "Current delivery location from device";

                const readableCity =
                    getCityFromAddress(data.address || {}) ||
                    "Detected from location";

                const postalCode =
                    data.address && data.address.postcode
                        ? data.address.postcode
                        : "";

                if (addressInput) addressInput.value = readableAddress;
                if (cityInput) cityInput.value = readableCity;
                if (postalInput) postalInput.value = postalCode;

                const addressParts =
                    readableAddress
                        .split(",")
                        .map(function (part) {
                            return part.trim();
                        })
                        .filter(Boolean);

                if (selectedLocationMain) {
                    selectedLocationMain.textContent =
                        addressParts.slice(0, 3).join(", ") ||
                        readableCity ||
                        "Current delivery location";
                }

                if (selectedLocationDetails) {
                    selectedLocationDetails.textContent =
                        addressParts.slice(3).join(", ") ||
                        readableAddress;
                }

                if (locationStatus) {
                    locationStatus.textContent =
                        "Location added as address text. You can still edit it manually if needed.";
                }

            } catch (error) {
                if (addressInput) addressInput.value = "Current delivery location from device";
                if (cityInput) cityInput.value = "Detected from location";
                if (postalInput) postalInput.value = "";

                if (selectedLocationMain) {
                    selectedLocationMain.textContent =
                        "Current delivery location";
                }

                if (selectedLocationDetails) {
                    selectedLocationDetails.textContent =
                        "Location selected from your device. You can edit the address manually if needed.";
                }

                if (locationStatus) {
                    locationStatus.textContent =
                        "Location selected. Please review or edit the address before confirming the order.";
                }
            }

            if (addressInput) addressInput.required = true;
            if (cityInput) cityInput.required = true;

            show(manualAddressFields);
            show(selectedLocationCard);

            if (selectedLocationLink) {
                selectedLocationLink.href = mapLink;
            }
        }

        useCurrentLocationBtn.addEventListener("click", function () {
            if (!navigator.geolocation) {
                if (locationStatus) {
                    locationStatus.textContent =
                        "Your browser does not support location. Please enter the address manually.";
                }
                return;
            }

            setLocationButtons("current");

            if (locationStatus) {
                locationStatus.textContent =
                    "Getting your current location...";
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    useMapLocation(
                        position.coords.latitude,
                        position.coords.longitude
                    );
                },
                function () {
                    if (locationStatus) {
                        locationStatus.textContent =
                            "Location permission was not allowed. Please enter the address manually.";
                    }

                    setLocationButtons("manual");
                }
            );
        });

        enterManualAddressBtn.addEventListener("click", showManualAddress);
    }

    /* ===== Init ===== */

    document.addEventListener("DOMContentLoaded", function () {
        setupCheckoutDelivery();
        setupPaymentMethod();
        setupLocationChoice();
        randomizeSuggestions();
    });

})();