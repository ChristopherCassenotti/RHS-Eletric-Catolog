document.addEventListener("DOMContentLoaded", () => {
  const questions = document.querySelectorAll("[data-faq-question]");

  questions.forEach((question) => {
    question.addEventListener("click", () => {
      const item = question.closest(".faq-item");

      if (!item) return;

      item.classList.toggle("open");
    });
  });
});