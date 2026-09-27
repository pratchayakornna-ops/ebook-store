/**
 * assets/app.js - NEXTREAD Dynamic Page Transitions & Micro-Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initial Page Entrance Trigger & Top Progress Bar
    let progressBar = document.getElementById('page-progress-bar');
    if (!progressBar) {
        progressBar = document.createElement('div');
        progressBar.id = 'page-progress-bar';
        document.body.prepend(progressBar);
    }

    // Quick subtle pulse on page load
    progressBar.style.opacity = '1';
    progressBar.style.width = '60%';
    setTimeout(() => {
        progressBar.style.width = '100%';
        setTimeout(() => {
            progressBar.style.opacity = '0';
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 300);
        }, 150);
    }, 100);

    // 2. Scroll Reveal for Interactive Elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -20px 0px'
    };

    const scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('stagger-item');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.tilt-card, .observe-fade').forEach(el => {
        scrollObserver.observe(el);
    });

    // 3. Subtle 3D Mouse Parallax Effect for Featured Cards
    document.querySelectorAll('.tilt-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            const tiltX = (y / (rect.height / 2)) * -5;
            const tiltY = (x / (rect.width / 2)) * 5;

            card.style.transform = `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateY(-4px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)';
        });
    });
});

// Always clear progress bar on page show/restore
window.addEventListener('pageshow', () => {
    const progressBar = document.getElementById('page-progress-bar');
    if (progressBar) {
        progressBar.style.opacity = '0';
        progressBar.style.width = '0%';
    }
});
