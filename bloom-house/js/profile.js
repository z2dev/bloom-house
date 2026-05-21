document.addEventListener("DOMContentLoaded", function () {
  const profileForm = document.getElementById("profileForm");
  const message = document.getElementById("profileMessage");

  profileForm.addEventListener("submit", function (event) {
  

    const formData = new FormData(profileForm);

    fetch("profile.php", {
      method: "POST",
      body: formData
    })
      .then(response => response.text())
      .then(data => {
        if (data.trim() === "success") {
          message.style.color = "green";
          message.textContent = "Profile updated successfully!";
        } else {
          message.style.color = "red";
          message.textContent = "Error updating profile.";
        }
      })
      .catch(error => {
        message.style.color = "red";
        message.textContent = "Something went wrong.";
      });
  });
});