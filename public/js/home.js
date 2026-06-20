import { requireAuth } from "/js/auth.js";
import { logout } from "/js/auth.js";
const token = localStorage.getItem("token");

document.addEventListener("DOMContentLoaded", async (e) => {
    const user = await requireAuth();

    if (user) {
        document.getElementById('name').textContent = user.username;
    } 

    document.getElementById("logoutBtn").addEventListener("click", logout);

    const resultDiv = document.getElementById("sessionList");

    try {
        const result = await fetch("api/sessions", {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            },
        });

        const data = await result.json();
        if (data.success) {
            data.sessions.forEach(session => {
                const div = document.createElement("div");

                div.innerHTML = `
                    <h2>Host: ${session.user.username}</h2>
                    <h3>${session.place}</h3>
                    <p>${session.date}</p>
                    <p>${session.time_start} - ${session.time_end ?? "Unknown"}</p>
                `;

                resultDiv.appendChild(div);
            });
        }


    } catch(err) {
        console.error("Fetch error:", err);
    }
});