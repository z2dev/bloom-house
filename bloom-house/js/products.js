// Get all filter checkboxes and important page elements
var filterInputs = document.querySelectorAll('.product-filter');
var clearInputs = document.querySelectorAll('.clear-filter-key');
var rangeInput = document.querySelector('.range-input');
var priceValue = document.querySelector('.price-value');
var sortSelect = document.querySelector('.sort-select');
var cartForms = document.querySelectorAll('.cart-form');
var detailsCartForm = document.getElementById('detailsCartForm');
var cartToast = document.getElementById('cartToast');
var cartToastTitle = document.getElementById('cartToastTitle');
var cartToastMessage = document.getElementById('cartToastMessage');
var continueShoppingBtn = document.getElementById('continueShoppingBtn');
var toastPrimaryLink = document.getElementById('toastPrimaryLink');
var quantityInput = document.getElementById('quantityInput');
var minusBtn = document.querySelector('.qty-minus');
var plusBtn = document.querySelector('.qty-plus');
var favButton = document.getElementById('favButton');
var productFavButtons = document.querySelectorAll('.product-fav-btn');
var helpOpen = document.getElementById('helpOpen');
var helpModal = document.getElementById('helpModal');
var helpClose = document.getElementById('helpClose');
var helpOk = document.getElementById('helpOk');
var priceTimer;

// Update page URL with selected filters and sorting options
function goWithParams(params) {
    var query = params.toString();
    if (query != '') {
        window.location.href = window.location.pathname + '?' + query;
    } else {
        window.location.href = window.location.pathname;
    }
}

function removeValue(params, key, value) {
    var name = key + '[]';
    var values = params.getAll(name);
    params.delete(name);
    for (var i = 0; i < values.length; i++) {
        if (values[i] != value) {
            params.append(name, values[i]);
        }
    }
}

// Handle filter checkbox changes
for (var i = 0; i < filterInputs.length; i++) {
    filterInputs[i].addEventListener('change', function () {
        var params = new URLSearchParams(window.location.search);
        var key = this.getAttribute('data-key');
        var value = this.getAttribute('data-value');
        var name = key + '[]';

        removeValue(params, key, value);
        if (this.checked) {
            params.append(name, value);
        }
        goWithParams(params);
    });
}

for (var j = 0; j < clearInputs.length; j++) {
    clearInputs[j].addEventListener('change', function () {
        var params = new URLSearchParams(window.location.search);
        var key = this.getAttribute('data-key');
        params.delete(key + '[]');
        params.delete(key);
        goWithParams(params);
    });
}

// Handle price range slider updates
if (rangeInput) {
    rangeInput.addEventListener('input', function () {
        if (priceValue) {
            priceValue.innerHTML = this.value + ' SAR';
        }
        clearTimeout(priceTimer);
        var value = this.value;
        priceTimer = setTimeout(function () {
            var params = new URLSearchParams(window.location.search);
            if (value == '200') {
                params.delete('max_price');
            } else {
                params.set('max_price', value);
            }
            goWithParams(params);
        }, 500);
    });
}

// Handle sorting products based on selected option
if (sortSelect) {
    sortSelect.addEventListener('change', function () {
        var params = new URLSearchParams(window.location.search);
        if (this.value == '') {
            params.delete('sort');
        } else {
            params.set('sort', this.value);
        }
        goWithParams(params);
    });
}

function setToastAction(type) {
    if (!toastPrimaryLink) { return; }

    if (type == 'favorite') {
        toastPrimaryLink.href = 'favourites.php';
        toastPrimaryLink.innerHTML = 'View Favourites';

    } else if (type == 'login') {

        toastPrimaryLink.href = 'register.php';
        toastPrimaryLink.innerHTML = 'Sign Up';

        if (cartToastMessage) {
            cartToastMessage.innerHTML =
            'Sign up now and get a special discount on your first order!';
        }

    } else {
        toastPrimaryLink.href = 'cart.php';
        toastPrimaryLink.innerHTML = 'View Cart';
    }
}

// Display notification message at the top of the page
function showCartToast(title, message, success, actionType) {
    if (!cartToast) { return; }

    cartToastTitle.innerHTML = title;
    cartToastMessage.innerHTML = message;
    cartToast.classList.add('show');

    if (!success) {
        cartToast.classList.add('error');
        cartToast.querySelector('.cart-toast-icon').innerHTML = '<i class="bi bi-exclamation-circle"></i>';
    } else {
        cartToast.classList.remove('error');
        cartToast.querySelector('.cart-toast-icon').innerHTML = '<i class="bi bi-check2"></i>';
    }

    setToastAction(actionType || 'cart');
}

function hideCartToast() {
    if (cartToast) {
        cartToast.classList.remove('show');
    }
}

if (continueShoppingBtn) {
    continueShoppingBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        hideCartToast();
    });
}

// Add product to cart using fetch without page reload
function handleAddCart(form) {
    var formData = new FormData(form);
    formData.set('action', 'add_cart');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        if (data.status == 'success') {
            showCartToast('Added to Cart', 'Product added successfully.', true, 'cart');
        } else if (data.status == 'not_logged_in') {
            showCartToast('Exclusive Discount', 'Sign up now and get a special discount on your first order!', false, 'login');
        } else {
            showCartToast('Notice', data.message, false, 'cart');
        }
    })
    .catch(function () {
        showCartToast('Error', 'Something went wrong. Please try again.', false, 'cart');
    });
}

for (var k = 0; k < cartForms.length; k++) {
    cartForms[k].addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();
        handleAddCart(this);
    });
}

if (detailsCartForm) {
    detailsCartForm.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();
        handleAddCart(detailsCartForm);
    });
}

function getQtyMax() {
    if (!quantityInput) { return 1; }
    var max = parseInt(quantityInput.getAttribute('max'));
    if (isNaN(max) || max < 1) { max = 1; }
    return max;
}

if (minusBtn && plusBtn && quantityInput) {
    minusBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var value = parseInt(quantityInput.value) || 1;
        quantityInput.value = value > 1 ? value - 1 : 1;
    });

    plusBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var value = parseInt(quantityInput.value) || 1;
        var max = getQtyMax();
        if (value < max) {
            quantityInput.value = value + 1;
        }
    });

    quantityInput.addEventListener('change', function () {
        var value = parseInt(quantityInput.value) || 1;
        var max = getQtyMax();
        if (value > max) { value = max; }
        if (value < 1) { value = 1; }
        quantityInput.value = value;
    });
}

// Change heart icon to filled favourite state
function markFavourite(button) {
    var icon = button.querySelector('i');
    if (icon) {
        icon.className = 'bi bi-heart-fill';
    }
    button.classList.add('active');
    button.setAttribute('title', 'Remove from favourites');
}

function unmarkFavourite(button) {
    var icon = button.querySelector('i');
    if (icon) {
        icon.className = 'bi bi-heart';
    }
    button.classList.remove('active');
    button.setAttribute('title', 'Add to favourites');
}

// Add or remove product from favourites
function addFavourite(button) {
    var plantId = button.getAttribute('data-plant-id');
    var formData = new FormData();
    formData.append('plant_id', plantId);

    fetch('toggle_favourite.php', {
        method: 'POST',
        body: formData
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        if (data.status == 'added') {
            markFavourite(button);
            showCartToast('Favourites', 'Plant added to favourites successfully.', true, 'favorite');
        } else if (data.status == 'removed') {
            unmarkFavourite(button);
            showCartToast('Favourites', 'Plant removed from favourites.', true, 'favorite');
        } else if (data.status == 'not_logged_in') {
            showCartToast('Login Required', 'Please login first to use favourites.', false, 'login');
        } else {
            showCartToast('Notice', 'Could not update favourites.', false, 'favorite');
        }
    })
    .catch(function () {
        showCartToast('Error', 'Something went wrong. Please try again.', false, 'favorite');
    });
}

if (favButton) {
    favButton.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        addFavourite(favButton);
    });
}

for (var f = 0; f < productFavButtons.length; f++) {
    productFavButtons[f].addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        addFavourite(this);
    });
}

// Open plant care help popup modal
function openHelp() {
    if (helpModal) {
        helpModal.classList.add('show');
        helpModal.setAttribute('aria-hidden', 'false');
    }
}

function closeHelp() {
    if (helpModal) {
        helpModal.classList.remove('show');
        helpModal.setAttribute('aria-hidden', 'true');
    }
}

if (helpOpen) {
    helpOpen.addEventListener('click', function (e) {
        e.stopPropagation();
        openHelp();
    });
}

if (helpClose) { helpClose.addEventListener('click', closeHelp); }
if (helpOk) { helpOk.addEventListener('click', closeHelp); }

if (helpModal) {
    helpModal.addEventListener('click', function (e) {
        if (e.target === helpModal) {
            closeHelp();
        }
    });
}

document.addEventListener('click', function (e) {
    if (cartToast && cartToast.classList.contains('show') && !cartToast.contains(e.target)) {
        hideCartToast();
    }
});
