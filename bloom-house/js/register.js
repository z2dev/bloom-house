function togglePw(fieldId, icon) {

    const input = document.getElementById(fieldId);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

/* PASSWORD STRENGTH */

function checkStrength(val) {

    const bar = document.getElementById("strengthBar");

    let score = 0;

    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const pct = (score / 5) * 100;

    const color =
        score <= 1 ? "#e74c3c" :
        score <= 3 ? "#f39c12" :
        "#2f5e4a";

    bar.style.width = pct + "%";
    bar.style.background = color;
}

/* PHONE PLACEHOLDER */

function updatePhonePlaceholder() {

    const country = document.getElementById("country_code");
    const phone = document.getElementById("phone");
    const msg = document.getElementById("phoneMessage");

    const selected = country.options[country.selectedIndex];

    phone.placeholder = selected.getAttribute("data-placeholder");
    phone.value = "";

    msg.style.display = "none";
    msg.textContent = "";
    msg.classList.remove("error", "success");
}

/* EMAIL VALIDATION */

function validateEmail() {

    const email = document.getElementById("email").value.trim();
    const msg = document.getElementById("phoneMessage");

    msg.style.display = "none";
    msg.textContent = "";
    msg.classList.remove("error", "success");

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailPattern.test(email)) {

        msg.textContent = "Please enter a valid email address.";
        msg.classList.add("error");
        msg.style.display = "block";

        return false;
    }

    return true;
}

/* PHONE VALIDATION */

function validatePhone() {

    const country = document.getElementById("country_code").value;
    const phoneInput = document.getElementById("phone");
    const phone = phoneInput.value.trim();
    const msg = document.getElementById("phoneMessage");

    let requiredLength = 0;
    let startPattern;
    let startText = "";

    msg.style.display = "none";
    msg.textContent = "";
    msg.classList.remove("success");
    msg.classList.add("error");

    if (!/^[0-9]*$/.test(phone)) {

        msg.textContent = "Phone number must contain numbers only.";
        msg.style.display = "block";

        return false;
    }

    if (country === "+966") {

        requiredLength = 10;
        startPattern = /^05/;
        startText = "Saudi number must start with 05.";

    } else if (country === "+965") {

        requiredLength = 8;
        startPattern = /^5/;
        startText = "Kuwait number must start with 5.";

    } else if (country === "+974") {

        requiredLength = 8;
        startPattern = /^3/;
        startText = "Qatar number must start with 3.";
    }

    if (phone.length > 0 && !startPattern.test(phone)) {

        msg.textContent = startText;
        msg.style.display = "block";

        return false;
    }

    if (phone.length > requiredLength) {

        msg.textContent =
            "Phone number is too long. It must be " +
            requiredLength +
            " digits.";

        msg.style.display = "block";

        return false;
    }

    if (phone.length < requiredLength) {

        msg.textContent =
            "Phone number is too short. It must be " +
            requiredLength +
            " digits.";

        msg.style.display = "block";

        return false;
    }

    msg.textContent = "Phone number looks good.";
    msg.classList.remove("error");
    msg.classList.add("success");
    msg.style.display = "block";

    return true;
}

/* PAGE LOAD */

document.addEventListener("DOMContentLoaded", function () {

    updatePhonePlaceholder();

    const form = document.querySelector("form");
    const phoneInput = document.getElementById("phone");
    const countrySelect = document.getElementById("country_code");

    /* SUBMIT */

    /* SUBMIT */

form.addEventListener("submit", function (e) {

    e.preventDefault();

    const firstName =
    document.getElementById("first_name").value.trim();

    const lastName =
    document.getElementById("last_name").value.trim();

    const email =
    document.getElementById("email").value.trim();

    const phone =
    document.getElementById("phone").value.trim();

    const password =
    document.getElementById("password").value.trim();

    const confirmPw =
    document.getElementById("confirm_pw").value.trim();

    const msg =
    document.getElementById("phoneMessage");

    msg.style.display = "none";
    msg.textContent = "";
    msg.classList.remove("error", "success");

    /* EMPTY FIELDS */

    if (
        firstName === "" ||
        lastName === "" ||
        email === "" ||
        phone === "" ||
        password === "" ||
        confirmPw === ""
    ) {

        msg.textContent =
        "Please fill in all required fields.";

        msg.classList.add("error");
        msg.style.display = "block";

        return;
    }

    /* EMAIL */

    if (!validateEmail()) {
        return;
    }

    /* PHONE */

    if (!validatePhone()) {
        return;
    }

    const btn =
    document.querySelector(".btn-register");

    btn.innerText = "Creating Account...";
    btn.disabled = true;

    setTimeout(() => {

        form.submit();

    }, 650);
});

    /* LIVE PHONE VALIDATION */

    phoneInput.addEventListener("input", function () {

        const country = countrySelect.value;
        const phone = phoneInput.value.trim();
        const msg = document.getElementById("phoneMessage");

        let requiredLength = 0;

        if (country === "+966") {
            requiredLength = 10;
        } else if (country === "+965") {
            requiredLength = 8;
        } else if (country === "+974") {
            requiredLength = 8;
        }

        if (phone.length < requiredLength) {
            msg.style.display = "none";
            msg.textContent = "";
            msg.classList.remove("error", "success");
            return;
        }

        validatePhone();
    });

    /* COUNTRY CHANGE */

    countrySelect.addEventListener("change", function () {

        updatePhonePlaceholder();

        const msg = document.getElementById("phoneMessage");

        msg.style.display = "none";
        msg.textContent = "";
        msg.classList.remove("error", "success");
    });
});