import { requireAuth } from "/js/auth.js";
import { logout } from "/js/auth.js";

const token = localStorage.getItem("token");

document.addEventListener("DOMContentLoaded", async (e) => {
    const user = await requireAuth();

    if (user) {
        document.getElementById('name').textContent = user.username;
        document.getElementById('profileName').textContent = user.username;
        document.getElementById('bioInput').value = user.bio ?? "";

        const img = document.getElementById('profilePicImg');
        img.src = user.profile_picture ? `/storage/${user.profile_picture}` : "/images/default-avatar.png";
    }

    document.getElementById("logoutBtn").addEventListener("click", logout);
});

// Update bio
document.getElementById("bioForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const bio = document.getElementById("bioInput").value;

    try {
        const response = await fetch("/api/profile/bio", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            },
            body: JSON.stringify({ bio })
        });

        const data = await response.json();

        if (data.success) {
            alert("Bio updated!");
        } else {
            alert(data.message ?? "Something went wrong.");
        }

    } catch (err) {
        console.error("Bio update error:", err);
    }
});

// Upload profile picture
document.getElementById("pictureForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById("pictureInput");

    if (!fileInput.files.length) {
        alert("Please choose a file first.");
        return;
    }

    const formData = new FormData();
    formData.append("profile_picture", fileInput.files[0]);

    try {
        const response = await fetch("/api/profile/picture", {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${token}`
                // No Content-Type here — the browser sets it automatically
                // with the correct multipart boundary for FormData
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById("profilePicImg").src = data.profile_picture_url;
        } else {
            alert(data.message ?? "Upload failed.");
        }

    } catch (err) {
        console.error("Picture upload error:", err);
    }
});

// Update username
document.getElementById("changeUserForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("username").value;

    try {
        const response = await fetch("/api/profile/username", {
            method: "PATCH",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            },
            body: JSON.stringify({ username })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('name').textContent = data.username;
            document.getElementById('profileName').textContent = data.username;
            alert("Username updated!");
        } else {
            alert(data.message ?? "Update failed.");
        }

    } catch (err) {
        console.error("Username update error:", err);
    }
});