// Filename: app.js
// Destination: /study_planner/assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-char-counter]').forEach((textarea) => {
        const counterId = textarea.getAttribute('data-char-counter');
        const counter = counterId ? document.getElementById(counterId) : null;

        if (!counter) {
            return;
        }

        const updateCounter = () => {
            counter.textContent = `${textarea.value.length} / ${textarea.maxLength || 2000}`;
        };

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
});
