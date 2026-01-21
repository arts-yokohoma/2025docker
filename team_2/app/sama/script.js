document.addEventListener("DOMContentLoaded", () => {
  /* ===== Mobile Menu ===== */
  const menuToggle = document.querySelector(".menu-toggle");
  const navLinks = document.querySelector(".nav-links");

  menuToggle.addEventListener("click", () => {
    const isActive = navLinks.classList.toggle("active");
    menuToggle.setAttribute("aria-expanded", isActive ? "true" : "false");
  });

  /* ===== Slider ===== */
  const slides = document.querySelectorAll(".slide");
  const dots = document.querySelectorAll(".slider-dot");
  let currentSlide = 0;

  function showSlide(index) {
    slides.forEach(slide => slide.classList.remove("active"));
    dots.forEach(dot => {
      dot.classList.remove("active");
      dot.setAttribute("aria-selected", "false");
      dot.setAttribute("tabindex", "-1");
    });

    currentSlide = (index + slides.length) % slides.length;
    slides[currentSlide].classList.add("active");
    dots[currentSlide].classList.add("active");
    dots[currentSlide].setAttribute("aria-selected", "true");
    dots[currentSlide].setAttribute("tabindex", "0");
  }

  dots.forEach((dot, i) => {
    dot.addEventListener("click", () => showSlide(i));
    dot.addEventListener("keydown", e => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        showSlide(i);
      }
    });
  });

  let sliderInterval = setInterval(() => showSlide(currentSlide + 1), 5000);

  // Pause slider on hover/focus
  const heroSlider = document.querySelector(".hero-slider");
  heroSlider.addEventListener("mouseenter", () => clearInterval(sliderInterval));
  heroSlider.addEventListener("mouseleave", () => {
    sliderInterval = setInterval(() => showSlide(currentSlide + 1), 5000);
  });

  /* ===== Counter ===== */
  const counters = document.querySelectorAll(".counter");
  let started = false;

  function startCounter() {
    counters.forEach(counter => {
      const target = +counter.dataset.target;
      let count = 0;

      const increment = Math.ceil(target / 150);

      const update = () => {
        count += increment;
        if (count < target) {
          counter.textContent = count.toLocaleString();
          requestAnimationFrame(update);
        } else {
          counter.textContent = target.toLocaleString();
        }
      };
      update();
    });
  }

  const stats = document.querySelector(".stats");
  const observer = new IntersectionObserver(
    entries => {
      if (entries[0].isIntersecting && !started) {
        startCounter();
        started = true;
      }
    },
    { threshold: 0.5 }
  );

  observer.observe(stats);

  /* ===== Language Switcher ===== */
  const translations = {
    en: {
      home: "Home",
      menu: "Menu",
      services: "Services",
      about: "About",
      contact: "Contact",
    },
    jp: {
      home: "ホーム",
      menu: "メニュー",
      services: "サービス",
      about: "約",
      contact: "連絡先",
    },
  };

  const langButtons = document.querySelectorAll("[data-lang]");
  langButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      const lang = btn.dataset.lang;

      // Update aria-pressed attributes
      langButtons.forEach(b => b.setAttribute("aria-pressed", "false"));
      btn.setAttribute("aria-pressed", "true");

      document.querySelectorAll("[data-i18n]").forEach(el => {
        if (translations[lang] && translations[lang][el.dataset.i18n]) {
          el.textContent = translations[lang][el.dataset.i18n];
        }
      });
    });
  });
});