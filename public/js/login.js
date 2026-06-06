document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const username = document.getElementById("loginUsername").value;
    const password = document.getElementById("loginPassword").value;

    try {
        const response = await fetch("/api/login", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                username: username,
                password: password,
            })
        });

        const data = await response.json();

        const resultDiv = document.getElementById("error");
        if (data.success && data.token) {
            localStorage.setItem('token', data.token);
            window.location.href="/pages/index.html";

        } else {
            resultDiv.textContent = data.message;
            resultDiv.style.color = "red";
        }

    } catch (err) {
        console.error("Fetch error:", err);
        document.getElementById("error").textContent = "Error contacting server.";
    }
});

document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const username = document.getElementById("regUsername").value;
    const password = document.getElementById("regPassword").value;

    try {
        const response = await fetch("/api/register", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                username: username,
                email: document.getElementById("regEmail").value,
                password: password,
                password_confirmation: document.getElementById("regPasswordConfirm").value,
            })
        });

        const data = await response.json();

        const resultDiv = document.getElementById("error");

        if (data.success && data.token) {
            localStorage.setItem('token', data.token);
            window.location.href = "/pages/index.html";
        }

    } catch (err) {
        console.error("Fetch error:", err);
        document.getElementById("error").textContent = "Error contacting server.";
    }
});