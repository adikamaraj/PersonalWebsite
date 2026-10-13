/* Progressive enhancements; links, content, and country stories work without JavaScript. */
(() => {
  "use strict";
  document.documentElement.classList.add("js");
  const motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const header = document.querySelector(".site-header");
  const menu = document.querySelector(".menu-toggle");
  const closeMenu = () => {
    header?.classList.remove("menu-open");
    menu?.setAttribute("aria-expanded", "false");
  };
  menu?.addEventListener("click", () => {
    const open = header.classList.toggle("menu-open");
    menu.setAttribute("aria-expanded", String(open));
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && header?.classList.contains("menu-open")) {
      closeMenu();
      menu.focus();
    }
  });
  document.addEventListener("click", (event) => {
    if (header && !header.contains(event.target)) closeMenu();
  });
  header
    ?.querySelectorAll(".site-nav a")
    .forEach((link) => link.addEventListener("click", closeMenu));
  window.matchMedia("(min-width: 801px)").addEventListener("change", closeMenu);

  document
    .querySelectorAll('[data-client-filter="true"]')
    .forEach((collection) => {
      const cards = [...collection.querySelectorAll(".project-card")];
      if (!cards.length) return;
      const filters = [...collection.querySelectorAll("[data-filter]")];
      filters.forEach((link) =>
        link.addEventListener("click", (event) => {
          if (
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey ||
            event.button !== 0
          )
            return;
          event.preventDefault();
          const selected = link.dataset.filter;
          filters.forEach((filter) => {
            const active = filter === link;
            filter.classList.toggle("is-active", active);
            if (active) filter.setAttribute("aria-current", "true");
            else filter.removeAttribute("aria-current");
          });
          let count = 0;
          cards.forEach((card) => {
            card.hidden =
              selected !== "all" &&
              !card.dataset.space.split(" ").includes(selected);
            if (!card.hidden) count++;
          });
          collection.querySelector(".filter-status").textContent =
            `${count} ${count === 1 ? "project" : "projects"} shown.`;
        }),
      );
    });

  const range = document.querySelector("#price-range");
  range?.addEventListener("input", () => {
    const price = Number(range.value);
    document.querySelector("#ai-price").textContent = `€${price}`;
    range.setAttribute("aria-valuetext", `€${price}`);
    document.querySelector("#price-caption").textContent =
      price === 20
        ? "Same price. Different decision-maker."
        : price > 20
          ? `€${price - 20} more. Does the decision-maker matter?`
          : `€${20 - price} less. Does a lower price feel different?`;
  });
  const robot = document.querySelector(".robot-tool");
  const robotButton = document.querySelector(".robot-button");
  robotButton?.addEventListener("click", () => {
    const awake = robot.classList.toggle("is-awake");
    robotButton.setAttribute("aria-pressed", String(awake));
    robotButton.innerHTML = `${awake ? "Let the robot rest" : "Wake up the robot"} <span aria-hidden="true">↗︎</span>`;
    robot.querySelector(".robot-status").textContent = awake
      ? "Online. Curious. Still no closer to making coffee."
      : "Currently contemplating its next coffee.";
  });
  robot?.addEventListener("pointermove", (event) => {
    if (!robot.classList.contains("is-awake") || motion.matches) return;
    const rect = robot.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width - 0.5) * 7;
    const y = ((event.clientY - rect.top) / rect.height - 0.5) * 5;
    robot.querySelectorAll(".robot-eye").forEach((eye) => {
      eye.style.translate = `${x}px ${y}px`;
    });
  });
  robot?.addEventListener("pointerleave", () =>
    robot.querySelectorAll(".robot-eye").forEach((eye) => {
      eye.style.translate = "";
    }),
  );
  const questions = [
    "Who needs this enough to change what they do?",
    "What assumption could we test before we build?",
    "What would make a customer trust this?",
    "What is the smallest experiment that could teach us something?",
    "Which decision will this evidence help us make?",
  ];
  let question = 0;
  document.querySelector(".question-button")?.addEventListener("click", () => {
    question = (question + 1) % questions.length;
    document.querySelector("#startup-question").textContent =
      questions[question];
  });
  document
    .querySelector("[data-copy-link]")
    ?.addEventListener("click", async (event) => {
      const button = event.currentTarget;
      const status = document.querySelector(".copy-status");
      try {
        await navigator.clipboard.writeText(window.location.href);
        button.textContent = "Link copied";
        status.textContent = "Page link copied to clipboard.";
      } catch {
        status.textContent = "Copy this page’s URL from your address bar.";
      }
    });

  // An accessible country selector controls a locally rendered, decorative globe.
  const globeWrap = document.querySelector(".globe-wrap");
  if (!globeWrap) return;
  const canvas = globeWrap.querySelector("canvas");
  const context = canvas.getContext("2d");
  const countries = {
    india: { lat: 11.02, lon: 76.96, label: "COIMBATORE" },
    germany: { lat: 50.78, lon: 6.08, label: "AACHEN" },
    switzerland: { lat: 46.95, lon: 7.45, label: "BERN" },
  };
  let selected = "india",
    yaw = 65,
    targetYaw = 65,
    latitude = 20,
    targetLatitude = 20;
  let points = [],
    size = 480,
    visible = true,
    frame = 0,
    lastTime = 0;
  const rad = Math.PI / 180;
  const buttons = [...document.querySelectorAll("[data-place]")];
  const panels = [...document.querySelectorAll("[data-panel]")];
  const selectPlace = (place) => {
    if (!countries[place]) return;
    selected = place;
    buttons.forEach((button) =>
      button.setAttribute(
        "aria-pressed",
        String(button.dataset.place === place),
      ),
    );
    panels.forEach((panel) => {
      panel.hidden = panel.dataset.panel !== place;
    });
    targetYaw = countries[place].lon - 10;
    targetLatitude = countries[place].lat - 5;
    if (motion.matches) {
      yaw = targetYaw;
      latitude = targetLatitude;
      draw();
    } else startAnimation();
  };
  buttons.forEach((button) =>
    button.addEventListener("click", () => selectPlace(button.dataset.place)),
  );
  panels.forEach((panel) => {
    panel.hidden = panel.dataset.panel !== selected;
  });
  if (!context) return;
  const project = (lat, lon) => {
    const a = lat * rad,
      b = (lon - yaw) * rad,
      c = latitude * rad;
    const x = Math.cos(a) * Math.sin(b),
      y = Math.sin(a),
      z = Math.cos(a) * Math.cos(b);
    return [
      x,
      y * Math.cos(c) - z * Math.sin(c),
      y * Math.sin(c) + z * Math.cos(c),
    ];
  };
  function draw() {
    if (!context || !points.length) return;
    const radius = size * 0.37,
      cx = size / 2,
      cy = size * 0.47;
    context.clearRect(0, 0, size, size);
    const gradient = context.createRadialGradient(
      cx - radius * 0.4,
      cy - radius * 0.4,
      radius * 0.05,
      cx,
      cy,
      radius,
    );
    gradient.addColorStop(0, "#fafbf4");
    gradient.addColorStop(1, "#e0e4d7");
    context.fillStyle = gradient;
    context.beginPath();
    context.arc(cx, cy, radius, 0, Math.PI * 2);
    context.fill();
    context.strokeStyle = "#c5ceba";
    context.lineWidth = 0.7;
    context.beginPath();
    context.arc(cx, cy, radius, 0, Math.PI * 2);
    context.stroke();
    points.forEach((point) => {
      const [x, y, z] = project(point[0], point[1]);
      if (z <= 0) return;
      context.fillStyle = `rgba(77,96,61,${0.24 + z * 0.57})`;
      context.beginPath();
      context.arc(
        cx + x * radius,
        cy - y * radius,
        Math.max(0.6, size / 430) * (0.65 + 0.35 * z),
        0,
        Math.PI * 2,
      );
      context.fill();
    });
    Object.entries(countries).forEach(([key, country]) => {
      const [x, y, z] = project(country.lat, country.lon);
      if (z <= 0.03) return;
      const px = cx + x * radius,
        py = cy - y * radius,
        active = key === selected;
      context.fillStyle = active ? "#31597b" : "#607249";
      context.beginPath();
      context.arc(px, py, active ? 4 : 2.7, 0, Math.PI * 2);
      context.fill();
      if (!active) return;
      context.strokeStyle = "#31597b55";
      context.lineWidth = 1;
      context.beginPath();
      context.arc(px, py, 10, 0, Math.PI * 2);
      context.stroke();
      const labelWidth = Math.min(120, size * 0.25),
        labelHeight = 27,
        lx = Math.min(size - labelWidth - 15, Math.max(15, px + 15)),
        ly = py - 44;
      context.strokeStyle = "#31597b";
      context.beginPath();
      context.moveTo(px, py);
      context.lineTo(lx, ly + labelHeight);
      context.stroke();
      context.fillStyle = "#31597b";
      context.beginPath();
      context.roundRect(lx, ly, labelWidth, labelHeight, 5);
      context.fill();
      context.font = `${Math.max(8, size / 55)}px monospace`;
      context.textAlign = "center";
      context.textBaseline = "middle";
      context.fillStyle = "#fff";
      context.fillText(
        country.label,
        lx + labelWidth / 2,
        ly + labelHeight / 2,
      );
    });
    globeWrap.classList.add("is-rendered");
  }
  function animate(time) {
    frame = 0;
    if (!visible || document.hidden || motion.matches) return;
    const factor = Math.min(0.14, (time - lastTime || 16) / 180);
    lastTime = time;
    yaw += (targetYaw - yaw) * factor;
    latitude += (targetLatitude - latitude) * factor;
    draw();
    if (
      Math.abs(targetYaw - yaw) > 0.02 ||
      Math.abs(targetLatitude - latitude) > 0.02
    )
      frame = requestAnimationFrame(animate);
  }
  function startAnimation() {
    if (!frame && visible && !document.hidden && !motion.matches) {
      lastTime = 0;
      frame = requestAnimationFrame(animate);
    }
  }
  function resize() {
    size = Math.max(1, globeWrap.clientWidth);
    const scale = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = size * scale;
    canvas.height = size * scale;
    context.setTransform(scale, 0, 0, scale, 0, 0);
    draw();
  }
  new ResizeObserver(resize).observe(globeWrap);
  new IntersectionObserver(
    (entries) => {
      visible = entries[0].isIntersecting;
      if (visible) {
        draw();
        startAnimation();
      }
    },
    { rootMargin: "100px" },
  ).observe(globeWrap);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) startAnimation();
  });
  motion.addEventListener("change", () => {
    if (motion.matches) {
      cancelAnimationFrame(frame);
      frame = 0;
      yaw = targetYaw;
      latitude = targetLatitude;
      draw();
    } else startAnimation();
  });
  fetch(globeWrap.dataset.pointsUrl)
    .then((response) => {
      if (!response.ok) throw new Error("Map unavailable");
      return response.json();
    })
    .then((data) => {
      points = data.filter(
        (point) =>
          Array.isArray(point) &&
          point.length === 2 &&
          point.every(Number.isFinite),
      );
      resize();
    })
    .catch(() => {
      globeWrap.classList.add("map-unavailable");
    });
})();
