import { requireAuth } from "/js/auth.js";
const user = await requireAuth();
const token = localStorage.getItem("token");

document.getElementById('createSessionForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const location = document.getElementById("place").value;
    const date = document.getElementById("date").value;
    const timeStart = document.getElementById("timeStart").value;
    const timeEnd = document.getElementById("timeEnd").value;
    const top_rope = document.getElementById("needsTrBelayer").checked;
    const lead = document.getElementById("needsLeadBelayer").checked;
    const other = document.getElementById("otherNeed").value;
    const notes = document.getElementById("notes").value;

    try {
        const response = await fetch("/api/sessions", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            },

            body: JSON.stringify({
                place: location,
                date: date,
                time_start: timeStart,
                time_end: timeEnd,
                needs_tr_belayer: top_rope,
                needs_lead_belayer: lead,
                other_need: other,
                notes: notes,
            })
        });

        const data = await response.json();
        const resultDiv = document.getElementById("error");

        if (data.success) {
            window.location.href="/pages/index.html";
        } else {
            resultDiv.textContent = data.message;
            resultDiv.style.color = "red";
        }

    } catch (err) {
        console.error("Fetch error:", err);
    }

});