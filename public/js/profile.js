import { requireAuth } from "/js/auth.js";
import { logout } from "/js/auth.js";

const token = localStorage.getItem("token");

document.addEventListener("DOMContentLoaded", async (e) => {
    const user = await requireAuth();

    if (user) {
        document.getElementById('name').textContent = user.username;
        document.getElementById('profileName').textContent = user.username;
    }   

    document.getElementById("logoutBtn").addEventListener("click", logout);
});