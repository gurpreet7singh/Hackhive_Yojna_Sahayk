
/* =========================================
   YOJANA SAHAYAK — INTERACTIVE HERO
   Cursor-responsive 3D typography
   + Floating particles + fluid glow
========================================= */

document.addEventListener("DOMContentLoaded", () => {

    const hero = document.querySelector(".hero");
    const title = document.getElementById("interactiveTitle");
    const glow = document.querySelector(".cursor-glow");
    const canvas = document.getElementById("particleCanvas");

    if (!hero || !title || !canvas) return;


    /* =====================================
       1. INTERACTIVE TYPOGRAPHY
    ===================================== */

    const originalText = title.textContent.trim();

    title.innerHTML = originalText
        .split("")
        .map(char => `<span class="char">${char}</span>`)
        .join("");

    const characters = document.querySelectorAll(".char");

    const mouse = {
        x: -1000,
        y: -1000,
        active: false
    };

    const isDesktop = window.matchMedia("(pointer: fine)").matches;

    let animationFrame;


    /* Cursor position */

    document.addEventListener("mousemove", (event) => {

        mouse.x = event.clientX;
        mouse.y = event.clientY;
        mouse.active = true;

        if (glow) {
            glow.style.left = `${mouse.x}px`;
            glow.style.top = `${mouse.y}px`;
        }

    });


    /* 3D character movement */

    function animateText() {

        characters.forEach((char) => {

            const rect = char.getBoundingClientRect();

            const charX = rect.left + rect.width / 2;
            const charY = rect.top + rect.height / 2;

            const distanceX = mouse.x - charX;
            const distanceY = mouse.y - charY;

            const distance = Math.sqrt(
                distanceX * distanceX +
                distanceY * distanceY
            );

            const radius = 220;

            if (distance < radius && distance > 0) {

                const force = (radius - distance) / radius;

                const moveX =
                    -(distanceX / distance) * force * 55;

                const moveY =
                    -(distanceY / distance) * force * 45;

                const rotateY =
                    (distanceX / radius) * 25;

                const rotateX =
                    -(distanceY / radius) * 20;

                const scale =
                    1 + force * 0.18;

                char.style.transform = `
                    translate3d(${moveX}px, ${moveY}px, ${force * 35}px)
                    rotateX(${rotateX}deg)
                    rotateY(${rotateY}deg)
                    scale(${scale})
                `;

                char.style.color = force > 0.65
                    ? "#E8D5A1"
                    : "#F5F3E9";

            } else {

                char.style.transform = `
                    translate3d(0px, 0px, 0px)
                    rotateX(0deg)
                    rotateY(0deg)
                    scale(1)
                `;

                char.style.color = "";

            }

        });

        animationFrame = requestAnimationFrame(animateText);

    }


    /* =====================================
       2. PARTICLE BACKGROUND
    ===================================== */

    const ctx = canvas.getContext("2d");

    let width;
    let height;

    let particles = [];

    const PARTICLE_COUNT = 90;

    const goldColor = "213, 180, 106";
    const ivoryColor = "245, 243, 233";


    /* Canvas sizing */

    function resizeCanvas() {

        const rect = hero.getBoundingClientRect();

        width = rect.width;
        height = rect.height;

        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        canvas.width = width * dpr;
        canvas.height = height * dpr;

        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;

        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        createParticles();

    }


    /* Create particles */

    function createParticles() {

        particles = [];

        const count = width < 768
            ? 35
            : PARTICLE_COUNT;

        for (let i = 0; i < count; i++) {

            particles.push({

                x: Math.random() * width,
                y: Math.random() * height,

                size: Math.random() * 1.8 + 0.4,

                speedX: (Math.random() - 0.5) * 0.35,
                speedY: (Math.random() - 0.5) * 0.35,

                opacity: Math.random() * 0.5 + 0.15,

                color: Math.random() > 0.65
                    ? goldColor
                    : ivoryColor

            });

        }

    }


    /* Draw and animate particles */

    function animateParticles() {

        ctx.clearRect(0, 0, width, height);

        particles.forEach((particle) => {

            // Natural drifting movement

            particle.x += particle.speedX;
            particle.y += particle.speedY;


            // Wrap around screen edges

            if (particle.x < 0) particle.x = width;
            if (particle.x > width) particle.x = 0;

            if (particle.y < 0) particle.y = height;
            if (particle.y > height) particle.y = 0;


            // Cursor repulsion

            if (mouse.active) {

                const dx = particle.x - mouse.x;
                const dy = particle.y - mouse.y;

                const distance = Math.sqrt(dx * dx + dy * dy);

                const interactionRadius = 140;

                if (distance < interactionRadius && distance > 0) {

                    const force =
                        (interactionRadius - distance) /
                        interactionRadius;

                    particle.x += (dx / distance) * force * 1.5;
                    particle.y += (dy / distance) * force * 1.5;

                }

            }


            // Draw particle

            ctx.beginPath();

            ctx.arc(
                particle.x,
                particle.y,
                particle.size,
                0,
                Math.PI * 2
            );

            ctx.fillStyle =
                `rgba(${particle.color}, ${particle.opacity})`;

            ctx.fill();


            // Subtle halo for larger particles

            if (particle.size > 1.5) {

                ctx.beginPath();

                ctx.arc(
                    particle.x,
                    particle.y,
                    particle.size * 3,
                    0,
                    Math.PI * 2
                );

                ctx.fillStyle =
                    `rgba(${particle.color}, 0.035)`;

                ctx.fill();

            }

        });


        requestAnimationFrame(animateParticles);

    }


    /* =====================================
       3. MOUSE LEAVE
    ===================================== */

    document.addEventListener("mouseleave", () => {

        mouse.active = false;

        characters.forEach((char) => {

            char.style.transform = "";
            char.style.color = "";

        });

    });


    /* =====================================
       4. INITIALIZE
    ===================================== */

    resizeCanvas();

    window.addEventListener("resize", resizeCanvas);

    if (isDesktop) {

        animateText();

    }

    animateParticles();


});
