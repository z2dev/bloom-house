document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".remove-fav-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            var card = button.closest(".product-card");
            var plantId = card.getAttribute("data-plant-id");

            fetch("toggle_favourite.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "plant_id=" + encodeURIComponent(plantId)
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {

                if (data.status === "removed") {
                    card.style.transition = "opacity 0.4s";
                    card.style.opacity = "0";

                    setTimeout(function () {
                        card.remove();

                        if (document.querySelectorAll(".product-card").length === 0) {
                            document.querySelector(".products-grid").innerHTML =
                                '<p style="font-size:18px; color:#777;">No favourite plants yet. <a href="products.php" style="color:#2f5e4a;">Browse plants</a></p>';
                        }

                    }, 400);
                }

                else if (data.status === "not_logged_in") {
                    window.location.href = "login.php";
                }

            })
            .catch(function () {
                alert("Something went wrong. Please try again.");
            });

        });

    });

});