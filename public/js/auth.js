export async function requireAuth() {
    const token = localStorage.getItem("token");

    if (!token) {
        window.location.href = "/pages/login.html";
        return null;
    }

    try {
        const response = await fetch("/api/user", {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            }
        });

        const data = await response.json();

        if (data.id) {
            return data;
        } else {
            window.location.href = "/pages/login.html";
            return null;
        }

    } catch (err) {
        console.error("Auth error:", err);
        window.location.href = "/pages/login.html";
        return null;
    }
}