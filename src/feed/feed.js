const STORAGE_KEYS = {
  liked: "studyshare-feed-liked",
  ratings: "studyshare-feed-ratings",
};

const filtersForm = document.querySelector("#feedFilters");
const searchInput = document.querySelector("#searchInput");
const disciplineFilter = document.querySelector("#disciplineFilter");
const courseFilter = document.querySelector("#courseFilter");
const typeFilter = document.querySelector("#typeFilter");
const resultCount = document.querySelector("#resultCount");
const emptyState = document.querySelector("#emptyState");
const cards = [...document.querySelectorAll(".js-material-card")];

const likedMaterials = new Set(loadJson(STORAGE_KEYS.liked, []));
let ratings = loadJson(STORAGE_KEYS.ratings, {});

setupFilters();
setupLikeButtons();
setupRatingControls();
updateVisibleCards();

function setupFilters() {
  filtersForm.addEventListener("submit", (event) => {
    event.preventDefault();
    updateVisibleCards();
  });

  [searchInput, disciplineFilter, courseFilter, typeFilter].forEach((field) => {
    field.addEventListener("input", updateVisibleCards);
    field.addEventListener("change", updateVisibleCards);
  });
}

function setupLikeButtons() {
  cards.forEach((card) => {
    const button = card.querySelector(".like-button");

    if (!button) {
      return;
    }

    button.addEventListener("click", () => {
      const materialId = card.dataset.id;

      if (likedMaterials.has(materialId)) {
        likedMaterials.delete(materialId);
      } else {
        likedMaterials.add(materialId);
      }

      saveJson(STORAGE_KEYS.liked, [...likedMaterials]);
      updateLikeButton(card);
    });

    updateLikeButton(card);
  });
}

function setupRatingControls() {
  cards.forEach((card) => {
    const ratingContainer = card.querySelector(".js-rating");

    if (!ratingContainer) {
      return;
    }

    renderRating(card, ratingContainer);
  });
}

function updateVisibleCards() {
  let visibleTotal = 0;

  cards.forEach((card) => {
    const isVisible = matchesFilters(card);
    card.classList.toggle("is-hidden", !isVisible);

    if (isVisible) {
      visibleTotal += 1;
    }
  });

  resultCount.textContent = formatResultCount(visibleTotal);
  emptyState.classList.toggle("is-visible", visibleTotal === 0);
}

function matchesFilters(card) {
  const search = normalizeText(searchInput.value);
  const discipline = disciplineFilter.value;
  const course = courseFilter.value;
  const type = typeFilter.value;
  const searchableText = normalizeText([
    card.dataset.title,
    card.dataset.description,
    card.dataset.type,
    card.dataset.discipline,
    card.dataset.course,
    card.dataset.author,
  ].join(" "));

  const matchesSearch = search === "" || searchableText.includes(search);
  const matchesDiscipline = discipline === "" || card.dataset.discipline === discipline;
  const matchesCourse = course === "" || card.dataset.course === course;
  const matchesType = type === "" || card.dataset.type === type;

  return matchesSearch && matchesDiscipline && matchesCourse && matchesType;
}

function updateLikeButton(card) {
  const button = card.querySelector(".like-button");
  const label = card.querySelector(".like-label");
  const count = card.querySelector(".like-count");
  const materialId = card.dataset.id;
  const baseLikes = Number(button.dataset.baseLikes || 0);
  const isLiked = likedMaterials.has(materialId);

  label.textContent = isLiked ? "Curtido" : "Curtir";
  count.textContent = String(baseLikes + (isLiked ? 1 : 0));
  button.classList.toggle("is-liked", isLiked);
  button.setAttribute("aria-pressed", String(isLiked));
}

function renderRating(card, container) {
  const materialId = card.dataset.id;
  const savedRating = Number(ratings[materialId]);
  const initialRating = Number(container.dataset.rating || 0);
  const currentRating = savedRating || initialRating;

  container.innerHTML = "";
  container.setAttribute("aria-label", `Avaliação ${currentRating} de 5`);

  for (let value = 1; value <= 5; value += 1) {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "star-button";
    button.classList.toggle("is-active", value <= currentRating);
    button.textContent = "★";
    button.title = `Avaliar com ${value} estrela${value > 1 ? "s" : ""}`;
    button.setAttribute("aria-label", button.title);
    button.addEventListener("click", () => {
      ratings = {
        ...ratings,
        [materialId]: value,
      };

      saveJson(STORAGE_KEYS.ratings, ratings);
      renderRating(card, container);
    });

    container.appendChild(button);
  }
}

function formatResultCount(total) {
  return total === 1 ? "1 material encontrado" : `${total} materiais encontrados`;
}

function normalizeText(text) {
  return String(text)
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

function loadJson(key, fallback) {
  try {
    const storedValue = localStorage.getItem(key);
    return storedValue ? JSON.parse(storedValue) : fallback;
  } catch {
    return fallback;
  }
}

function saveJson(key, value) {
  localStorage.setItem(key, JSON.stringify(value));
}
