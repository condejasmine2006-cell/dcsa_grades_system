document.getElementById("loginForm").addEventListener("submit", function(event) {

    event.preventDefault();

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    const errorMessage = document.getElementById("errorMessage");

    if (username === "" || password === "") {

        errorMessage.textContent = "Please enter your username and password.";

        return;
    }

    // Temporary login for testing
    if (username === "admin" && password === "1234") {

        alert("Login successful!");

        window.location.href = "index.html";

    } else {

        errorMessage.textContent = "Incorrect username or password.";

    }

});