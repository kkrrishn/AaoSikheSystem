/**
 * Animation Library for AaoSikheSystem
 * Advanced animations and scroll effects
 */

class AnimationManager {
    constructor() {
        this.observers = new Map();
        this.scrollEffects = new Map();
        this.animatedElements = new Set();
        this.init();
    }

    init() {
        this.setupScrollObserver();
        this.setupParallaxEffects();
        this.setupHoverAnimations();
        this.setupLoadingAnimations();
    }

    /**
     * Setup Intersection Observer for scroll animations
     */
    setupScrollObserver() {
        this.scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateOnScroll(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
    }

    /**
     * Setup parallax scrolling effects
     */
    setupParallaxEffects() {
        window.addEventListener('scroll', () => {
            this.applyParallaxEffects();
        });
    }

    /**
     * Setup hover animations
     */
    setupHoverAnimations() {
        document.addEventListener('DOMContentLoaded', () => {
            this.applyHoverAnimations();
        });
    }

    /**
     * Setup loading animations
     */
    setupLoadingAnimations() {
        window.addEventListener('load', () => {
            this.applyLoadingAnimations();
        });
    }

    /**
     * Register element for scroll animation
     */
    observeElement(selector, animationClass = 'animate-in') {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.classList.add('scroll-animate');
            this.scrollObserver.observe(element);
            this.animatedElements.add({ element, animationClass });
        });
    }

    /**
     * Add parallax effect to element
     */
    addParallax(selector, speed = 0.5) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            this.scrollEffects.set(element, { type: 'parallax', speed });
        });
    }

    /**
     * Add fade-in animation
     */
    addFadeIn(selector, delay = 0) {
        this.observeElement(selector, 'fade-in');
        const elements = document.querySelectorAll(selector);
        elements.forEach((element, index) => {
            element.style.animationDelay = `${delay + (index * 0.1)}s`;
        });
    }

    /**
     * Add slide-in animation
     */
    addSlideIn(selector, direction = 'left', delay = 0) {
        this.observeElement(selector, `slide-in-${direction}`);
        const elements = document.querySelectorAll(selector);
        elements.forEach((element, index) => {
            element.style.animationDelay = `${delay + (index * 0.1)}s`;
        });
    }

    /**
     * Add bounce animation
     */
    addBounce(selector) {
        this.observeElement(selector, 'bounce-in');
    }

    /**
     * Add pulse animation
     */
    addPulse(selector) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.classList.add('pulse-animate');
        });
    }

    /**
     * Add typewriter effect
     */
    addTypewriter(selector, speed = 50) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            const text = element.textContent;
            element.textContent = '';
            element.classList.add('typewriter');
            
            let i = 0;
            const timer = setInterval(() => {
                if (i < text.length) {
                    element.textContent += text.charAt(i);
                    i++;
                } else {
                    clearInterval(timer);
                }
            }, speed);
        });
    }

    /**
     * Add counter animation
     */
    addCounter(selector, duration = 2000) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            const target = parseInt(element.getAttribute('data-count')) || parseInt(element.textContent);
            const increment = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    element.textContent = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = target;
                }
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(element);
        });
    }

    /**
     * Add staggered animation to children
     */
    addStaggerAnimation(selector, animationClass, staggerDelay = 0.1) {
        const containers = document.querySelectorAll(selector);
        containers.forEach(container => {
            const children = container.children;
            Array.from(children).forEach((child, index) => {
                child.classList.add('stagger-animate');
                child.style.animationDelay = `${index * staggerDelay}s`;
                this.observeElementChild(child, animationClass);
            });
        });
    }

    /**
     * Handle scroll animations
     */
    animateOnScroll(element) {
        const elementData = Array.from(this.animatedElements).find(
            data => data.element === element
        );

        if (elementData) {
            element.classList.add(elementData.animationClass);
            this.animatedElements.delete(elementData);
        }
    }

    /**
     * Apply parallax effects on scroll
     */
    applyParallaxEffects() {
        const scrolled = window.pageYOffset;
        
        this.scrollEffects.forEach((effect, element) => {
            if (effect.type === 'parallax') {
                const speed = effect.speed;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            }
        });
    }

    /**
     * Apply hover animations
     */
    applyHoverAnimations() {
        // Card hover effects
        const cards = document.querySelectorAll('.card, .course-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Button hover effects
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', () => {
                button.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', () => {
                button.style.transform = 'translateY(0)';
            });
        });
    }

    /**
     * Apply loading animations
     */
    applyLoadingAnimations() {
        // Add loaded class to body
        document.body.classList.add('loaded');

        // Animate hero section
        const hero = document.querySelector('.hero-section');
        if (hero) {
            hero.classList.add('hero-loaded');
        }
    }

    /**
     * Create particle effect
     */
    createParticles(container, count = 30) {
        for (let i = 0; i < count; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.cssText = `
                position: absolute;
                width: 4px;
                height: 4px;
                background: rgba(255, 255, 255, 0.8);
                border-radius: 50%;
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                animation: float ${3 + Math.random() * 4}s infinite ease-in-out;
                animation-delay: ${Math.random() * 2}s;
            `;
            container.appendChild(particle);
        }
    }

    /**
     * Add magnetic button effect
     */
    addMagneticEffect(selector) {
        const buttons = document.querySelectorAll(selector);
        
        buttons.forEach(button => {
            button.addEventListener('mousemove', (e) => {
                const rect = button.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const deltaX = (x - centerX) / centerX;
                const deltaY = (y - centerY) / centerY;
                
                button.style.transform = `translate(${deltaX * 10}px, ${deltaY * 10}px)`;
            });
            
            button.addEventListener('mouseleave', () => {
                button.style.transform = 'translate(0, 0)';
            });
        });
    }

    /**
     * Add scroll progress indicator
     */
    addScrollProgress() {
        const progressBar = document.createElement('div');
        progressBar.className = 'scroll-progress';
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            z-index: 9999;
            transition: width 0.1s ease;
        `;
        
        document.body.appendChild(progressBar);
        
        window.addEventListener('scroll', () => {
            const winHeight = window.innerHeight;
            const docHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset;
            const progress = (scrollTop / (docHeight - winHeight)) * 100;
            
            progressBar.style.width = `${progress}%`;
        });
    }

    /**
     * Add page transition
     */
    addPageTransition() {
        const transition = document.createElement('div');
        transition.className = 'page-transition';
        transition.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 9999;
            transform: translateY(100%);
            transition: transform 0.6s ease-in-out;
        `;
        
        document.body.appendChild(transition);
        
        // Animate in on page load
        setTimeout(() => {
            transition.style.transform = 'translateY(0)';
        }, 100);
        
        // Animate out after content loads
        window.addEventListener('load', () => {
            setTimeout(() => {
                transition.style.transform = 'translateY(-100%)';
                setTimeout(() => {
                    transition.remove();
                }, 600);
            }, 500);
        });
    }

    /**
     * Initialize all animations
     */
    initializeAnimations() {
        // Add scroll progress
        this.addScrollProgress();

        // Add fade-in animations
        this.addFadeIn('.card, .course-card', 0.2);
        this.addFadeIn('.feature-icon', 0.3);
        
        // Add slide-in animations
        this.addSlideIn('.hero-section h1', 'up', 0.5);
        this.addSlideIn('.hero-section p', 'up', 0.7);
        this.addSlideIn('.hero-section .btn', 'up', 0.9);
        
        // Add staggered animations
        this.addStaggerAnimation('.row.g-4', 'fade-in-up', 0.1);
        
        // Add counter animations
        this.addCounter('.counter');
        
        // Add magnetic effects to buttons
        this.addMagneticEffect('.btn-primary, .btn-warning');
        
        // Add parallax to hero section
        this.addParallax('.hero-section', 0.3);
        
        // Add particles to hero section
        const hero = document.querySelector('.hero-section');
        if (hero) {
            this.createParticles(hero, 20);
        }
    }
}

// CSS Animation Keyframes
const animationStyles = `
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInUp {
    from { 
        opacity: 0;
        transform: translateY(50px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from { 
        opacity: 0;
        transform: translateX(-50px);
    }
    to { 
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from { 
        opacity: 0;
        transform: translateX(50px);
    }
    to { 
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes bounceIn {
    from, 20%, 40%, 60%, 80%, to {
        animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
    }
    0% {
        opacity: 0;
        transform: scale3d(.3, .3, .3);
    }
    20% {
        transform: scale3d(1.1, 1.1, 1.1);
    }
    40% {
        transform: scale3d(.9, .9, .9);
    }
    60% {
        opacity: 1;
        transform: scale3d(1.03, 1.03, 1.03);
    }
    80% {
        transform: scale3d(.97, .97, .97);
    }
    to {
        opacity: 1;
        transform: scale3d(1, 1, 1);
    }
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Animation Classes */
.animate-in {
    animation: fadeIn 0.6s ease-out forwards;
}

.fade-in {
    opacity: 0;
    animation: fadeIn 0.8s ease-out forwards;
}

.slide-in-up {
    opacity: 0;
    animation: slideInUp 0.8s ease-out forwards;
}

.slide-in-left {
    opacity: 0;
    animation: slideInLeft 0.8s ease-out forwards;
}

.slide-in-right {
    opacity: 0;
    animation: slideInRight 0.8s ease-out forwards;
}

.bounce-in {
    opacity: 0;
    animation: bounceIn 0.8s ease-out forwards;
}

.pulse-animate {
    animation: pulse 2s infinite;
}

.stagger-animate {
    opacity: 0;
}

.typewriter {
    border-right: 2px solid;
    white-space: nowrap;
    overflow: hidden;
}

/* Scroll animation base */
.scroll-animate {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease-out;
}

.scroll-animate.animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Hero loading animation */
.hero-section {
    opacity: 0;
    transform: translateY(50px);
    transition: all 1s ease-out;
}

.hero-section.hero-loaded {
    opacity: 1;
    transform: translateY(0);
}

/* Body loaded state */
body:not(.loaded) * {
    animation: none !important;
}
`;

// Add styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = animationStyles;
document.head.appendChild(styleSheet);

// Export AnimationManager
window.AnimationManager = AnimationManager;