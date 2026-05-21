/* Bloom House - Orders JavaScript
   Scripts moved from orders pages into one external file. */

/* From: manage-orders.php */
var toast = document.getElementById("orderToast");

if (toast) {
    setTimeout(function () {
        toast.style.display = "none";
    }, 3000);
}

/* From: admin-view-order.php */
function downloadInvoicePDF() {
    var invoice = document.getElementById("pdfInvoice");
    if (!invoice) { return; }

    var orderId = "";
    var params = new URLSearchParams(window.location.search);
    if (params.has("order_id")) {
        orderId = params.get("order_id");
    } else if (params.has("id")) {
        orderId = params.get("id");
    }

    invoice.style.display = "block";

    var opt = {
        margin: 0,
        filename: "Bloom-House-Invoice-BH-" + orderId + ".pdf",
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: "pt", format: "a4", orientation: "portrait" }
    };

    html2pdf().set(opt).from(invoice.querySelector(".pdf-invoice")).save().then(function () {
        invoice.style.display = "none";
    });
}

/* From: admin-edit-order.php */
var unlockBtn = document.getElementById("unlockInfoBtn");
var infoCard = document.getElementById("infoCard");
var confirmOverlay = document.getElementById("confirmOverlay");
var cancelUnlock = document.getElementById("cancelUnlock");
var confirmUnlock = document.getElementById("confirmUnlock");

if (unlockBtn && confirmOverlay) {
    unlockBtn.onclick = function () {
        confirmOverlay.classList.add("show");
    };
}

if (cancelUnlock && confirmOverlay) {
    cancelUnlock.onclick = function () {
        confirmOverlay.classList.remove("show");
    };
}

if (confirmUnlock && infoCard && unlockBtn && confirmOverlay) {
    confirmUnlock.onclick = function () {
        var fields = infoCard.querySelectorAll("input");
        for (var i = 0; i < fields.length; i++) {
            fields[i].disabled = false;
        }

        var buttons = infoCard.querySelectorAll("button");
        for (var j = 0; j < buttons.length; j++) {
            buttons[j].disabled = false;
        }

        infoCard.classList.remove("locked");
        unlockBtn.innerHTML = "<i class='fas fa-check'></i> Info Editable";
        confirmOverlay.classList.remove("show");
    };
}

function initAddressAutocomplete() {
    var addressInput = document.getElementById("shippingAddress");

    if (!addressInput || typeof google === "undefined" || !google.maps || !google.maps.places) {
        return;
    }

    var autocomplete = new google.maps.places.Autocomplete(addressInput, {
        types: ["geocode"],
        componentRestrictions: { country: "sa" }
    });

    autocomplete.addListener("place_changed", function () {
        var place = autocomplete.getPlace();

        if (place && place.formatted_address) {
            addressInput.value = place.formatted_address;
        }
    });
}

var searchAddressBtn = document.getElementById("searchAddressBtn");
var shippingAddress = document.getElementById("shippingAddress");
var addressResults = document.getElementById("addressResults");

if (searchAddressBtn && shippingAddress && addressResults) {
    searchAddressBtn.onclick = function () {
        var query = shippingAddress.value.trim();

        if (query.length < 3) {
            addressResults.innerHTML = "<div class='address-result-item'>Please type at least 3 letters.</div>";
            return;
        }

        addressResults.innerHTML = "<div class='address-result-item'>Searching...</div>";

        fetch("https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=5&countrycodes=sa&q=" + encodeURIComponent(query))
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                addressResults.innerHTML = "";

                if (data.length === 0) {
                    addressResults.innerHTML = "<div class='address-result-item'>No results found.</div>";
                    return;
                }

                data.forEach(function (place) {
                    var item = document.createElement("button");
                    item.type = "button";
                    item.className = "address-result-item";
                    item.innerHTML = place.display_name;

                    item.onclick = function () {
                        shippingAddress.value = place.display_name;
                        addressResults.innerHTML = "";
                    };

                    addressResults.appendChild(item);
                });
            })
            .catch(function () {
                addressResults.innerHTML = "<div class='address-result-item'>Address search failed.</div>";
            });
    };
}

/* Extra validation for admin order edit form (added for project rubric). */
var editOrderForm = document.getElementById("editOrderForm");

function showOrderValidationMessage(message) {
    var oldMessage = document.getElementById("orderValidationMessage");
    if (oldMessage) {
        oldMessage.remove();
    }

    var messageBox = document.createElement("div");
    messageBox.id = "orderValidationMessage";
    messageBox.className = "dashboard-card";
    messageBox.style.marginBottom = "16px";
    messageBox.style.borderLeft = "4px solid #c0392b";
    messageBox.innerHTML = "<strong>Please check:</strong> " + message;

    if (editOrderForm && editOrderForm.parentNode) {
        editOrderForm.parentNode.insertBefore(messageBox, editOrderForm);
    } else {
        alert(message);
    }
}

if (editOrderForm) {
    editOrderForm.addEventListener("submit", function (event) {
        var selectedStatus = editOrderForm.querySelector("input[name='order_status']:checked");
        if (!selectedStatus) {
            event.preventDefault();
            showOrderValidationMessage("Please choose an order status.");
            return;
        }

        var phoneInput = editOrderForm.querySelector("input[name='phone']");
        if (phoneInput && !phoneInput.disabled) {
            var phoneValue = phoneInput.value.trim();
            if (!/^05[0-9]{8}$/.test(phoneValue)) {
                event.preventDefault();
                showOrderValidationMessage("Phone number must start with 05 and contain 10 digits.");
                phoneInput.focus();
                return;
            }
        }

        var cityInput = editOrderForm.querySelector("input[name='city']");
        if (cityInput && !cityInput.disabled && cityInput.value.trim() === "") {
            event.preventDefault();
            showOrderValidationMessage("City cannot be empty.");
            cityInput.focus();
            return;
        }

        var addressInput = editOrderForm.querySelector("input[name='shipping_address']");
        if (addressInput && !addressInput.disabled && addressInput.value.trim() === "") {
            event.preventDefault();
            showOrderValidationMessage("Shipping address cannot be empty.");
            addressInput.focus();
            return;
        }

        var discountInput = editOrderForm.querySelector("input[name='discount']");
        if (discountInput && !discountInput.disabled) {
            var discountValue = parseFloat(discountInput.value);
            if (isNaN(discountValue) || discountValue < 0 || discountValue > 100) {
                event.preventDefault();
                showOrderValidationMessage("Discount must be between 0 and 100.");
                discountInput.focus();
                return;
            }
        }

        var quantityInputs = editOrderForm.querySelectorAll("input[name^='quantity']");
        for (var q = 0; q < quantityInputs.length; q++) {
            var quantityValue = parseInt(quantityInputs[q].value, 10);
            if (isNaN(quantityValue) || quantityValue < 1) {
                event.preventDefault();
                showOrderValidationMessage("Quantity must be at least 1.");
                quantityInputs[q].focus();
                return;
            }
        }

        var priceInputs = editOrderForm.querySelectorAll("input[name^='unit_price']");
        for (var p = 0; p < priceInputs.length; p++) {
            var priceValue = parseFloat(priceInputs[p].value);
            if (isNaN(priceValue) || priceValue < 0) {
                event.preventDefault();
                showOrderValidationMessage("Unit price cannot be negative.");
                priceInputs[p].focus();
                return;
            }
        }
    });
}
