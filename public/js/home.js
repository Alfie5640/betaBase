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
                <div style="display:flex; align-items:center; gap:8px;">
                    <img 
                        src="${session.user.profile_picture ? `/storage/${session.user.profile_picture}` : '/images/default-avatar.png'}" 
                        alt="${session.user.username}" 
                        style="width:32px; height:32px; border-radius:50%; object-fit:cover;"
                    >
                    <h2>${session.user.username}</h2>
                </div>
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