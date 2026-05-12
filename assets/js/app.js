document.addEventListener("DOMContentLoaded", () => {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alertEl) => {
        setTimeout(() => {
            if (alertEl.classList.contains("alert-success")) {
                alertEl.style.display = "none";
            }
        }, 4000);
    });
});
